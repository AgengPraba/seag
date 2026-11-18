<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Administrator extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAccessAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'access_admin'], true);
    }

    public function isSecurityAuditor(): bool
    {
        return in_array($this->role, ['super_admin', 'security_auditor'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
