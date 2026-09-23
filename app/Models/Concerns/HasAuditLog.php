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
        // Geheimen nooit in de audit-log; en pure logins (last_login_at) niet als wijziging loggen
        $verborgen = array_merge(method_exists($this, 'getHidden') ? $this->getHidden() : [], ['password', 'remember_token', 'activation_token']);
        $old = $old !== null ? array_diff_key($old, array_flip($verborgen)) : null;
        $new = $new !== null ? array_diff_key($new, array_flip($verborgen)) : null;
        if ($event === 'updated' && $new !== null && array_diff(array_keys($new), ['last_login_at', 'updated_at']) === []) {
            return;
        }
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
