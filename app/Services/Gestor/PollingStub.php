<?php

namespace App\Services\Gestor;

use App\Models\Integration;

class PollingStub
{
    public function __construct(private readonly Integration $integration) {}

    /**
     * MVP: webhook é o caminho principal. Este stub existe para fallback futuro
     * quando o Gestor não suportar callbacks.
     */
    public function pollSince(?string $cursor = null): array
    {
        return [
            'status' => 'not_implemented',
            'reason' => 'MVP usa webhook. Configure o Gestor para postar eventos em /api/v1/gestor/access-events.',
            'cursor' => $cursor,
        ];
    }
}
