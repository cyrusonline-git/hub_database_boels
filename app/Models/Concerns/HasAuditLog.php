<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        if (! config('boels.audit.enabled', true)) {
            return;
        }

        static::created(fn ($model) => $model->writeAuditLog('created', null, $model->getAttributes()));
        static::updated(fn ($model) => $model->writeAuditLog('updated', $model->getOriginal(), $model->getChanges()));
        static::deleted(fn ($model) => $model->writeAuditLog('deleted', $model->getOriginal(), null));
    }

    public function writeAuditLog(string $event, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_id' => $this->getKey(),
            'auditable_type' => static::class,
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
        ]);
    }
}
