<?php

namespace App\Domain\Catalog\Models;

/**
 * @property string|null $font
 * @property int|null $weight
 * @property bool $show_on_home
 */
final class Script extends Taxonomy
{
    protected $fillable = ['slug', 'name_ar', 'name_en', 'sort', 'font', 'weight', 'show_on_home'];

    protected function casts(): array
    {
        return ['weight' => 'integer', 'show_on_home' => 'boolean'];
    }
}
