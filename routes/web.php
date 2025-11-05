<?php

use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\MushraResultsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/mushra');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Microsoft OAuth routes
Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');

// webMUSHRA routes - protected by auth middleware
Route::middleware('auth')->group(function () {
    // Redirect /mushra to the index.html file
    Route::get('/mushra', function () {
        return redirect('/mushra/index.html');
    })->name('mushra');

    // Results submission endpoint
    Route::post('/api/mushra/results', [MushraResultsController::class, 'store'])->name('mushra.results.store');
});

require __DIR__.'/auth.php';
