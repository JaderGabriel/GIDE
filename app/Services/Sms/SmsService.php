<?php

namespace App\Services\Sms;

use App\Models\Integration;
use App\Models\SmsDelivery;
use App\Models\SmsTemplate;
use Carbon\CarbonInterface;

class SmsService
{
    private function maxAttempts(): int
    {
        return max(1, (int) config('gide.deliveries.max_attempts', 3));
    }

    public function sendPresenceSms(string $eventId, array $payload, array $analysis, ?CarbonInterface $occurredAt = null): SmsDelivery
    {
        $smsIntegration = Integration::query()->where('key', 'sms')->where('enabled', true)->first();
        if (! $smsIntegration) {
            throw new \RuntimeException('Integração SMS não habilitada.');
        }

        $template = SmsTemplate::query()->where('key', 'presence_notification')->where('enabled', true)->first();
        if (! $template) {
            throw new \RuntimeException('Template de SMS (presence_notification) não configurado/ativo.');
        }

        $phoneKey = (string) data_get($smsIntegration->extra, 'payload_map.phone', 'phone');
        $toRaw = data_get($payload, $phoneKey);
        $to = $this->normalizeBrPhone((string) ($toRaw ?? ''));
        if ($to === '') {
            throw new \RuntimeException('Telefone do responsável não encontrado no payload.');
        }

        $from = (string) data_get($smsIntegration->extra, 'from', '');
        if ($from === '') {
            throw new \RuntimeException('Remetente do SMS não configurado (integrations.extra.from).');
        }

        $context = [
            'event_id' => $eventId,
            'aluno_id' => data_get($analysis, 'aluno_id') ?? data_get($payload, 'aluno_id'),
            'matricula_id' => data_get($analysis, 'matricula_id') ?? data_get($payload, 'matricula_id'),
            'window' => data_get($analysis, 'window.name'),
            'occurred_at' => $occurredAt?->toDateTimeString(),
            'date' => $occurredAt?->format('d/m/Y'),
            'time' => $occurredAt?->format('H:i'),
            'event_type' => data_get($payload, 'type') ?? data_get($payload, 'event_type'),
        ];

        $message = (new SmsTemplateRenderer)->render($template->body, $context);

        $delivery = SmsDelivery::query()->firstOrCreate(
            ['event_id' => $eventId, 'template_key' => $template->key, 'to' => $to],
            [
                'from' => $from,
                'message' => $message,
                'provider' => (string) data_get($smsIntegration->extra, 'provider', 'zenvia'),
                'status' => 'pending',
                'aluno_id' => is_scalar($context['aluno_id'] ?? null) ? (string) $context['aluno_id'] : null,
                'matricula_id' => is_scalar($context['matricula_id'] ?? null) ? (string) $context['matricula_id'] : null,
                'window' => is_scalar($context['window'] ?? null) ? (string) $context['window'] : null,
                'event_type' => is_scalar($context['event_type'] ?? null) ? (string) $context['event_type'] : null,
                'occurred_at' => $occurredAt,
                'context' => $context,
            ],
        );

        if ($delivery->sent_at) {
            return $delivery;
        }

        if ((int) $delivery->attempts >= $this->maxAttempts()) {
            $delivery->status = 'error';
            $delivery->last_error = $delivery->last_error ?: 'Máximo de tentativas atingido.';
            $delivery->next_retry_at = null;
            $delivery->save();

            return $delivery;
        }

        $delivery->attempts = (int) $delivery->attempts + 1;
        $delivery->last_error = null;
        $delivery->last_http_status = null;
        $delivery->save();

        $provider = (string) data_get($smsIntegration->extra, 'provider', 'zenvia');
        if ($provider !== 'zenvia') {
            $delivery->last_error = 'Provedor de SMS não suportado: '.$provider;
            $delivery->status = 'error';
            $delivery->next_retry_at = null;
            $delivery->save();

            return $delivery;
        }

        $resp = (new ZenviaSmsClient($smsIntegration))->sendText($to, $from, $message, $eventId);
        $delivery->last_http_status = $resp->status();
        $delivery->provider_response = $resp->json();

        if ($resp->successful()) {
            $delivery->provider_message_id = (string) ($resp->json('id') ?? '');
            $delivery->sent_at = now();
            $delivery->status = 'sent';
            $delivery->next_retry_at = null;
        } else {
            $delivery->last_error = 'HTTP '.$resp->status().' body='.(string) $resp->body();
            $delivery->status = 'error';
            $delivery->next_retry_at = $delivery->attempts >= $this->maxAttempts() ? null : now()->addSeconds($this->backoffSeconds($delivery->attempts));
        }

        $delivery->save();

        return $delivery;
    }

    private function normalizeBrPhone(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';
        if ($digits === '') {
            return '';
        }

        // Se vier com 0 inicial, remove.
        $digits = ltrim($digits, '0');

        // Se vier sem DDI e tiver 10/11 dígitos (DDD + número), prefixa 55.
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55'.$digits;
        }

        // E.164 numérico (DDI + número), ex.: 5511988887777
        if (strlen($digits) < 12) {
            return '';
        }

        return $digits;
    }

    private function backoffSeconds(int $attempts): int
    {
        $attempts = max(1, $attempts);

        return match (true) {
            $attempts <= 1 => 10,
            $attempts === 2 => 30,
            $attempts === 3 => 60,
            $attempts === 4 => 120,
            default => 300,
        };
    }
}
