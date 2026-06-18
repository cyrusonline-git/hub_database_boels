<?php

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending_activation';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'name', 'email', 'password', 'employee_id',
        'is_super_admin', 'active', 'last_login_at',
        'allowed_areas', 'allowed_depots', 'allowed_countries',
        'status', 'activation_token', 'activation_token_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'activation_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'active' => 'boolean',
            'allowed_areas' => 'array',
            'allowed_depots' => 'array',
            'allowed_countries' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Heeft de user (1) een permission op een app
     * EN (2) overlap met de restricties van die app op area/depot/country
     * OF de {slug}.global permission die alles bypasses.
     */
    public function applications()
    {
        $query = Application::query()->where('active', true);

        if (! $this->is_super_admin) {
            // Filter op apps waarvoor user permissies heeft
            $query->whereIn('id', function ($q) {
                $q->select('application_id')
                    ->from('permissions')
                    ->whereIn('id', function ($q2) {
                        $q2->select('permission_id')
                            ->from('role_permissions')
                            ->whereIn('role_id', $this->roles()->select('roles.id'));
                    })
                    ->whereNotNull('application_id');
            });
        }

        $apps = $query->orderBy('sort_order')->get();

        // Post-query area/depot/country filter — eenvoudiger dan complex SQL omdat
        // JSON-overlap in MySQL/Maria niet altijd portable is.
        return $apps->filter(function (Application $app) {
            if ($this->is_super_admin) {
                return true;
            }
            // Override via {slug}.global permissie
            if ($this->hasPermission($app->slug . '.global')) {
                return true;
            }
            return $this->matchesAppRestrictions($app);
        })->values();
    }

    public function matchesAppRestrictions(Application $app): bool
    {
        return $this->overlaps($app->restricted_to_areas, $this->allowed_areas)
            && $this->overlaps($app->restricted_to_depots, $this->allowed_depots)
            && $this->overlaps($app->restricted_to_countries, $this->allowed_countries);
    }

    /**
     * Lege app-restrictie = iedereen mag.
     * Niet-lege restrictie = user moet overlap hebben in zijn allowed-lijst.
     */
    private function overlaps(?array $appRestriction, ?array $userAllowed): bool
    {
        if (empty($appRestriction)) {
            return true;
        }
        if (empty($userAllowed)) {
            return false;
        }
        return count(array_intersect($appRestriction, $userAllowed)) > 0;
    }
}
