<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificatie-teller per (app, gebruiker) — het rode bolletje op de
 * dashboard-tegel. De child-app is de bron van de waarheid: die bepaalt
 * wat "nieuw" is en meldt het absolute aantal via POST /api/badge.
 */
class AppBadge extends Model
{
    protected $fillable = ['application_id', 'user_id', 'count'];

    protected $casts = ['count' => 'integer'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
