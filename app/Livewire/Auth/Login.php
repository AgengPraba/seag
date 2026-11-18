<?php

namespace App\Livewire\Auth;

use App\Models\Administrator;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        $admin = Administrator::where('email', $this->email)->first();

        if (! $admin || ! $admin->isActive()) {
            AuditLogService::log(
                eventType: 'admin.login',
                actorType: 'system',
                actorId: $this->email,
                eventStatus: 'denied',
                description: 'Percobaan login administrator gagal: akun tidak ditemukan atau tidak aktif.',
                metadata: ['email' => $this->email]
            );

            throw ValidationException::withMessages([
                'email' => 'Kredensial tidak valid atau akun dinonaktifkan.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            AuditLogService::log(
                eventType: 'admin.login',
                actorType: 'system',
                actorId: (string) $admin->id,
                targetUserId: (string) $admin->id,
                eventStatus: 'failed',
                description: 'Percobaan login administrator gagal: password salah.',
                metadata: ['email' => $this->email]
            );

            throw ValidationException::withMessages([
                'email' => 'Kredensial tidak valid atau password salah.',
            ]);
        }

        session()->regenerate();

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        AuditLogService::log(
            eventType: 'admin.login',
            actorType: 'administrator',
            actorId: (string) $admin->id,
            targetUserId: (string) $admin->id,
            eventStatus: 'success',
            description: "Administrator {$admin->name} ({$admin->role}) berhasil login.",
            metadata: ['email' => $admin->email]
        );

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('components.layouts.app', ['title' => 'Login Administrator']);
    }
}

