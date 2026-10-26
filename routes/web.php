<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('clases', 'pages.clases')
    ->middleware(['auth'])
    ->name('clases');

Route::middleware(['auth'])->group(function () {
    Volt::route('proyectos', 'pages.proyectos.index')->name('proyectos');
    Volt::route('proyectos/nuevo', 'pages.proyectos.nuevo')->name('proyectos.nuevo');
    Volt::route('proyectos/{proyecto}', 'pages.proyectos.ver')->name('proyectos.ver');
    Volt::route('fichas', 'pages.fichas.index')->name('fichas');
    Volt::route('fichas/nueva', 'pages.fichas.nueva')->name('fichas.nueva');
    Volt::route('fichas/{ficha}', 'pages.fichas.ver')->name('fichas.ver');
});

Volt::route('curriculo', 'pages.curriculo')
    ->middleware(['auth'])
    ->name('curriculo');

require __DIR__.'/auth.php';
