<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSetting extends Model
{
    use SoftDeletes;

    protected $fillable = ['mode', 'selected_palette_id', 'colors_json', 'updated_by'];

    protected $casts = [
        'colors_json' => 'array',
    ];

    public function selectedPalette(): BelongsTo
    {
        return $this->belongsTo(ThemePalette::class, 'selected_palette_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
