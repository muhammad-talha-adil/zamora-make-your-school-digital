<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title',
        'icon',
        'url',
        'is_active',
        'parent_id',
        'order',
        'type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getHrefAttribute()
    {
        // Prioritize explicit URL over auto-generated path
        return $this->url ?: $this->path;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($menu) {
            $menu->generatePath();
        });

        static::updating(function ($menu) {
            if ($menu->isDirty('title') || $menu->isDirty('parent_id')) {
                $menu->generatePath();
            }
        });
    }

    protected function generatePath()
    {
        $slug = strtolower(str_replace(' ', '-', $this->title));
    
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);
            if ($parent) {
                $this->path = $parent->path . '/' . $slug;
            } else {
                $this->path = '/' . $slug;
            }
        } else {
            $this->path = '/' . $slug;
        }
    }
}