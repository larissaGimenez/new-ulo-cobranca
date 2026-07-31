<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DevSettingsController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ReverbController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;

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
    Route::post('/dev-settings/vinculos/import-client', [DevSettingsController::class, 'importOrphanClient'])->name('dev-settings.vinculos.import-client');
    Route::get('/dev-settings/vinculos/search-clients', [DevSettingsController::class, 'searchClients'])->name('dev-settings.vinculos.search-clients');
    Route::post('/dev-settings/vinculos/reassociate', [DevSettingsController::class, 'reassociateTitle'])->name('dev-settings.vinculos.reassociate');
    Route::get('/dev-settings/vinculos/{id}/suggest', [DevSettingsController::class, 'suggestReassociation'])->name('dev-settings.vinculos.suggest');
    Route::get('/dev-settings/contas', [DevSettingsController::class, 'contasIndex'])->name('dev-settings.contas');
    Route::post('/dev-settings/contas/sync-page', [DevSettingsController::class, 'syncContasPage'])->name('dev-settings.contas.sync-page');
    
    // Acompanhamento routes
    Route::get('/dev-settings/acompanhamento', [DevSettingsController::class, 'acompanhamentoIndex'])->name('dev-settings.acompanhamento');
    Route::get('/dev-settings/reverb/status', [ReverbController::class, 'status'])->name('dev-settings.reverb.status');
    Route::post('/dev-settings/reverb/start', [ReverbController::class, 'start'])->name('dev-settings.reverb.start');
    Route::post('/dev-settings/reverb/stop', [ReverbController::class, 'stop'])->name('dev-settings.reverb.stop');

    // Webhook logs route
    Route::get('/dev-settings/logs-webhooks', [DevSettingsController::class, 'logsIndex'])->name('dev-settings.logs-webhooks');

    // Notification routes
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');
    Route::post('/notifications/clear', [NotificationController::class, 'clearAll'])->name('notifications.clear');

    // Settings routes
    Route::get('/configuracoes', [SettingsController::class, 'index'])->name('configuracoes');
    Route::put('/configuracoes', [SettingsController::class, 'update'])->name('configuracoes.update');

    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes');
    Route::post('/clientes/update-stage', [ClientesController::class, 'updateStage'])->name('clientes.update-stage');
    Route::post('/clientes/kanban/column/store', [ClientesController::class, 'storeColumn'])->name('clientes.kanban.column.store');
    Route::post('/clientes/kanban/column/reorder', [ClientesController::class, 'reorderColumns'])->name('clientes.kanban.column.reorder');
    Route::post('/clientes/kanban/column/delete', [ClientesController::class, 'deleteColumn'])->name('clientes.kanban.column.delete');
});
