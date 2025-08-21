<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AvatarController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Avatar routes
    Route::post('/profile/avatar', [AvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    
    // Application routes
    Route::resource('applications', ApplicationController::class);
    Route::post('/applications/{application}/mark-applied', [ApplicationController::class, 'markAsApplied'])->name('applications.mark-applied');
    Route::post('/applications/{application}/regenerate', [ApplicationController::class, 'regenerate'])->name('applications.regenerate');
    Route::get('/applications/{application}/export/{format}', [ApplicationController::class, 'export'])->name('applications.export');
    Route::get('/applications/{application}/download-resume', [ApplicationController::class, 'downloadResume'])->name('applications.download-resume');
});

require __DIR__.'/auth.php';
