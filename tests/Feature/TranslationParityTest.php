<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

it('has identical translation keys across ar and en for every file', function () {
    $base = lang_path();

    foreach (File::files($base.DIRECTORY_SEPARATOR.'ar') as $arFile) {
        $name = $arFile->getFilename();
        $enPath = $base.DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.$name;

        expect(file_exists($enPath))->toBeTrue("Missing en/{$name}");

        $ar = require $arFile->getPathname();
        $en = require $enPath;

        expect(is_array($ar) && is_array($en))->toBeTrue("{$name} must return arrays");

        $arKeys = collect(Arr::dot($ar))->keys()->sort()->values()->all();
        $enKeys = collect(Arr::dot($en))->keys()->sort()->values()->all();

        $missingInEn = array_diff($arKeys, $enKeys);
        $missingInAr = array_diff($enKeys, $arKeys);

        expect($missingInEn)->toBe([], "Keys missing in en/{$name}: ".implode(', ', $missingInEn));
        expect($missingInAr)->toBe([], "Keys missing in ar/{$name}: ".implode(', ', $missingInAr));
    }
});
