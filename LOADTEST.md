# Load Test Results — Phase 9

Environment: Windows/Laragon Tier 0 pilot — Apache 2.4 + mod_php, MySQL 8 on localhost,
2000 seeded pieces (~1900 approved), no CDN, no Redis. Tool: ApacheBench 2.3.

## Results (`ab -n 300 -c 25`, zero failed requests)

| Endpoint | Mean per request | Throughput | Budget (doc §5) | Verdict |
|---|---|---|---|---|
| `/` (feed w/ Livewire mount) | **222 ms** | 113 req/s | < 500 ms uncached | ✅ |
| `/explore?script=1` (filtered grid) | **19 ms** | 1 333 req/s | < 500 ms | ✅ |
| `/pieces/3` (detail + related) | **12 ms** | 2 069 req/s | < 300 ms search-class pages | ✅ |

## Notes

- Home is the slowest path because every anonymous request mounts the Livewire
  feed component (first-page ID list comes from cache; the component render is
  the remaining cost). Still comfortably inside budget.
- Explore/detail are near-instant thanks to the FULLTEXT index, seller-scoped
  eager loading, and keyset-friendly queries.
- These are single-box Tier-0 numbers; the doc's Tier-1 stack (Redis, Octane,
  Meilisearch, CDN) exists to multiply this headroom under real traffic.
- Re-run after any feed/query change:
  `ab -n 300 -c 25 -H "Host: call_store.test" http://127.0.0.1/`
