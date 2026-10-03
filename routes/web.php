<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\ExcoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::controller(AppController::class)->group(function () {

    // Route::get('/', 'index')->name('home');
    Route::get('/support', 'supportPage')->name('support.page');
    Route::get('/location/wards/{lga}', 'wards')->name('ward');
    Route::get('/location/pollingunits/{ward}', 'pollingUnit')->name('polling.unit');
    Route::post('/register/submit', 'ProcessSubmition')->name('register.submit');
    Route::post('/support/register', 'processSupport')->name('register.support');
    Route::get('/event', 'EventPage')->name('event.page');
    Route::get('/event/passcard/{pass_id}', 'passcard')->name('event.passcard');

});

Route::controller(HomeController::class)->group(function () {

    Route::get('/', 'index')->name('new.homepage');
    Route::get('/new/wards/{lga}', 'wards');
    Route::get('/new/pollingunits/{ward}', 'pollingUnit');

    Route::post('/cbm/registration', 'cbmRegister')->name('cbm.register');
    Route::post('/nc/registration', 'noCardRegister')->name('nc.register');
});

Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
Route::resource('excos', ExcoController::class)->only(['index', 'create', 'store', 'destroy']);
Route::view('/about', 'about')->name('about.page');
Route::view('/contact', 'contact')->name('contact.page');
Route::view('/updates', 'updates')->name('updates.page');
Route::get('/membership/import', [MembershipImportController::class, 'index'])->name('membership.import');
Route::post('/membership/import/preview', [MembershipImportController::class, 'preview'])->name('membership.import.preview');
Route::post('/membership/import/confirm', [MembershipImportController::class, 'store'])->name('membership.import.store');
Route::get('/membership/import/template/{format}', [MembershipImportController::class, 'template'])->name('membership.import.template');
Route::get('/membership/import/locations', [MembershipImportController::class, 'locations'])->name('membership.import.locations');
Route::post('/membership/cards/download', [MembershipController::class, 'bulkCards'])->name('membership.cards.download');
Route::get('/membership/export/{type}', [MembershipController::class, 'export'])->name('membership.export');
Route::get('/membership/{membership}/edit', [MembershipController::class, 'edit'])->whereNumber('membership')->name('membership.edit');
Route::get('/membership/{membership}/card', [MembershipController::class, 'card'])->whereNumber('membership')->name('membership.card');
Route::get('/membership/{membership}', [MembershipController::class, 'show'])->whereNumber('membership')->name('membership.show');
Route::patch('/membership/{membership}', [MembershipController::class, 'update'])->whereNumber('membership')->name('membership.update');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
