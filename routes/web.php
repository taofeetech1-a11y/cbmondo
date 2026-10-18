<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\CoordinatorAgentController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\CoordinatorProfileController;
use App\Http\Controllers\CoordinatorSessionController;
use App\Http\Controllers\ExcoController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ExecutivePositionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\VoterImportController;
use App\Http\Middleware\EnsureAccountAccess;
use App\Http\Middleware\PrivateDashboard;
use App\Http\Middleware\ProtectVoterPages;
use Illuminate\Support\Facades\Route;

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

Route::view('/about', 'about')->name('about.page');
Route::view('/contact', 'contact')->name('contact.page');
Route::view('/updates', 'updates')->name('updates.page');

Route::middleware([PrivateDashboard::class, 'auth:web', EnsureAccountAccess::class, 'auth.session', 'can:access-dashboard'])->group(function () {
    Route::patch('/membership/{membership}/coordinator-agent', [CoordinatorAgentController::class, 'update'])->whereNumber('membership')->middleware('can:manage-users')->name('coordinator-agents.update');
    Route::post('/membership/{membership}/coordinator-agent', [CoordinatorAgentController::class, 'store'])->whereNumber('membership')->middleware('can:manage-users')->name('coordinator-agents.store');
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
    Route::resource('excos', ExcoController::class)->only(['index', 'create', 'store', 'destroy'])->middleware('can:manage-appointments');
    Route::resource('executives', ExecutiveController::class)->only(['index', 'create', 'store', 'destroy'])->middleware('can:manage-appointments');
    Route::resource('executive-positions', ExecutivePositionController::class)->parameters(['executive-positions' => 'executivePosition'])->only(['index', 'store', 'update', 'destroy'])->middleware('can:manage-positions');
    Route::get('/membership/import', [MembershipImportController::class, 'index'])->name('membership.import')->middleware('can:import-members');
    Route::post('/membership/import/preview', [MembershipImportController::class, 'preview'])->name('membership.import.preview')->middleware('can:import-members');
    Route::post('/membership/import/confirm', [MembershipImportController::class, 'store'])->name('membership.import.store')->middleware('can:import-members');
    Route::get('/membership/import/template/{format}', [MembershipImportController::class, 'template'])->name('membership.import.template')->middleware('can:import-members');
    Route::get('/membership/import/locations', [MembershipImportController::class, 'locations'])->name('membership.import.locations')->middleware('can:import-members');
    Route::post('/membership/cards/download', [MembershipController::class, 'bulkCards'])->name('membership.cards.download')->middleware('can:download-cards');
    Route::get('/membership/export/{type}', [MembershipController::class, 'export'])->name('membership.export')->middleware('can:export-data');
    Route::get('/membership/{membership}/edit', [MembershipController::class, 'edit'])->whereNumber('membership')->name('membership.edit')->middleware('can:update-members');
    Route::get('/membership/{membership}/card', [MembershipController::class, 'card'])->whereNumber('membership')->name('membership.card')->middleware('can:download-cards');
    Route::get('/membership/{membership}', [MembershipController::class, 'show'])->whereNumber('membership')->name('membership.show');
    Route::patch('/membership/{membership}', [MembershipController::class, 'update'])->whereNumber('membership')->name('membership.update')->middleware('can:update-members');

    Route::get('/dashboard', fn () => redirect()->route('membership.index'))->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:5,1')->name('profile.update');
        Route::get('/profile/email/confirm/{user}', [ProfileController::class, 'confirmEmail'])->middleware(['signed', 'throttle:6,1'])->name('profile.email.confirm');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::resource('staff-users', StaffUserController::class)->parameters(['staff-users' => 'staffUser'])->only(['index', 'store', 'update'])->middleware('can:manage-users');
});

require __DIR__.'/auth.php';

Route::prefix('coordinator')->name('coordinator.')->middleware(PrivateDashboard::class)->group(function () {
    Route::middleware('guest:coordinator')->group(function () {
        Route::get('/login', [CoordinatorSessionController::class, 'create'])->name('login');
        Route::post('/login', [CoordinatorSessionController::class, 'store'])->middleware('throttle:30,1');
    });
    Route::middleware(['auth:coordinator', EnsureAccountAccess::class, 'auth.session'])->group(function () {
        Route::get('/dashboard', [VoterImportController::class, 'index'])->middleware(['can:view-voters', ProtectVoterPages::class])->name('dashboard');
        Route::get('/register', [CoordinatorDashboardController::class, 'register'])->name('register');
        Route::get('/profile', [CoordinatorProfileController::class, 'edit'])->name('profile');
        Route::put('/password', [CoordinatorProfileController::class, 'password'])->middleware('throttle:5,1')->name('password');
        Route::post('/photo', [CoordinatorProfileController::class, 'updatePhoto'])->name('photo.update');
        Route::get('/photo', [CoordinatorProfileController::class, 'photo'])->name('photo');
        Route::post('/logout', [CoordinatorSessionController::class, 'destroy'])->name('logout');
    });
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');

Route::middleware(['auth:web,coordinator', EnsureAccountAccess::class, 'auth.session', 'can:view-voters'])->group(function () {
    Route::get('/voters', [VoterImportController::class, 'index'])->middleware(ProtectVoterPages::class)->name('voters.index');
    Route::get('/voters/{voter}/photo', [VoterController::class, 'photo'])->middleware([ProtectVoterPages::class, 'can:update-voter,voter'])->whereNumber('voter')->name('voters.photo');
    Route::get('/voters/{voter}/edit', [VoterController::class, 'edit'])->middleware([ProtectVoterPages::class, 'can:update-voter,voter'])->whereNumber('voter')->name('voters.edit');
    Route::patch('/voters/{voter}', [VoterController::class, 'update'])->middleware('can:update-voter,voter')->whereNumber('voter')->name('voters.update');
});

Route::middleware([PrivateDashboard::class, 'auth:web', EnsureAccountAccess::class, 'auth.session', 'can:import-voters'])->group(function () {
    Route::get('/voters/import', [VoterImportController::class, 'create'])->name('voters.import.create');
    Route::post('/voters/import', [VoterImportController::class, 'store'])->middleware('throttle:10,1')->name('voters.import');
    Route::get('/voters/imports/status', [VoterImportController::class, 'importStatus'])->name('voters.imports.status');
    Route::post('/voters/imports/{import}/retry', [VoterImportController::class, 'retryImport'])->middleware('throttle:10,1')->name('voters.imports.retry');
});
