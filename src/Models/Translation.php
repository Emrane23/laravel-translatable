<?php

namespace Emrane23\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = [
        'table_name',
        'foreign_key',
        'column_name',
        'locale',
        'value',
    ];

    public function translatable()
    {
        return $this->morphTo('translatable', 'table_name', 'foreign_key');
    }
}