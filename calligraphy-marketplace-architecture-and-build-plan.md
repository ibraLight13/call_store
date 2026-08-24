# خَطّ — Architecture & Phased Build Plan

*Marketplace + dashboards. Written from the seat of a senior engineer: every choice below is the one I'd defend in a design review, with the reasoning attached. Companion to the Phase A foundation doc (spec + database schema).*

---

## 0. TL;DR

**Architecture:** a **modular monolith** — one Laravel codebase, one database, split internally into bounded modules (Identity, Catalog, Discovery, Social, Orders, Commissions, Payments, Moderation). Business logic lives in **Action classes**, authorization in **Policies**, the commission lifecycle in a **state machine**, and all slow work behind **queued events**. Filament powers the two dashboards; Livewire powers the interactive consumer front; most public pages are cached HTML.

**Why not more:** microservices and repository-over-Eloquent DDD are both premature here — they'd spend your runway on infrastructure and ceremony instead of features. The modular monolith gives you the isolation and testability those patterns promise, while staying one thing you can deploy, cache, and reason about. If a single module ever needs independent scale, the boundaries are already drawn to carve it out.

**Stack (latest stable, Aug 2026):** Laravel 13 · Livewire 4 · Filament 4 · Tailwind 4 · PHP 8.3+ · MySQL 8 · Redis. *(Filament 5 is beta-only right now — we ship on 4 and upgrade later.)*

**Build order:** 10 phases, each independently shippable, from tooling foundation → identity → catalog → discovery → social → ready-made commerce → commissions → dashboards → trust/polish → hardening & launch. Then the AI phase.

---

## 1. The stack, and why each piece

