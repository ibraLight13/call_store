<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Taxonomy extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'sort'];
}
