<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/campaigns', App\Livewire\Campaigns::class)->name('campaigns');
    Route::get('/schools', App\Livewire\Schools::class)->name('schools');
    Route::get('/schools/{school}/structures', App\Livewire\SchoolStructureManager::class)->name('schools.structures');
    Route::get('/settings/foundation', App\Livewire\Settings\FoundationSettings::class)->name('settings.foundation');
});

// School Portal
Route::get('/access/{school}/{token}', [App\Http\Controllers\MagicLinkController::class, 'login'])->name('school.access');

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureSchoolAccess::class])->prefix('portal/{school}')->as('school.')->group(function () {
    Route::get('/', App\Livewire\School\SchoolDashboard::class)->name('dashboard');
    Route::get('/structure/{structure}', App\Livewire\School\GroupManager::class)->name('structure');
    
    Route::get('/docs/contract', [App\Http\Controllers\SchoolDocsController::class, 'downloadContract'])->name('docs.contract');
    Route::get('/docs/annex', [App\Http\Controllers\SchoolDocsController::class, 'downloadAnnex'])->name('docs.annex');
    Route::get('/docs/gdpr', [App\Http\Controllers\SchoolDocsController::class, 'downloadGdpr'])->name('docs.gdpr');
    Route::get('/group/{group}/distribution-table', [App\Http\Controllers\SchoolDocsController::class, 'downloadGroupDistributionTable'])->name('docs.group.distribution');
});

require __DIR__.'/settings.php';
