<?php

namespace App\Services\Outbound;

use App\Models\GestorGuestLink;
use App\Models\Integration;
use App\Models\OutboundDelivery;
use App\Services\Gestor\GestorClient;
use Carbon\CarbonImmutable;

class AccessControlOutboundService
{
    private function maxAttempts(): int
    {
        return max(1, (int) config('gide.deliveries.max_attempts', 3));
    }

    /**
     * Envia payload do iEducar para o sistema de controle de acesso (Gestor).
     *
     * O endpoint é configurável em integrations.extra.endpoints.enrollment_sync_path.
     */
    public function sendEnrollmentPayload(string $eventId, array $payload): OutboundDelivery
    {
        $integration = Integration::query()
            ->where('key', 'gestor')
            ->where('enabled', true)
            ->first();

        if (! $integration) {
            throw new \RuntimeException('Integração gestor não habilitada.');
        }

        $path = (string) data_get($integration->extra, 'endpoints.enrollment_sync_path', '');
        if ($path === '') {
            throw new \RuntimeException('Endpoint outbound não configurado (integrations.extra.endpoints.enrollment_sync_path).');
        }

        $invitePayload = $this->buildInvitePayload($integration, $payload);

        $delivery = OutboundDelivery::query()->firstOrCreate(
            [
                'integration_key' => 'gestor',
                'event_type' => 'enrollment_ingest',
                'event_id' => $eventId,
            ],
            [
                'endpoint' => $path,
                'payload' => $invitePayload,
            ],
        );

        if ($delivery->delivered_at) {
            return $delivery;
        }

        if ((int) $delivery->attempts >= $this->maxAttempts()) {
            $delivery->last_error = $delivery->last_error ?: 'Máximo de tentativas atingido.';
            $delivery->next_retry_at = null;
            $delivery->save();

            return $delivery;
        }

        $delivery->attempts = (int) $delivery->attempts + 1;
        $delivery->last_attempt_at = now();
        $delivery->last_error = null;
        $delivery->last_http_status = null;
        $delivery->save();

        try {
            $client = new GestorClient($integration);
            $resp = $client->request('post', $path, $invitePayload);

            $delivery->last_http_status = $resp->status();

            if ($resp->successful()) {
                $delivery->delivered_at = now();
                $delivery->next_retry_at = null;

                // Tenta capturar inviteId/guestId para permitir face create depois.
                $json = $resp->json();
                $inviteId = null;
                $guestId = null;
                if (is_array($json)) {
                    $inviteId = $json['id'] ?? $json['Id'] ?? data_get($json, 'invite.id') ?? data_get($json, 'Invite.Id');
                    $guestId = data_get($json, 'guests.0.id')
                        ?? data_get($json, 'guests.0.Id')
                        ?? data_get($json, 'Guests.0.id')
                        ?? data_get($json, 'Guests.0.Id')
                        ?? data_get($json, 'guestId')
                        ?? data_get($json, 'GuestId')
                        ?? data_get($json, 'guest.id')
                        ?? data_get($json, 'Guest.Id');
                }

                $codAluno = (string) ($invitePayload['name'] ?? '');
                if ($codAluno !== '') {
                    $link = GestorGuestLink::query()->firstOrCreate(['cod_aluno' => $codAluno], ['cod_aluno' => $codAluno]);
                    $link->invite_id = is_numeric($inviteId) ? (int) $inviteId : $link->invite_id;
                    $link->guest_id = is_numeric($guestId) ? (int) $guestId : $link->guest_id;
                    $link->last_invite_http_status = (int) $resp->status();
                    $link->last_invite_response_json = is_array($json) ? $json : null;
                    $link->last_error = ($link->guest_id || $guestId) ? null : 'Invite criado, mas guest_id não encontrado na resposta.';
                    $link->save();
                }
            } else {
                $delivery->last_error = 'HTTP '.$resp->status().' body='.(string) $resp->body();
                $delivery->next_retry_at = $delivery->attempts >= $this->maxAttempts() ? null : now()->addSeconds($this->backoffSeconds($delivery->attempts));
            }
        } catch (\Throwable $e) {
            $delivery->last_error = $e->getMessage();
            $delivery->next_retry_at = $delivery->attempts >= $this->maxAttempts() ? null : now()->addSeconds($this->backoffSeconds($delivery->attempts));
        }

        $delivery->save();

        return $delivery;
    }

