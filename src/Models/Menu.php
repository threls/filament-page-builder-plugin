<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'description',
        'location',
        'status',
        'max_depth',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function allMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

}
