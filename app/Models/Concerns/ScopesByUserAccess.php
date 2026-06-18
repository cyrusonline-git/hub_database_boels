<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Voor child-apps (Fleet, Sales, Schade, ...): scope queries automatisch
 * op de allowed_areas / allowed_depots / allowed_countries van de ingelogde user.
 *
 * Gebruik op een Model:
 *
 *   class Machine extends Model
 *   {
 *       use ScopesByUserAccess;
 *
 *       // welke kolommen op DEZE tabel mappen op welke user-permissie-array
 *       protected array $userScopeColumns = [
 *           'area' => 'allowed_areas',
 *           'depot' => 'allowed_depots',
 *           'country' => 'allowed_countries',
 *       ];
 *
 *       // permissie die de scope bypasst (bv. "fleet.global")
 *       protected ?string $userScopeBypassPermission = 'fleet.global';
 *   }
 *
 * Effect: Machine::all() in een Fleet App geeft automatisch alleen
 * machines die overlappen met user.allowed_areas etc.
 *
 * Super admin + users met bypassPermission krijgen alle records.
 * Niet-ingelogde gebruikers (CLI / scheduler) krijgen ook alles
 * (zodat seeders en cron-jobs niet stuk gaan).
 */
trait ScopesByUserAccess
{
    public static function bootScopesByUserAccess(): void
    {
        static::addGlobalScope(new class implements Scope {
            public function apply(Builder $builder, Model $model): void
            {
                $user = Auth::user();
                if (! $user) {
                    return; // geen request-context, geen scope
                }
                if (method_exists($user, 'getAttribute') && $user->is_super_admin) {
                    return;
                }

                $bypass = $model->getUserScopeBypassPermission();
                if ($bypass && method_exists($user, 'hasPermission') && $user->hasPermission($bypass)) {
                    return;
                }

                $table = $model->getTable();
                $cols = $model->getUserScopeColumns();

                foreach ($cols as $column => $userField) {
                    $allowed = $user->{$userField} ?? null;
                    if (empty($allowed)) {
                        // user heeft niets toegestaan op deze dimensie -> niets tonen
                        $builder->whereRaw('1 = 0');
                        return;
                    }
                    $builder->whereIn("$table.$column", $allowed);
                }
            }
        });
    }

    public function getUserScopeColumns(): array
    {
        return $this->userScopeColumns ?? [];
    }

    public function getUserScopeBypassPermission(): ?string
    {
        return $this->userScopeBypassPermission ?? null;
    }

    /**
     * Tijdelijk zonder scope querien (bv. voor admin-overzichten).
     *
     *   Machine::withoutUserScope()->where(...)->get();
     */
    public function scopeWithoutUserScope(Builder $q): Builder
    {
        return $q->withoutGlobalScopes([self::class]);
    }
}
