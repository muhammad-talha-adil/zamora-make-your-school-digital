<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemePalette extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'mode', 'is_premium', 'is_active'];

    public function colors(): HasMany
    {
        return $this->hasMany(ThemePaletteColor::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ThemeSetting::class, 'selected_palette_id');
    }
}
