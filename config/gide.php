<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entregas outbound (Gestor) e SMS
    |--------------------------------------------------------------------------
    |
    | Tentativas por registro em outbound_deliveries / sms_deliveries e
    | recuperação de jobs que nunca rodaram (fila/worker parado).
    |
    */
    'deliveries' => [
        'max_attempts' => (int) env('GIDE_DELIVERY_MAX_ATTEMPTS', 3),
        /** Re-despacho opcional: entregas com attempts=0 e sem sucesso há N minutos */
        'stale_minutes' => (int) env('GIDE_DELIVERY_STALE_MINUTES', 15),
    ],

];
