<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MagicLinkController extends Controller
{
    public function login(School $school, string $token)
    {
        if ($school->access_token !== $token) {
            abort(403, 'Invalid access token.');
        }

        // Admin clicking the link: impersonate instead of logging in as educator
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            session(['impersonate_school_id' => $school->id]);

            return redirect()->route('school.dashboard', $school);
        }

        $email = "school_{$school->id}@portal.local";

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $school->official_name,
                'password' => bcrypt(Str::random(32)),
                'role' => 'school_manager',
                'school_id' => $school->id,
            ]
        );

        if (! $user->school_id) {
            $user->update(['school_id' => $school->id]);
        }

        if (! $user->hasRole('school_manager')) {
            $user->assignRole('school_manager');
        }

        Auth::login($user);

        return redirect()->route('school.dashboard', $school);
    }
}
