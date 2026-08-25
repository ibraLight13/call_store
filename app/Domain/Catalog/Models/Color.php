<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

final class Color extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'hex', 'name_ar', 'name_en', 'sort'];
}
