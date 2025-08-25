<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blueprint_id
 * @property int $version
 * @property array $schema
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Blueprint|null $blueprint
 */
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
