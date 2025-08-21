<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
            'status' => 'string',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }
}
