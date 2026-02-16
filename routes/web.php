<?php

use Illuminate\Support\Facades\Route;

Route::get('/list-template', function () {
    return view('list-template');
})->name('list-template');

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Admin-only: dashboard, campaigns, schools, settings. Magic-link users (school_manager) are redirected to their portal.
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/campaigns', App\Livewire\Campaigns::class)->name('campaigns');
    Route::get('/campaigns/{campaign}/schools', App\Livewire\CampaignSchools::class)->name('campaigns.schools');
    Route::get('/campaigns/{campaign}/schools/{school}/structures', App\Livewire\SchoolStructureManager::class)->name('campaigns.schools.structures');
    Route::get('/schools', App\Livewire\Schools::class)->name('schools');
    Route::get('/schools/{school}/structures', App\Livewire\SchoolStructureManager::class)->name('schools.structures');
    Route::get('/schools/{school}/docs/proces-verbal-retur', [App\Http\Controllers\SchoolDocsController::class, 'downloadProcesVerbalRetur'])->name('schools.docs.proces-verbal-retur');
    Route::get('/schools/{school}/docs/proces-verbal-primire-din-retur', [App\Http\Controllers\SchoolDocsController::class, 'downloadProcesVerbalPrimireDinRetur'])->name('schools.docs.proces-verbal-primire-din-retur');
    Route::get('/uploads', App\Livewire\Uploads::class)->name('uploads');
    Route::get('/contacts', App\Livewire\Contacts::class)->name('contacts');
    Route::get('/settings/foundation', App\Livewire\Settings\FoundationSettings::class)->name('settings.foundation');
});

// School Portal – magic link (rate limited to 10 attempts per minute per IP to prevent token brute-force)
Route::get('/access/{school}/{token}', [App\Http\Controllers\MagicLinkController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('school.access');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/exit-impersonation', function () {
        session()->forget('impersonate_school_id');

        return redirect()->route('dashboard');
    })->name('exit-impersonation');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureSchoolAccess::class])->prefix('portal/{school}')->as('school.')->group(function () {
    Route::get('/', App\Livewire\School\SchoolDashboard::class)->name('dashboard');
    Route::get('/structure/{structure}', App\Livewire\School\GroupManager::class)->name('structure');

    Route::get('/docs/contract', [App\Http\Controllers\SchoolDocsController::class, 'downloadContract'])->name('docs.contract');
    Route::get('/docs/annex', [App\Http\Controllers\SchoolDocsController::class, 'downloadAnnex'])->name('docs.annex');
    Route::get('/docs/gdpr', [App\Http\Controllers\SchoolDocsController::class, 'downloadGdpr'])->name('docs.gdpr');
    Route::get('/docs/gdpr/child/{child}', [App\Http\Controllers\SchoolDocsController::class, 'downloadGdprChild'])->name('docs.gdpr.child');
    Route::get('/docs/gdpr/child/{child}/test', [App\Http\Controllers\SchoolDocsController::class, 'downloadGdprChildTest'])->name('docs.gdpr.child.test');
    Route::get('/group/{group}/distribution-table', [App\Http\Controllers\SchoolDocsController::class, 'downloadGroupDistributionTable'])->name('docs.group.distribution');
});

require __DIR__.'/settings.php';