    private function buildInvitePayload(Integration $integration, array $ieducarPayload): array
    {
        // No "name" do Invite e do Guest, vai o ID do aluno no iEducar (cod_aluno).
        $codAluno = (string) (
            data_get($ieducarPayload, 'identificacao.cod_aluno')
            ?? data_get($ieducarPayload, 'aluno.cod_aluno')
            ?? data_get($ieducarPayload, 'cod_aluno')
            ?? ''
        );
        if ($codAluno === '') {
            throw new \RuntimeException('Payload do iEducar sem cod_aluno (necessário para montar Invite/Guest).');
        }

        $inviteId = (int) preg_replace('/\D/', '', $codAluno);
        if ($inviteId <= 0) {
            throw new \RuntimeException('cod_aluno inválido (necessário para montar Invite.id).');
        }

        $unityId = $this->resolveGestorUnityId($integration);
        $accessProfileId = $this->resolveGestorAccessProfileId($integration);

        // Datas: por padrão, usa ano letivo e semestre (conforme exemplo).
        $ano = (int) (
            data_get($ieducarPayload, 'matricula.ano_letivo')
            ?? data_get($ieducarPayload, 'matricula.ano')
            ?? data_get($ieducarPayload, 'status.matricula.ano')
            ?? date('Y')
        );
        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $start = sprintf('%04d-01-01T01:00:00Z', $ano);
        $end = CarbonImmutable::parse($start)->addDays(365)->toIso8601ZuluString();

        // Invite.id deve ser válido: usar o mesmo valor do name (cod_aluno).
        return [
            'id' => $inviteId,
            'unityId' => $unityId,
            'name' => $codAluno,
            'start' => $start,
            'end' => $end,
            'accessProfileId' => $accessProfileId,
            'guests' => [
                [
                    // Guest.id: deixar null conforme orientação (auto-gerado/ignorado pelo SDK).
                    'id' => null,
                    'name' => $codAluno,
                ],
            ],
        ];
    }

    private function resolveGestorUnityId(Integration $integration): int
    {
        $raw = data_get($integration->extra, 'onboarding.unity_id')
            ?? data_get($integration->extra, 'defaults.unity_id')
            ?? config('integrations.gestor.default_unity_id');
        $v = (int) $raw;
        if ($v <= 0) {
            throw new \RuntimeException('unityId não configurado: defina em /integracoes/gestor (integrations.extra) ou GESTOR_DEFAULT_UNITY_ID no .env.');
        }

        return $v;
    }

    private function resolveGestorAccessProfileId(Integration $integration): int
    {
        $raw = data_get($integration->extra, 'onboarding.access_profile_id')
            ?? data_get($integration->extra, 'defaults.access_profile_id')
            ?? config('integrations.gestor.default_access_profile_id');
        $v = (int) $raw;
        if ($v <= 0) {
            throw new \RuntimeException('accessProfileId não configurado: defina em /integracoes/gestor (integrations.extra) ou GESTOR_DEFAULT_ACCESS_PROFILE_ID no .env.');
        }

        return $v;
    }

    private function backoffSeconds(int $attempts): int
    {
        $attempts = max(1, $attempts);

        // 10s, 30s, 60s, 120s, 300s (cap)
        return match (true) {
            $attempts <= 1 => 10,
            $attempts === 2 => 30,
            $attempts === 3 => 60,
            $attempts === 4 => 120,
            default => 300,
        };
    }
}
