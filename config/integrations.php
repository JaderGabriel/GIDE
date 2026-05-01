<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gestor (Porter / Kiper SDK)
    |--------------------------------------------------------------------------
    |
    | Valores opcionais usados quando não estiverem em integrations(key=gestor).extra.
    | Preferência: configure na tela /integracoes/gestor (banco).
    |
    */
    'gestor' => [
        /** Base URL pública do SDK (seed / primeiro cadastro; preferir salvar na integração no banco). */
        'default_base_url' => env('GESTOR_DEFAULT_BASE_URL'),
        'default_unity_id' => env('GESTOR_DEFAULT_UNITY_ID'),
        'default_access_profile_id' => env('GESTOR_DEFAULT_ACCESS_PROFILE_ID'),
        /** Path relativo ao base_url quando não houver em integrations.extra */
        'default_enrollment_sync_path' => env('GESTOR_DEFAULT_ENROLLMENT_SYNC_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (URL e token via integração ou .env)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'default_base_url' => env('SMS_DEFAULT_BASE_URL', 'https://api.zenvia.com/v2'),
    ],

];
