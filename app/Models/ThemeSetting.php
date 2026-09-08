<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeSetting extends Model
{
    use SoftDeletes;

    protected $fillable = ['mode', 'selected_palette_id', 'colors_json', 'updated_by'];

    protected $casts = [
        'colors_json' => 'array',
    ];

    /**
     * Palette colours keyed by slot (card_bg, sidebar_text, ...).
     *
     * Views read `$theme->colors`, which has no backing column; without this
     * accessor every lookup resolved to null and the saved palette was ignored.
     *
     * @return array<string, string>
     */
    public function getColorsAttribute(): array
    {
        return $this->colors_json ?? [];
    }

    public function selectedPalette(): BelongsTo
    {
        return $this->belongsTo(ThemePalette::class, 'selected_palette_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
