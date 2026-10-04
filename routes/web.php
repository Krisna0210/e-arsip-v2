<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', function () {
    return view('pages.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('pages.dashboard');
})->name('dashboard');

Route::get('/settings/roles', function () {
    return view('pages.settings.roles.index');
})->name('settings.roles.index');

Route::get('/settings/roles/create', function () {
    return view('pages.settings.roles.edit');
})->name('settings.roles.create');
