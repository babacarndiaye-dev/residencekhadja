<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuOption extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuOptionGroup::class, 'menu_option_group_id');
    }
}
