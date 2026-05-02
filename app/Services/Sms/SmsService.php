<?php

namespace App\Services\Sms;

use App\Models\Integration;
use App\Models\SmsDelivery;
use App\Models\SmsTemplate;
use App\Support\BrPhoneNormalizer;
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

        $recipients = $this->resolvePresenceSmsRecipients($smsIntegration, $payload);
        if ($recipients === []) {
            throw new \RuntimeException('Nenhum destinatário válido para SMS (verifique telefone no payload ou números de teste na configuração).');
        }

        $last = null;
        foreach ($recipients as $to) {
            $last = $this->sendPresenceSmsToRecipient($smsIntegration, $template, $eventId, $payload, $analysis, $occurredAt, $to);
        }

        return $last ?? throw new \RuntimeException('Falha interna ao enviar SMS.');
    }

    /**
     * @return list<string> E.164 digits without +
     */
    private function resolvePresenceSmsRecipients(Integration $smsIntegration, array $payload): array
    {
        $mode = (string) data_get($smsIntegration->extra, 'sms_recipient_mode', 'alunos');
        if ($mode === 'test_numbers') {
            $raw = data_get($smsIntegration->extra, 'test_phone_numbers', []);
            $list = is_array($raw) ? $raw : [];
            $out = [];
            foreach ($list as $item) {
                $n = BrPhoneNormalizer::toE164Digits((string) $item);
                if ($n !== '') {
                    $out[] = $n;
                }
            }
            $out = array_values(array_unique($out));
            if ($out === []) {
                throw new \RuntimeException('Modo de testes SMS ativo, mas nenhum número de teste válido cadastrado.');
            }

            return $out;
        }

        $phoneKey = (string) data_get($smsIntegration->extra, 'payload_map.phone', 'phone');
        $toRaw = data_get($payload, $phoneKey);
        $to = BrPhoneNormalizer::toE164Digits((string) ($toRaw ?? ''));
        if ($to === '') {
            throw new \RuntimeException('Telefone do responsável não encontrado no payload.');
        }

        return [$to];
    }

    private function sendPresenceSmsToRecipient(
        Integration $smsIntegration,
        SmsTemplate $template,
        string $eventId,
        array $payload,
        array $analysis,
        ?CarbonInterface $occurredAt,
        string $to,
    ): SmsDelivery {
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
