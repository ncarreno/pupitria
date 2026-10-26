<?php

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
    Volt::route('fichas', 'pages.fichas.index')->name('fichas');
    Volt::route('fichas/nueva', 'pages.fichas.nueva')->name('fichas.nueva');
    Volt::route('fichas/{ficha}', 'pages.fichas.ver')->name('fichas.ver');
    Volt::route('curriculo', 'pages.curriculo')->name('curriculo');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Volt::route('ia', 'pages.admin.ia')->name('admin.ia');
    Volt::route('ia/uso', 'pages.admin.ia-uso')->name('admin.ia.uso');
});

require __DIR__.'/auth.php';
