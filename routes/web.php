<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\FacialAdminController;
use App\Http\Controllers\Web\FacialSendController;
use App\Http\Controllers\Web\GestorAccessEventAdminController;
use App\Http\Controllers\Web\GideQueuesController;
use App\Http\Controllers\Web\IeducarFrequenciaRegistroAdminController;
use App\Http\Controllers\Web\IeducarFrequenciaRegistroController;
use App\Http\Controllers\Web\IntegrationController;
use App\Http\Controllers\Web\IntegrationOverviewController;
use App\Http\Controllers\Web\SmsDeliveryController;
use App\Http\Controllers\Web\UserAuditLogController;
use App\Http\Controllers\Web\UserManagementController;
use App\Models\Integration;
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

    $byKey = Integration::query()->get()->keyBy(fn (Integration $i) => (string) $i->key);
    $ieducar = $byKey->get('ieducar');
    $gestor = $byKey->get('gestor');
    $sms = $byKey->get('sms');

    $integrationConfigured = function (?Integration $i): bool {
        if (! $i) {
            return false;
        }
        $hasBase = is_string($i->base_url ?? null) && (string) $i->base_url !== '';
        $hasAuthToken = is_string($i->auth_token ?? null) && (string) $i->auth_token !== '';
        $hasHmac = is_string($i->hmac_secret ?? null) && (string) $i->hmac_secret !== '';

        return $hasBase || $hasAuthToken || $hasHmac || ! empty($i->extra);
    };

    $ieducarInboundReady = (bool) ($ieducar
        && $ieducar->base_url
        && ($ieducar->auth_token || data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token')));

    $gestorConfigured = $integrationConfigured($gestor);
    $gestorEnabled = (bool) ($gestor?->enabled ?? false);
    $gestorChainOk = $gestorConfigured && $gestorEnabled;

    $smsConfigured = $integrationConfigured($sms);
    $smsEnabled = (bool) ($sms?->enabled ?? false);
    $smsChainReady = $smsConfigured && $gestorChainOk && $smsEnabled;

    $dashFlowLanes = [
        'ieducar_in' => $ieducarInboundReady
            ? [
                'tone' => 'ok',
                'label' => 'Ingresso pronto (API + token)',
                'hint' => 'A integração iEducar tem URL base e token de API (ou token de confirmação em extra). O ERP pode chamar as rotas inbound do GIDE e obter o link de coleta facial com token.',
            ]
            : [
                'tone' => 'warn',
                'label' => 'Configurar iEducar (URL base e token)',
                'hint' => 'Em Integrações → iEducar, defina a URL base e o Bearer da API, ou o token em catraca_frequencia, para o GIDE aceitar pedidos inbound e devolver URLs assinadas.',
            ],
        'gestor' => $gestorChainOk
            ? [
                'tone' => 'ok',
                'label' => 'Gestor activo para enroll',
                'hint' => 'A integração Gestor está configurada e habilitada: a coleta facial pode ser enviada para enroll na catraca.',
            ]
            : ($gestorConfigured
                ? [
                    'tone' => 'warn',
                    'label' => 'Gestor configurado — habilitar',
                    'hint' => 'Os dados da integração Gestor existem, mas a integração está desligada. Habilite-a para permitir envio de fotos ao enroll.',
                ]
                : [
                    'tone' => 'warn',
                    'label' => 'Configurar Gestor (SDK / catraca)',
                    'hint' => 'Registe a integração Gestor (URL, credenciais/SDK) para o GIDE encaminhar capturas à catraca após a coleta.',
                ]),
        'notify' => match (true) {
            $smsChainReady => [
                'tone' => 'ok',
                'label' => 'Cadeia Gestor + SMS pronta',
                'hint' => 'Gestor e SMS estão configurados e activos: eventos de presença podem disparar notificações SMS conforme as regras e fila assíncrona.',
            ],
            $gestorChainOk && ! $smsConfigured => [
                'tone' => 'warn',
                'label' => 'Configure a integração SMS',
                'hint' => 'O Gestor está pronto, mas falta configurar a integração SMS (provedor, credenciais). Sem isso o ramo notify não envia mensagens.',
            ],
            $gestorChainOk && $smsConfigured && ! $smsEnabled => [
                'tone' => 'warn',
                'label' => 'Habilite o SMS',
                'hint' => 'A integração SMS está preenchida mas desligada. Habilite-a para processar envios após presença.',
            ],
            default => [
                'tone' => 'neutral',
                'label' => 'Ramo em espera (Gestor / SMS)',
                'hint' => 'O ramo paralelo ao SMS depende do Gestor operacional e, em seguida, do SMS. Este estado resume o que falta na cadeia.',
            ],
        },
        'ieducar_out' => $ieducarInboundReady
            ? [
                'tone' => 'ok',
                'label' => 'Consulta e confirmação disponíveis',
                'hint' => 'Com o mesmo token/API do iEducar, o GIDE pode confirmar a coleta facial e consultar situação de matrícula no ERP.',
            ]
            : [
                'tone' => 'warn',
                'label' => 'Depende do token iEducar',
                'hint' => 'Saídas para o iEducar (confirmação, consulta) usam as credenciais do conector iEducar. Complete URL e token para activar este percurso.',
            ],
    ];

    return view('dashboard', compact('dashFlowLanes'));
})->middleware('auth');

// Fluxo de coleta facial: somente via token do iEducar (sem modo manual).
Route::get('/facial/enviar', [FacialSendController::class, 'create'])->name('facial.send');
Route::post('/facial/enviar', [FacialSendController::class, 'store'])->name('facial.send.store');

Route::middleware('auth')->group(function () {
    Route::get('/integracoes', [IntegrationOverviewController::class, 'index'])->name('integrations.overview');
    Route::get('/integracoes/filas', [GideQueuesController::class, 'index'])->name('integrations.gide-queues');
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
        Route::post('/admin/gestor-access-events/{id}/requeue', [GestorAccessEventAdminController::class, 'requeue'])->name('admin.gestor-access-events.requeue');
        Route::post('/admin/gestor-access-events/{id}/force-mark-presence', [GestorAccessEventAdminController::class, 'forceMarkPresence'])->name('admin.gestor-access-events.force-mark-presence');
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
