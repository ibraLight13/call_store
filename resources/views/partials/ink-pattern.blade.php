{{-- Decorative calligraphy layer: letters and tashkeel "write" themselves (stroke draws, ink fills, fades) on a staggered cycle.
     Letters mix the styles we can serve as web fonts — Naskh (Amiri), Ruq'ah (Aref Ruqaa), Kufic (Reem Kufi), Nasta'liq (Gulzar).
     Tashkeel stands on its own beside the letters as drawn marks (paths), not attached to them.
     ponytail: no free Thuluth/Diwani web font exists; add them here once a licensed font is self-hosted.
     Purely ornamental, so hidden from assistive tech. Positions are fixed so every render is identical (cache-safe).
     Gulzar is subset in the layout to exactly its letters below (ن س ق) — keep them in sync. --}}
@php
    // Original mark shapes in a 100×100 box, drawn as single pen strokes.
    $marks = [
        'fatha' => 'M12 72 Q48 52 88 26',
        'kasra' => 'M12 74 Q50 56 88 30',
        'damma' => 'M60 44 C44 22 76 12 76 36 C76 58 54 82 22 94',
        'shadda' => 'M10 42 Q18 74 34 48 Q46 76 60 46 Q72 74 92 30',
        'sukun' => 'M50 26 a22 22 0 1 0 0.1 0',
        'tanween' => 'M12 58 Q48 40 88 18 M12 86 Q48 68 88 46',
    ];
@endphp
<svg class="ink-pattern" aria-hidden="true" focusable="false" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice">
    @foreach ([
        ['ب', 90, 150, 150, 'Aref Ruqaa', 700],
        ['ع', 330, 500, 120, 'Amiri', 700],
        ['ن', 560, 210, 170, 'Gulzar', 400],
        ['ك', 800, 470, 130, 'Reem Kufi', 600],
        ['قلم', 1050, 170, 110, 'Amiri', 700],
        ['ل', 230, 330, 190, 'Reem Kufi', 600],
        ['م', 700, 330, 110, 'Aref Ruqaa', 700],
        ['س', 960, 340, 130, 'Gulzar', 400],
        ['حبر', 420, 100, 100, 'Aref Ruqaa', 700],
        ['خط', 1110, 560, 140, 'Aref Ruqaa', 700],
        ['ا', 40, 560, 160, 'Amiri', 700],
        ['ي', 500, 590, 130, 'Amiri', 400],
        ['ق', 160, 50, 120, 'Gulzar', 400],
        ['و', 1170, 330, 120, 'Amiri', 400],
    ] as $i => [$letter, $x, $y, $size, $font, $weight])
        <text x="{{ $x }}" y="{{ $y }}" style="font-family:'{{ $font }}',serif;font-weight:{{ $weight }};font-size:{{ $size }}px;animation-delay:-{{ ($i * 1.9) % 13 }}s">{{ $letter }}</text>
    @endforeach

    @foreach ([
        ['fatha', 180, 200, .7, -8],
        ['damma', 640, 60, .8, 0],
        ['shadda', 880, 220, .9, -4],
        ['kasra', 300, 560, .6, 6],
        ['sukun', 760, 540, .55, 0],
        ['tanween', 1000, 420, .7, -10],
        ['fatha', 520, 380, .55, 12],
        ['shadda', 60, 330, .6, 8],
        ['damma', 1140, 60, .6, 0],
        ['sukun', 420, 260, .45, 0],
    ] as $j => [$mark, $x, $y, $scale, $turn])
        <path class="haraka haraka-{{ $mark }}" pathLength="1" d="{{ $marks[$mark] }}"
              transform="translate({{ $x }} {{ $y }}) rotate({{ $turn }}) scale({{ $scale }})"
              style="animation-delay:-{{ ($j * 2.3 + 1) % 13 }}s"/>
    @endforeach
</svg>
