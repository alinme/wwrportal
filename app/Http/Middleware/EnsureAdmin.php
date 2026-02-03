<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Restrict dashboard, campaigns, schools, and settings to admin users only.
     * Users who logged in via magic link (school_manager) are redirected to their school portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($user->hasRole('school_manager')) {
            $school = $user->school;
            if (! $school) {
                abort(403, __('You do not have access to any school portal.'));
            }

            return redirect()->route('school.dashboard', $school);
        }

        abort(403, __('You do not have access to the admin area.'));
    }
}
