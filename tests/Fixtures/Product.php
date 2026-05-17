<?php

namespace Emrane23\Translatable\Tests\Fixtures;

use Emrane23\Translatable\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use Translatable;

    protected $table = 'products';

    protected $fillable = ['name', 'description', 'price'];

    protected $translatable = ['name', 'description'];
}