<?php

namespace App\Services\Photo;

use Illuminate\Http\Client\Response;

interface PhotoSource
{
    /**
     * Deve retornar uma resposta HTTP em modo stream (sem salvar em disco).
     */
    public function fetch(array $context): Response;
}
