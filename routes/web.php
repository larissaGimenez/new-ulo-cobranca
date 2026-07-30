<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DevSettingsController;
use App\Http\Controllers\ClientesController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dev-settings', [DevSettingsController::class, 'index'])->name('dev-settings');
    Route::post('/dev-settings/sync-page', [DevSettingsController::class, 'syncPage'])->name('dev-settings.sync-page');
    Route::get('/dev-settings/clientes', [DevSettingsController::class, 'clientesIndex'])->name('dev-settings.clientes');
    Route::post('/dev-settings/clientes/sync-page', [DevSettingsController::class, 'syncClientesPage'])->name('dev-settings.clientes.sync-page');
    Route::get('/dev-settings/vinculos', [DevSettingsController::class, 'vinculosIndex'])->name('dev-settings.vinculos');
    Route::get('/dev-settings/contas', [DevSettingsController::class, 'contasIndex'])->name('dev-settings.contas');
    Route::post('/dev-settings/contas/sync-page', [DevSettingsController::class, 'syncContasPage'])->name('dev-settings.contas.sync-page');

    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes');
});
