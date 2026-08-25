<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Handige links op het dashboard: rekentools (bv. generator-tool),
 * documenten, externe sites. Beheerd via Beheer > Handige links,
 * zichtbaar voor alle ingelogde medewerkers.
 */
class QuickLink extends Model
{
    protected $fillable = [
        'title', 'url', 'icon', 'category', 'description', 'sort_order', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
