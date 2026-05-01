<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\FacialAdminController;
use App\Http\Controllers\Web\FacialSendController;
use App\Http\Controllers\Web\IntegrationController;
use App\Http\Controllers\Web\IntegrationOverviewController;
use App\Http\Controllers\Web\SmsDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');

// Fluxo de coleta facial: somente via token do iEducar (sem modo manual).
Route::get('/facial/enviar', [FacialSendController::class, 'create'])->name('facial.send');
Route::post('/facial/enviar', [FacialSendController::class, 'store'])->name('facial.send.store');

Route::middleware('auth')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/integracoes', [IntegrationOverviewController::class, 'index'])->name('integrations.overview');
        Route::post('/integracoes/{key}/testar', [IntegrationOverviewController::class, 'test'])->name('integrations.overview.test');
        Route::post('/integracoes/ponte/ieducar', [IntegrationOverviewController::class, 'bridgeProbeIeducar'])->name('integrations.bridge.ieducar');
        Route::post('/integracoes/ponte/gestor', [IntegrationOverviewController::class, 'bridgeProbeGestor'])->name('integrations.bridge.gestor');
        Route::post('/integracoes/ponte/sms', [IntegrationOverviewController::class, 'bridgeProbeSms'])->name('integrations.bridge.sms');

        Route::get('/integracoes/ieducar', [IntegrationController::class, 'ieducar'])->name('integrations.ieducar');
        Route::post('/integracoes/ieducar', [IntegrationController::class, 'updateIeducar'])->name('integrations.ieducar.update');
        Route::post('/integracoes/ieducar/rotacionar-hmac', [IntegrationController::class, 'rotateIeducarHmac'])->name('integrations.ieducar.rotate-hmac');

        Route::get('/integracoes/gestor', [IntegrationController::class, 'gestor'])->name('integrations.gestor');
        Route::post('/integracoes/gestor', [IntegrationController::class, 'updateGestor'])->name('integrations.gestor.update');
        Route::post('/integracoes/gestor/rotacionar-hmac', [IntegrationController::class, 'rotateGestorHmac'])->name('integrations.gestor.rotate-hmac');
        Route::post('/integracoes/gestor/testar-auth', [IntegrationController::class, 'testGestorAuth'])->name('integrations.gestor.test-auth');
        Route::post('/integracoes/gestor/testar-unities', [IntegrationController::class, 'testGestorUnities'])->name('integrations.gestor.test-unities');

        Route::get('/integracoes/catraca-frequencia', [IntegrationController::class, 'catracaFrequencia'])->name('integrations.catraca-frequencia');
        Route::post('/integracoes/catraca-frequencia', [IntegrationController::class, 'updateCatracaFrequencia'])->name('integrations.catraca-frequencia.update');

        Route::get('/integracoes/sms', [IntegrationController::class, 'sms'])->name('integrations.sms');
        Route::post('/integracoes/sms', [IntegrationController::class, 'updateSms'])->name('integrations.sms.update');

        Route::get('/sms', [SmsDeliveryController::class, 'index'])->name('sms.index');
        Route::get('/sms/{id}', [SmsDeliveryController::class, 'show'])->name('sms.show');

        Route::get('/admin/faciais', [FacialAdminController::class, 'index'])->name('admin.facial-requests.index');
        Route::get('/admin/faciais/{id}', [FacialAdminController::class, 'show'])->name('admin.facial-requests.show');
        Route::post('/admin/faciais/{id}/atualizar-status', [FacialAdminController::class, 'refreshStatus'])->name('admin.facial-requests.refresh-status');
    });
});
