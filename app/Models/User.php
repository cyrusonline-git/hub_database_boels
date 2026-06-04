<?php

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'employee_id',
        'is_super_admin', 'active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function applications()
    {
        if ($this->is_super_admin) {
            return Application::query()->where('active', true)->orderBy('sort_order');
        }

        return Application::query()
            ->where('active', true)
            ->whereIn('id', function ($q) {
                $q->select('application_id')
                    ->from('permissions')
                    ->whereIn('id', function ($q2) {
                        $q2->select('permission_id')
                            ->from('role_permissions')
                            ->whereIn('role_id', $this->roles()->select('roles.id'));
                    })
                    ->whereNotNull('application_id');
            })
            ->orderBy('sort_order');
    }
}
