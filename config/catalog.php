<?php

return [

    'currency' => 'SAR',

    // Fonts an admin can pick for a script style (all loaded in layouts/app.blade.php).
    'fonts' => [
        'amiri' => "'Amiri', serif",
        'aref-ruqaa' => "'Aref Ruqaa', serif",
        'reem-kufi' => "'Reem Kufi', sans-serif",
        'plex-arabic' => "'IBM Plex Sans Arabic', sans-serif",
    ],

    // Script slug => CSS font stack used to render piece text as art.
    'script_fonts' => [
        'thuluth' => "'Amiri', serif",
        'diwani' => "'Aref Ruqaa', serif",
        'naskh' => "'Amiri', serif",
        'kufic' => "'Reem Kufi', sans-serif",
        'ruqah' => "'Aref Ruqaa', serif",
        'nastaliq' => "'Aref Ruqaa', serif",
    ],
    'script_weights' => [
        'thuluth' => 700,
        'diwani' => 700,
        'naskh' => 400,
        'kufic' => 600,
        'ruqah' => 400,
        'nastaliq' => 400,
    ],

    // Paper tints: [background, ink] pairs cycled through the pin grid.
    'paper_tints' => [
        ['#fdf6e9', '#7a2f1e'],
        ['#e7f0ec', '#0f5c4f'],
        ['#fbeae6', '#8a2e22'],
        ['#e8eef7', '#22406b'],
        ['#23201b', '#f0e6d2'],
        ['#f2ece0', '#4a3a1e'],
        ['#efeaf6', '#46356e'],
        ['#e6f4ee', '#14664f'],
        ['#f7edd6', '#7a5a12'],
        ['#1c2b2a', '#cfe6df'],
        ['#eef1ea', '#33402a'],
        ['#fceee4', '#9a4a1e'],
    ],
];
