<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blueprint extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'working_schema' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BlueprintVersion::class);
    }

    public function publishedVersions(): HasMany
    {
        return $this->versions()->where('status', 'published');
    }

    /**
     * Snapshot the current working_schema into blueprint_versions as a new published version.
     *
     * Returns the created version number, or null if nothing was published (e.g., empty schema).
     */
    public function publishNewVersion(): ?int
    {
        $schema = $this->working_schema ?? [];
        if (! is_array($schema) || empty($schema)) {
            return null;
        }

        $next = (int) (($this->versions()->max('version')) ?? 0) + 1;

        $this->versions()->create([
            'version' => $next,
            'schema' => $schema,
            'status' => 'published',
            'published_at' => now(),
        ]);

        if ($this->status !== 'published') {
            $this->forceFill(['status' => 'published'])->save();
        }

        return $next;
    }
}
