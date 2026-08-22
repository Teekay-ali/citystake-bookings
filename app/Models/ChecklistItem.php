<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single line in the inspection template (e.g. "Main door & lock"). Items are
 * stored in the DB rather than code so the checklist can be revised without a
 * deploy; each answer snapshots the label, so revisions never rewrite history.
 */
class ChecklistItem extends Model
{
    protected $fillable = [
        'key', 'category', 'issue_category', 'section', 'scope', 'label',
        'sort_order', 'repeats_per_bedroom', 'requires_photo_on_fail', 'active',
    ];

    protected $casts = [
        'repeats_per_bedroom'    => 'boolean',
        'requires_photo_on_fail' => 'boolean',
        'active'                 => 'boolean',
        'sort_order'             => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /** Active items in display order, optionally limited to one category. */
    public static function template(?string $category = null)
    {
        return static::active()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();
    }
}