| Layer | Choice | Why this, now |
|---|---|---|
| Framework | **Laravel 13** (13.26+) | Current major; yearly cadence keeps upgrades small. Ships a first-party image-processing helper and the new **Laravel AI SDK** — the latter drops straight into your planned AI phase instead of a third-party bolt-on. Requires PHP 8.3+. |
| Interactivity | **Livewire 4** | Stable since Jan 2026. Single-file components, **islands** (isolated re-rendering — a "like" doesn't re-render the feed), optimistic UI, and a new diffing engine. The islands + optimistic-UI features are exactly what a snappy social feed and a live commission workspace need. |
| Admin/dashboards | **Filament 4** | Latest **stable**. Schema-driven (define UI in PHP), built-in MFA, Tailwind 4, and 2–3× faster table rendering than v3 — which matters for a moderation queue and seller order lists. **Filament 5 is still beta**; shipping a store on a beta is the wrong risk. Upgrade path to v5 is a composer constraint bump when it stabilizes. |
| CSS | **Tailwind 4** | Comes with Filament 4; faster builds, modern config. One RTL-first build serves Arabic and English via logical properties. |
| PHP | **8.3+ (8.4/8.5)** | Required by L13; enums, readonly, typed everything — leaned on hard for clean domain code. |
| DB | **MySQL 8** | FULLTEXT for the v1 Arabic search, JSON columns for translations, window functions for reporting. Postgres is a fine alternative; MySQL matches your existing hosting. |
| Cache/queue/session | **Redis** (prod) | The single biggest lever for "fast under real traffic." Falls back to the database driver on shared hosting (see §5 hosting tiers). |

**Key first-party & community packages** (each earns its place; nothing speculative):
`laravel/scout` (search abstraction) · `laravel/horizon` (queue dashboard, Redis) · `laravel/octane` + FrankenPHP (throughput) · `laravel/cashier`-adjacent **Stripe PHP SDK** for Connect (Cashier is subscription-shaped; a marketplace needs raw Connect) · `spatie/laravel-permission` (roles) · `spatie/laravel-model-states` (commission state machine) · `spatie/laravel-data` (DTOs at boundaries) · `spatie/laravel-medialibrary` **or** a custom media table (§ decided in Phase A) · `intervention/image` (derivatives/watermark) · Dev: `pestphp/pest`, `larastan/larastan`, `laravel/pint`, `rector/rector`.

---

## 2. Architecture — the shape of the code

### 2.1 The verdict and the reasoning

This app has **two workloads living in one product**:

1. **Public discovery** — the feed, search, piece and profile pages. Enormous read volume, latency-sensitive, highly cacheable, almost no per-request writes.
2. **Transactional flows** — commissions, checkout, payouts. Lower volume, stateful, correctness- and money-critical.

A **modular monolith** is the right container for both. It keeps them in one deployable (cheap to run, easy to reason about, one migration path), while **module boundaries keep their code physically separate** — the payments code never reaches into discovery internals, and vice versa. You scale the read path with caching, a CDN, and read replicas **without** splitting services. And because the seams are drawn along business capabilities, if one module (say search, or media) ever outgrows the monolith, you extract *that one* — you don't pre-pay for a distributed system you don't need.

**What we deliberately do *not* do, and why:**

- **Microservices → no.** They introduce network hops, partial failures, and distributed transactions. Coordinating "charge the buyer, transfer to the seller, advance the commission state" across services is a correctness nightmare for zero benefit at startup scale. You'd burn months on infra you can't yet justify.
- **Repository pattern over Eloquent → no.** Eloquent *is* the persistence abstraction. Wrapping it in repositories doubles the surface area and fights the framework, buying testability you already get from Actions + model factories. Use **models + query scopes**.
- **Full hexagonal / heavyweight DDD tactical patterns → no.** Keep the *good* DDD ideas — bounded contexts, a shared vocabulary, aggregate-shaped models — and skip the ports/adapters/entities/value-object ceremony that slows a small team down.
- **Fat controllers or fat models → no.** Controllers orchestrate and stay thin; models hold persistence, relationships, and scopes; **behavior lives in Actions.**

### 2.2 Where logic lives (the request lifecycle)

```
HTTP / Livewire / Filament / API / AI module
        │  (thin: parse input, authorize, hand off)
        ▼
   Form Request  ──►  validation
        ▼
   Policy         ──►  authorization (data-layer, not UI)
        ▼
   ACTION         ──►  the business operation (the reusable core)
        │  emits
        ▼
   Domain Event   ──►  queued listeners: counters, search index,
        │               notifications, cache invalidation, webhooks
        ▼
   Eloquent Model ──►  persistence + relations + scopes
```

Every front-end calls the **same Action**. `PublishPiece`, `ApprovePiece`, `RejectPiece`, `SaveToBoard`, `SubmitCommissionRequest`, `QuoteCommission`, `PayCommissionDeposit`, `SubmitProof`, `ApproveProof`, `PayCommissionBalance`, `ShipCommission`, `CheckoutCart`, `RefundOrder` — each is one class, one public `handle()`, independently testable, reused by Filament (admin/seller), Livewire (consumer), a future REST/mobile API, **and the future AI module**. Write the rule once; never duplicate it per screen.

**Illustrative Action shape:**
```php
final class ApproveProof
{
    public function __construct(private Clock $clock) {}

    public function handle(User $buyer, CommissionProof $proof): Commission
    {
        Gate::forUser($buyer)->authorize('approve', $proof);   // Policy
        $commission = $proof->commission;
        $commission->state->transitionTo(AwaitingBalance::class); // state machine guards validity

        $proof->update(['buyer_response' => 'approved', 'responded_at' => $this->clock->now()]);
        ProofApproved::dispatch($commission);                  // queued side effects
        return $commission;
    }
}
```

### 2.3 Bounded contexts (modules)

| Module | Owns |
|---|---|
| **Identity** | users, profiles, follows, roles, seller onboarding (Stripe Connect account) |
| **Catalog** | pieces, media pipeline, taxonomy (scripts/mediums/themes/colors/tags) |
| **Discovery** | feed, search, filtering, related pieces, caching |
| **Social** | boards/saves, likes, comments, notifications |
| **Orders** | cart, ready-made checkout, order/order_items, shipping, addresses |
| **Commissions** | the request → quote → proof → delivery lifecycle (state machine) |
| **Payments** | Stripe Connect charges, transfers, application fees, refunds, webhooks, ledger |
| **Moderation** | review queue, approvals, reports, admin policy |

Rule: modules talk to each other **through Actions and events**, never by reaching into another module's models directly. That one rule is what keeps the monolith from rotting into a big ball of mud.

### 2.4 Suggested directory layout

```
app/
├─ Domain/
│  ├─ Identity/{Models, Actions, Policies, Events, Data}
│  ├─ Catalog/{Models, Actions, Policies, Events, Data, Media}
│  ├─ Discovery/{Actions, Queries, Cache}
│  ├─ Social/{Models, Actions, Policies, Events}
│  ├─ Orders/{Models, Actions, Policies, Events, Data}
│  ├─ Commissions/{Models, Actions, States, Policies, Events, Data}
│  ├─ Payments/{Actions, Gateway, Webhooks, Models}
│  └─ Moderation/{Actions, Policies}
├─ Http/{Controllers(thin), Requests, Middleware}
├─ Livewire/            # consumer-facing components (feed, workspace, boards)
├─ Filament/
│  ├─ Admin/            # /admin panel — Resources, Pages, Widgets
│  └─ Seller/           # /studio panel — Resources, Pages, Widgets
└─ Support/             # Clock, Money cast, shared value objects
```

### 2.5 Two cross-cutting design decisions

- **Commission lifecycle = state machine** (`spatie/laravel-model-states`). States: `Requested → Quoted → Accepted → DepositPaid → InProgress → ProofSubmitted ⇄ RevisionRequested → Approved → BalancePaid → Shipped → Completed`, plus `Declined/Cancelled/Refunded`. Each transition declares who may trigger it and what side effects fire. **The machine makes illegal money flows impossible** — you cannot ship before the balance is paid, or start work before the deposit clears, because those transitions aren't wired. This is the cleanest possible home for the rules and doubles as an audit trail.
- **DTOs at boundaries** (`spatie/laravel-data`). Input from Livewire/Filament/API is validated into a typed Data object before it reaches an Action, so the domain never touches raw request arrays. Same DTOs feed the future API and AI module.

---

## 3. Clean-code principles this codebase holds to

Concrete rules, not slogans:

- **Single Responsibility, enforced by Actions.** One business operation = one class. If a method does two things, it becomes two Actions.
- **Thin edges, fat core.** Controllers/Livewire/Filament classes only parse, authorize, and delegate. No queries or business rules in a controller or a Filament resource — ever.
- **Dependency inversion at the seams.** The payment gateway sits behind a `PaymentGateway` interface; Stripe is one implementation. "Decide the gateway later" costs nothing because nothing in the domain names Stripe.
- **Tell, don't ask.** Behavior lives next to the data it changes (model methods/scopes + Actions), not scattered across services that yank data out to manipulate it.
- **One-way module dependencies.** No circular references between modules; shared primitives live in `Support/`. Enforced in CI with a dependency linter (Deptrac).
- **Explicit types everywhere.** PHP 8.3 enums for statuses, readonly DTOs, typed properties/returns. The type system is the first test.
- **Names from the domain.** `Commission`, `Proof`, `Payout`, `Script` — the ubiquitous language of calligraphy commerce, mirrored in code, UI, and this doc.
- **Errors direct the user.** Domain exceptions carry actionable messages; the UI never shows a stack trace or a vague "something went wrong."
- **No premature abstraction.** Extract the third repetition, not the first. Build for today's requirements; the module seams already make tomorrow cheap.

Quality gates run in CI on every push (see §6) so these aren't aspirational — they're merge-blocking.

---

## 4. The dashboards

Three surfaces, deliberately split by *who uses them* and *what they need*:

**Admin panel — Filament, `/admin`, admin role only.** Moderation queue (approve/reject pending pieces), users, taxonomy management, order/commission oversight, disputes/refunds, payouts overview, platform settings (commission %, default currency, auto-publish toggle, fonts). Correctness over flash; Filament's schema-driven CRUD is exactly right here.

**Seller panel — Filament, `/studio`, sellers only.** The seller's own pieces (create/edit/archive), incoming commission requests + quoting, orders to fulfil, earnings and Stripe Connect payouts, simple analytics (views, saves, conversion). Filament again — it's CRUD- and table-heavy, the last place you want hand-rolled UI.

**Commission workspace — *not* a dashboard, lives in the consumer app (Livewire).** Because **both** the buyer and the seller collaborate here (brief, proofs, approvals, chat, payments), it can't sit inside the seller's admin panel. It's a shared, branded, real-time surface in the main app. This split is the reason we don't just "put everything in Filament."

All three call the **same Actions and Policies**. A quote created in the seller panel and a quote created via API run identical code. Filament is multi-panel (admin + seller) out of the box in v4.

---

## 5. Performance & scalability — "handle real traffic and stay fast"

The public read path is where traffic concentrates, so it's engineered to mostly **not hit the database or PHP at all**.

**Read path (the hot path):**
- **Cursor / keyset pagination** for the infinite feed — never `OFFSET`. Offset pagination slows down linearly as users scroll deep; keyset stays constant. This is the single most important query decision for a Pinterest-style feed.
- **Redis caching** of taxonomy, the feed pages, and rendered piece-card fragments; **invalidated by domain events** (publishing/editing a piece busts the relevant keys). Aim for cache hits on the vast majority of anonymous feed requests.
- **Denormalized counters** (`likes_count`, `saves_count`, `views_count`) maintained by queued listeners and atomic increments. A list view **never** runs `COUNT()`.
- **N+1 eliminated by default** — eager loading everywhere, plus `Model::preventLazyLoading()` in non-production so a stray lazy load fails the test suite instead of shipping.
- **Media never served by PHP.** Originals in private object storage (S3/R2); pre-generated responsive derivatives (WebP/AVIF, multiple widths, watermarked) served from a **CDN**, lazy-loaded, with width/height set to prevent layout shift. This offloads the heaviest bytes entirely.

**Write path:**
- **Queue everything non-interactive** — notifications, image derivative generation, search indexing, feed-related fan-out, webhook processing — behind Redis + **Horizon**. User requests return immediately; the work happens out of band.
- **Rate limiting** on cheap-but-abusable writes (comments, follows, saves).
- **Idempotent Stripe webhooks** keyed on event id, so a retried webhook can't double-charge, double-transfer, or double-advance a commission.

**Throughput & search:**
- **Octane + FrankenPHP** for 3–5× request throughput once on a VPS (keeps the framework booted between requests).
- **Search via Scout** — MySQL FULLTEXT for the v1 pilot, **Meilisearch/Typesense** when infra allows, with Arabic diacritic-normalization (the `normalized_text` column from Phase A). Swapping engines is a config change, not a rewrite.
- **DB**: the indexes from Phase A, then a **read replica** for discovery queries if/when read volume demands it.

**Rough performance budget** (targets to test against, not guarantees): cached feed TTFB < 200 ms; uncached feed page < 500 ms; search < 300 ms; image LCP driven by CDN, not origin. Verified with load tests before launch (§6).

### Hosting tiers — an honest recommendation

"Fast under real user traffic" and "shared hosting" pull against each other. Be deliberate about which tier you're on:

| Tier | Setup | Reality |
|---|---|---|
| **0 — Pilot** | DirectAdmin shared hosting. DB-backed cache/queue/sessions, cron-driven `queue:work`, DB FULLTEXT search, media via a CDN in front of local disk. | Runs and validates the product. **No Redis, Horizon, or Octane** → limited concurrency, slower under load. Fine to prove the idea; not "real traffic, fast." |
| **1 — Recommended launch** | A modest VPS (or Laravel Forge + a cloud VM): **Redis + Horizon + Octane/FrankenPHP**, object storage (S3/R2) + CDN (Cloudflare/Bunny), **Meilisearch**. | This is the tier that actually delivers the performance budget above. Start here for a serious launch. |
| **2 — Scale** | Managed DB with read replica, autoscaling app nodes behind a load balancer, dedicated search cluster, image CDN with on-the-fly transforms. | Only when metrics demand it. The modular monolith reaches this without a rewrite. |

**Recommendation:** target **Tier 1** for a real launch. Treat shared hosting strictly as a stopgap for a low-traffic pilot — the code runs on both because every performance feature degrades gracefully to a database driver, but don't mistake the pilot tier for production-grade speed.

---

## 6. Testing & quality gates

- **Pest** for tests. A **feature test per Action** (the business API is where correctness lives), unit tests for pure logic (Arabic normalization, Money, state-machine guards), and a handful of end-to-end tests for the critical money paths (checkout, deposit→proof→balance).
- **Larastan / PHPStan at max level** — static analysis catches whole bug classes before runtime.
- **Laravel Pint** (style) and **Rector** (safe modernization) keep the codebase uniform.
- **Deptrac** enforces the module dependency rules from §2.3 so the architecture can't silently erode.
- **CI on every push**: Pint → Larastan → Pest → Deptrac, all merge-blocking.
- **Load testing** (k6 or Artillery) against the feed, search, and checkout before launch, to validate the §5 budget under realistic concurrency.

---

## 7. Observability & operations

Logging with context, **error tracking** (Sentry or Flare), **Laravel Pulse** for real-time app health (slow queries, queue depth, cache hit rate), **Telescope** in non-prod for debugging, **Horizon** for queue visibility. Automated **database + media backups** with tested restores. Staging environment mirroring production; deploys via Forge/Envoyer (or a CI pipeline) with zero-downtime and migration safety checks.

---

## 8. Build phases

Ten phases, each **independently shippable** and each with a **Definition of Done (DoD)**. Order chosen so every phase builds on a stable predecessor and delivers something demonstrable.

### Phase 0 — Foundations & tooling
Repo, Laravel 13 install, PHP 8.3+, the package set, the module/directory skeleton (§2.4), RTL-first base layout + design tokens, locale switching (ar default), `spatie/laravel-permission` roles, auth scaffolding, CI (Pint/Larastan/Pest/Deptrac), local env (Laragon), and the **hosting-tier decision**.
**DoD:** green CI on an empty-but-structured app; a themed, bilingual shell renders; roles seedable.

### Phase 1 — Identity & profiles
Users, public profiles (`/@username`), follow/unfollow, avatar/cover media base, locale persistence, RTL/LTR switch. Filament **admin panel skeleton** + user management.
**DoD:** sign up, edit profile, follow a user, switch language; admin can view/manage users.

### Phase 2 — Catalog & media
Pieces, taxonomy (scripts/mediums/themes/colors/tags, seeded bilingual), the **media pipeline** (private originals + watermarked responsive derivatives to object storage/CDN), the create-piece flow, `normalized_text` generation for Arabic search, and the **moderation queue** (`PublishPiece`/`ApprovePiece`/`RejectPiece` + `PieceState`).
**DoD:** a seller creates a piece → it enters review → an admin approves → it's publicly visible with correct derivatives.

### Phase 3 — Discovery
Masonry **feed with cursor pagination**, **search** (Scout + FULLTEXT + Arabic normalization), filters (script/theme/medium/color/price/type), piece detail, related pieces, and the **caching layer** with event-based invalidation. First real performance pass.
**DoD:** browse and search thousands of seeded pieces fast; filters work; cached anonymous feed hits the performance budget locally.

### Phase 4 — Social
Boards/saves, likes, comments (one level of replies), **notifications** (database + mail), **denormalized counters** via queued events, and write **rate limiting**.
**DoD:** save to a board, like, comment, and receive notifications; counters stay correct under concurrent actions; abuse is rate-limited.

### Phase 5 — Commerce (ready-made)
Cart, **seller Stripe Connect onboarding**, ready-made checkout, orders/order_items, **split payments** (destination charges + application fee), shipping/addresses, order statuses, seller order management, and **idempotent webhooks**.
**DoD:** buy pieces from two sellers in one cart → one payment splits correctly → each seller sees their order → a refund reverses cleanly.

### Phase 6 — Commissions (the differentiator)
The commission request form, the **state machine** with every guarded transition, quoting, **deposit + balance PaymentIntents**, the **proof/approval loop**, the buyer↔seller message thread, reference uploads, and per-transition notifications. Both buyer and seller views of the shared workspace.
**DoD:** a full lifecycle runs end-to-end — request → quote → deposit → proof → revision → approval → balance → ship → complete — with no illegal transition possible and money released only on approval.

### Phase 7 — Seller dashboard
The Filament **seller panel** (`/studio`): pieces, incoming commission requests + quoting, orders to fulfil, earnings/payouts, basic analytics.
**DoD:** a seller runs their whole business from the panel; every operation reuses the Phase 2/5/6 Actions.

### Phase 8 — Trust & polish
Reviews (post-completion), reporting/abuse handling, SEO + Open Graph + sitemap, i18n completeness, accessibility pass (focus, reduced motion, contrast), and considered empty/error/loading states.
**DoD:** verified reviews appear on profiles; the site is indexable, accessible, and fully bilingual with no untranslated strings.

### Phase 9 — Hardening & launch
Security review (policies on every model, webhook idempotency, file-upload validation, rate limits), **load testing** to the performance budget, caching/CDN finalization, observability wired (Pulse/Sentry/Horizon), backups tested, staging→prod, and a launch checklist.
**DoD:** load tests pass at target concurrency; security review clean; one-command zero-downtime deploy to production.

### Post-launch — the AI phase
The arabicdesign.ai idea, built on Laravel 13's **AI SDK**: a customer generates a concept/preview, then orders a **handmade** version from a calligrapher (AI for ideation, human for the final piece). Slots in as new Actions + a Livewire flow, reusing the commission pipeline — exactly the extension the module boundaries were drawn to allow. Alongside it: Meilisearch cutover, Octane, and analytics deepening as traffic grows.

---

## 9. Decision summary

| Question | Decision | One-line why |
|---|---|---|
| Overall architecture | Modular monolith | Isolation + testability without distributed-systems cost |
| Business logic home | Action classes | One operation per class, reused by every front-end |
| Authorization | Policies + spatie/permission, at the data layer | UI hiding isn't security |
| Commission lifecycle | State machine | Makes illegal money flows impossible; free audit trail |
| Persistence | Eloquent + scopes, **no** repositories | Eloquent is already the abstraction |
| Boundaries | DTOs (spatie/laravel-data) | Domain never touches raw request arrays |
| Feed paging | Cursor/keyset | Constant speed at any scroll depth |
| Slow work | Queued events + Horizon | Keeps user requests fast |
| Media | Object storage + CDN, never PHP | Offloads the heaviest bytes |
| Search | Scout → FULLTEXT then Meilisearch | Engine swap is config, not rewrite |
| Admin & seller UI | Filament 4 (multi-panel) | Schema-driven CRUD where hand-rolling wastes time |
| Commission workspace | Livewire, in consumer app | Buyer + seller share it — can't live in an admin panel |
| Stack | Laravel 13 · Livewire 4 · Filament 4 · PHP 8.3+ | Latest **stable**; Filament 5 beta deferred |
| Launch hosting | VPS (Tier 1): Redis + Horizon + Octane + CDN + Meilisearch | The tier that's actually fast under real traffic |

---

*Next: I can turn any phase into an execution ticket set (migrations, models, Actions, tests, Filament resources, Livewire components) — copy-paste-ready, one phase at a time, with an approval gate between each.*
