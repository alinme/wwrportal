<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        // If we want to strictly enforce scope, we might need a custom guard or middleware
        // For now, we rely on the route check and user role

        Auth::login($user);

        return redirect()->route('school.dashboard', $school);
    }
}
