<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAccess
{
    /**
     * Ensure school_manager users can only access their assigned school's portal.
     * Admins can access when impersonating (session impersonate_school_id).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');

        if (! $school) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('home');
        }

        if ($user->hasRole('admin')) {
            $impersonateId = session('impersonate_school_id');
            if ($impersonateId && (string) $school->id === (string) $impersonateId) {
                return $next($request);
            }
            session()->forget('impersonate_school_id');

            return redirect()->route('dashboard');
        }

        if ($user->hasRole('school_manager') && $user->school_id !== $school->id) {
            abort(403, __('You do not have access to this school portal.'));
        }

        return $next($request);
    }
}
