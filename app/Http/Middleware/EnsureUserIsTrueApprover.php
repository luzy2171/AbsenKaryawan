<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTrueApprover
{
    /**
     * Handle an incoming request.
     * Mengizinkan user dengan role 'approval' atau 'superadmin' untuk melanjutkan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
