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

it('has no BOM or double-encoded (mojibake) text in any lang file', function () {
    // "â€" is how ’ “ ” … — look after a UTF-8 → cp1252 → UTF-8 round trip;
    // "Ø" / "Ù" are the lead characters of double-encoded Arabic.
    $markers = ["\u{00E2}\u{20AC}", "\u{00C3}", "\u{00D8}", "\u{00D9}"];

    foreach (File::allFiles(lang_path()) as $file) {
        $raw = file_get_contents($file->getPathname());
        $name = $file->getRelativePathname();

        expect(str_starts_with($raw, "\xEF\xBB\xBF"))->toBeFalse("BOM in {$name}");

        foreach ($markers as $marker) {
            expect(str_contains($raw, $marker))->toBeFalse("Mojibake in {$name}");
        }
    }
});
