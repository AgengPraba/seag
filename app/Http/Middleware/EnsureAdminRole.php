<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user();

        if (! $admin || ! $admin->isActive()) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Akun administrator Anda tidak aktif atau sesi telah berakhir.']);
        }

        if (in_array($admin->role, $roles, true) || $admin->isSuperAdmin()) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki hak akses (Role) yang diperlukan untuk mengakses modul ini.');
    }
}

