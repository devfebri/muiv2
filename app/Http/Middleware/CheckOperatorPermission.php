<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOperatorPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isOperator() && $user->hasMenuPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akses ditolak: Tugas atau hak akses menu ini tidak diberikan ke akun Anda.',
            ], 403);
        }

        abort(403, 'Akses ditolak: Tugas atau hak akses menu ini tidak diberikan oleh Admin ke akun Anda.');
    }
}
