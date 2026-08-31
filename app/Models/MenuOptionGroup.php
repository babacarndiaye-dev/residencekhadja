<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuOptionGroup extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(MenuOption::class)->orderBy('sort_order');
    }
}
