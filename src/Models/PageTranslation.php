<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['content'];

    protected function casts(): array
    {
        return [
            'content' => 'array'
        ];
    }
}
