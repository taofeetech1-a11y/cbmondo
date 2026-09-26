<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::controller(AppController::class)->group(function () {

    Route::get('/', 'index')->name('home');
    Route::get('/support', 'supportPage')->name('support.page');
    Route::get('/location/wards/{lga}', 'wards')->name('ward');
    Route::get('/location/pollingunits/{ward}', 'pollingUnit')->name('polling.unit');
    Route::post('/register/submit', 'ProcessSubmition')->name('register.submit');
    Route::post('/support/register', 'processSupport')->name('register.support');
    Route::get('/event', 'EventPage')->name('event.page');
    Route::get('/event/passcard/{pass_id}', 'passcard')->name('event.passcard');

});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
