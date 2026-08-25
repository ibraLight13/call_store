<?php

use App\Support\ArabicNormalizer;

it('strips diacritics and tatweel', function () {
    expect(ArabicNormalizer::normalize('وَقُل رَّبِّ زِدْنِي عِلْمًا'))
        ->toBe('وقل رب زدني علما');
});

it('unifies alef and yeh variants', function () {
    expect(ArabicNormalizer::normalize('أَحْمَد إِبْرَاهِيمَ آل'))
        ->toBe('احمد ابراهيم ال');

    expect(ArabicNormalizer::normalize('عليّ ٱلعظيمـــة'))
        ->toBe('علي العظيمه');
});

it('converts arabic-indic digits', function () {
    expect(ArabicNormalizer::normalize('٥٠ × ٧٠'))
        ->toBe('50 × 70');
});

it('collapses whitespace', function () {
    expect(ArabicNormalizer::normalize("  الحمدُ   لله\t\n "))
        ->toBe('الحمد لله');
});
