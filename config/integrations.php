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
    | SMS (URL e token via integração ou .env)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'default_base_url' => env('SMS_DEFAULT_BASE_URL', 'https://api.zenvia.com/v2'),
    ],

];
