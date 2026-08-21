<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PinAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredRole = null): Response
    {
        $role = session('auth_role');

        if (!$role) {
            return redirect()->route('admin.login')->with('error', 'Sila masukkan Kod Akses untuk masuk.');
        }

        if ($requiredRole === 'master' && $role !== 'master') {
            abort(403, 'Anda tidak mempunyai akses Master untuk melihat halaman ini.');
        }

        return $next($request);
    }
}
