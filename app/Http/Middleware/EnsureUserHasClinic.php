<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasClinic
{
    /**
     * Ensure the authenticated user belongs to the active clinic.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            abort(403, 'Su cuenta está desactivada. Contacte al administrador del consultorio.');
        }

        if ($user->clinic_id === null || $user->clinic === null || ! $user->clinic->is_active) {
            abort(403, 'Su usuario no tiene un consultorio activo asignado.');
        }

        return $next($request);
    }
}
