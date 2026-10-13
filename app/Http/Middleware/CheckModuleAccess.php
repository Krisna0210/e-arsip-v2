<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (Auth::check()) {
            if (!Auth::user()->hasModuleAccess($module)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akses Ditolak: Anda mencoba mengakses halaman tanpa otorisasi yang sah. Sesi Anda telah dihentikan demi keamanan.'
                ]);
            }
        }

        return $next($request);
    }
}
