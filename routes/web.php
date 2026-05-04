<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\FacialAdminController;
use App\Http\Controllers\Web\GestorAccessEventAdminController;
use App\Http\Controllers\Web\FacialSendController;
use App\Http\Controllers\Web\IeducarFrequenciaRegistroAdminController;
use App\Http\Controllers\Web\IeducarFrequenciaRegistroController;
use App\Http\Controllers\Web\IntegrationController;
use App\Http\Controllers\Web\IntegrationOverviewController;
use App\Http\Controllers\Web\SmsDeliveryController;
use App\Http\Controllers\Web\UserAuditLogController;
use App\Http\Controllers\Web\UserManagementController;
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
    if (! auth()->user()->is_admin) {
        return redirect()->route('integrations.overview');
    }

    return view('dashboard');
})->middleware('auth');

// Fluxo de coleta facial: somente via token do iEducar (sem modo manual).
Route::get('/facial/enviar', [FacialSendController::class, 'create'])->name('facial.send');
Route::post('/facial/enviar', [FacialSendController::class, 'store'])->name('facial.send.store');

Route::middleware('auth')->group(function () {
    Route::get('/integracoes', [IntegrationOverviewController::class, 'index'])->name('integrations.overview');
    Route::get('/integracoes/status', [IntegrationOverviewController::class, 'status'])->name('integrations.overview.status');

    Route::middleware('admin')->group(function () {
        Route::post('/integracoes/{key}/testar', [IntegrationOverviewController::class, 'test'])->name('integrations.overview.test');
        Route::post('/integracoes/ponte/ieducar', [IntegrationOverviewController::class, 'bridgeProbeIeducar'])->name('integrations.bridge.ieducar');
        Route::post('/integracoes/ponte/gestor', [IntegrationOverviewController::class, 'bridgeProbeGestor'])->name('integrations.bridge.gestor');
        Route::post('/integracoes/ponte/sms', [IntegrationOverviewController::class, 'bridgeProbeSms'])->name('integrations.bridge.sms');

        Route::get('/integracoes/ieducar', [IntegrationController::class, 'ieducar'])->name('integrations.ieducar');
        Route::post('/integracoes/ieducar', [IntegrationController::class, 'updateIeducar'])->name('integrations.ieducar.update');
        Route::post('/integracoes/ieducar/rotacionar-hmac', [IntegrationController::class, 'rotateIeducarHmac'])->name('integrations.ieducar.rotate-hmac');

        Route::get('/integracoes/ieducar/frequencia-registro', [IeducarFrequenciaRegistroController::class, 'index'])->name('integrations.ieducar.frequencia-registro');
        Route::post('/integracoes/ieducar/frequencia-registro/preview', [IeducarFrequenciaRegistroController::class, 'preview'])->name('integrations.ieducar.frequencia-registro.preview');
        Route::post('/integracoes/ieducar/frequencia-registro/enfileirar', [IeducarFrequenciaRegistroController::class, 'enqueue'])->name('integrations.ieducar.frequencia-registro.enqueue');
        Route::post('/integracoes/ieducar/frequencia-registro/{id}/enviar', [IeducarFrequenciaRegistroController::class, 'forceSend'])->name('integrations.ieducar.frequencia-registro.force-send');
        Route::get('/integracoes/ieducar/frequencia-registro/{id}', [IeducarFrequenciaRegistroController::class, 'show'])->name('integrations.ieducar.frequencia-registro.show');

        Route::get('/docs/ieducar-frequencia-registro-gide', function () {
            $path = base_path('docs/IEDUCAR_FREQUENCIA_REGISTRO_GIDE.md');

            return response()->file($path, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
            ]);
        })->name('integrations.docs.ieducar-frequencia-registro');

        Route::get('/integracoes/gestor', [IntegrationController::class, 'gestor'])->name('integrations.gestor');
        Route::post('/integracoes/gestor', [IntegrationController::class, 'updateGestor'])->name('integrations.gestor.update');
        Route::post('/integracoes/gestor/rotacionar-hmac', [IntegrationController::class, 'rotateGestorHmac'])->name('integrations.gestor.rotate-hmac');
        Route::post('/integracoes/gestor/gerar-token-webhook-catraca', [IntegrationController::class, 'generateGestorCatracaWebhookBearer'])->name('integrations.gestor.generate-catraca-webhook-bearer');
        Route::post('/integracoes/gestor/testar-auth', [IntegrationController::class, 'testGestorAuth'])->name('integrations.gestor.test-auth');
        Route::post('/integracoes/gestor/testar-unities', [IntegrationController::class, 'testGestorUnities'])->name('integrations.gestor.test-unities');

        Route::get('/integracoes/catraca-frequencia', [IntegrationController::class, 'catracaFrequencia'])->name('integrations.catraca-frequencia');
        Route::post('/integracoes/catraca-frequencia', [IntegrationController::class, 'updateCatracaFrequencia'])->name('integrations.catraca-frequencia.update');

        Route::get('/integracoes/sms', [IntegrationController::class, 'sms'])->name('integrations.sms');
        Route::post('/integracoes/sms', [IntegrationController::class, 'updateSms'])->name('integrations.sms.update');

        Route::get('/sms', [SmsDeliveryController::class, 'index'])->name('sms.index');
        Route::get('/sms/{id}', [SmsDeliveryController::class, 'show'])->name('sms.show');

        Route::get('/admin/gestor-access-events', [GestorAccessEventAdminController::class, 'index'])->name('admin.gestor-access-events.index');
        Route::post('/admin/gestor-access-events/{id}/retry', [GestorAccessEventAdminController::class, 'retry'])->name('admin.gestor-access-events.retry');
        Route::get('/admin/gestor-access-events/{id}', [GestorAccessEventAdminController::class, 'show'])->name('admin.gestor-access-events.show');

        Route::get('/admin/faciais', [FacialAdminController::class, 'index'])->name('admin.facial-requests.index');
        Route::get('/admin/faciais/{id}/gestor-invite', [FacialAdminController::class, 'inspectGestorInvite'])->name('admin.facial-requests.gestor-invite');
        Route::get('/admin/faciais/{id}', [FacialAdminController::class, 'show'])->name('admin.facial-requests.show');
        Route::post('/admin/faciais/{id}/atualizar-status', [FacialAdminController::class, 'refreshStatus'])->name('admin.facial-requests.refresh-status');

        Route::get('/admin/frequencia-ieducar', [IeducarFrequenciaRegistroAdminController::class, 'index'])->name('admin.ieducar-frequencia-deliveries.index');
        Route::get('/admin/frequencia-ieducar/{id}', [IeducarFrequenciaRegistroAdminController::class, 'show'])->name('admin.ieducar-frequencia-deliveries.show');

        Route::prefix('usuarios')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/novo', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::post('/{user}/desativar', [UserManagementController::class, 'deactivate'])->name('deactivate');
            Route::post('/{user}/reativar', [UserManagementController::class, 'reactivate'])->name('reactivate');
            Route::post('/{user}/promover-admin', [UserManagementController::class, 'promoteAdmin'])->name('promote-admin');
            Route::post('/{user}/rebaixar-admin', [UserManagementController::class, 'demoteAdmin'])->name('demote-admin');
        });

        Route::get('/admin/auditoria-usuarios', [UserAuditLogController::class, 'index'])->name('admin.user-audit-logs.index');
    });
});
