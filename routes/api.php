<?php

use App\Http\Controllers\Api\CatracaAccessWebhookController;
use App\Http\Controllers\Api\GestorWebhookController;
use App\Http\Controllers\Api\GideFacialInboundController;
use App\Http\Controllers\Api\IeducarFacialRequestController;
use App\Http\Controllers\Api\IeducarInboundController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/ieducar/enrollments', [IeducarInboundController::class, 'store'])
        ->middleware('verify.hmac:ieducar')
        ->name('api.ieducar.enrollments.store');

    Route::post('/ieducar/facial-requests', [IeducarFacialRequestController::class, 'store'])
        ->middleware('verify.hmac:ieducar')
        ->name('api.ieducar.facial-requests.store');

    Route::post('/gestor/access-events', [GestorWebhookController::class, 'store'])
        ->middleware('verify.hmac:gestor')
        ->name('api.gestor.access-events.store');

    /** Catraca → GIDE: JSON + Bearer (token em extra.catraca_access_token_hash; mesma auditoria que gestor/access-events). */
    Route::post('/catraca/access-events', [CatracaAccessWebhookController::class, 'store'])
        ->middleware('verify.catraca.webhook.bearer')
        ->name('api.catraca.access-events.store');

    // iEducar → GIDE (Catraca/Frequência): endpoints fixos, auth Bearer por integração.
    Route::post('/catraca-frequencia/gide/facial/nova', [GideFacialInboundController::class, 'nova'])
        ->middleware('verify.bearer:catraca_frequencia')
        ->name('api.catraca-frequencia.gide.facial.nova');

    Route::post('/catraca-frequencia/gide/facial/excluir', [GideFacialInboundController::class, 'excluir'])
        ->middleware('verify.bearer:catraca_frequencia')
        ->name('api.catraca-frequencia.gide.facial.excluir');
});
