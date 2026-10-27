<?php

use App\Http\Controllers\ImpresionController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

/**
 * La docente tiene que verificar su email antes de usar la aplicación.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Volt::route('clases', 'pages.clases')->name('clases');
    Volt::route('proyectos', 'pages.proyectos.index')->name('proyectos');
    Volt::route('proyectos/nuevo', 'pages.proyectos.nuevo')->name('proyectos.nuevo');
    Volt::route('proyectos/{proyecto}', 'pages.proyectos.ver')->name('proyectos.ver');
    Route::get('proyectos/{proyecto}/imprimir', [ImpresionController::class, 'proyecto'])->middleware('can:view,proyecto')->name('proyectos.imprimir');
    Volt::route('fichas', 'pages.fichas.index')->name('fichas');
    Volt::route('fichas/nueva', 'pages.fichas.nueva')->name('fichas.nueva');
    Volt::route('fichas/{ficha}', 'pages.fichas.ver')->name('fichas.ver');
    Route::get('fichas/{ficha}/imprimir', [ImpresionController::class, 'ficha'])->middleware('can:view,ficha')->name('fichas.imprimir');
    Volt::route('curriculo', 'pages.curriculo')->name('curriculo');
});

/**
 * La administración va aparte de la app de las docentes: su propia entrada, su layout y su menú.
 * Sin sesión, `/admin/*` lleva a `/admin/login` (ver `bootstrap/app.php`).
 */
Route::prefix('admin')->name('admin.')->group(function () {
    Volt::route('login', 'pages.admin.login')->name('login');

    Route::middleware(['auth', 'verified', 'admin'])->group(function () {
        Volt::route('/', 'pages.admin.inicio')->name('inicio');
        Volt::route('ia', 'pages.admin.ia')->name('ia');
        Volt::route('ia/uso', 'pages.admin.ia-uso')->name('ia.uso');
        Volt::route('cuentas', 'pages.admin.cuentas')->name('cuentas');
    });

    /**
     * La batería de pruebas la ven y valoran el administrador y quien tenga el permiso (la beta tester).
     */
    Route::middleware(['auth', 'verified', 'can:valorar-pruebas'])->group(function () {
        Volt::route('ia/pruebas', 'pages.admin.pruebas.index')->name('pruebas');
        Volt::route('ia/pruebas/{caso}', 'pages.admin.pruebas.ver')->name('pruebas.ver');
    });
});

require __DIR__.'/auth.php';
