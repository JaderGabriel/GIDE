<?php

namespace App\Support;

/**
 * Chaves de {@see \App\Models\SmsTemplate} para notificações de presença.
 */
final class SmsTemplateKey
{
    /** SMS ao registar presença a partir do evento da catraca (antes ou independente do iEducar). */
    public const PRESENCE_CATRACA = 'presence_catraca';

    /** SMS após envio à API catraca-frequência do iEducar com sucesso (confirmação HTTP). */
    public const PRESENCE_IEDUCAR_SYNC = 'presence_ieducar_sync';

    /** Chave legada em bases antigas; resolvida para {@see self::PRESENCE_CATRACA} no serviço. */
    public const LEGACY_PRESENCE_NOTIFICATION = 'presence_notification';
}
