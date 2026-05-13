<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gestor (Porter / Kiper SDK)
    |--------------------------------------------------------------------------
    |
    | URL, path de convite, unityId e accessProfileId vêm apenas do banco
    | (integrations key=gestor), via /integracoes/gestor — sem fallback em .env.
    |
    */
    'gestor' => [],

    /*
    |--------------------------------------------------------------------------
    | SMS — apenas URLs públicas de fallback (credenciais só no banco)
    |--------------------------------------------------------------------------
    |
    | Account SID, tokens, From e base_url específica ficam em `integrations`
    | (key=sms), gravados pela UI /integracoes/sms — sem TWILIO_* ou SMS_* no .env.
    |
    */
    'sms' => [
        /** Provedor quando `integrations.extra.provider` não está definido. */
        'default_provider' => 'twilio',
        /** Raiz da API REST Twilio (2010-04-01), sem barra final — usada se base_url vazio. */
        'twilio_api_root' => 'https://api.twilio.com/2010-04-01',
        /** Base URL padrão Zenvia v2 — usada se integrations.base_url vazio com provider=zenvia. */
        'default_base_url' => 'https://api.zenvia.com/v2',
    ],

];
