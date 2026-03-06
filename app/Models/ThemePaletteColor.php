<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemePaletteColor extends Model
{
    use SoftDeletes;

    protected $fillable = ['theme_palette_id', 'slot', 'hex', 'label'];

    public function palette(): BelongsTo
    {
        return $this->belongsTo(ThemePalette::class, 'theme_palette_id');
    }
}
