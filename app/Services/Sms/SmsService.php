<?php

namespace App\Services\Sms;

use App\Models\Integration;
use App\Models\SmsDelivery;
use App\Models\SmsTemplate;
use App\Support\BrPhoneNormalizer;
use App\Support\SmsTemplateKey;
use Carbon\CarbonInterface;

class SmsService
{
    private function maxAttempts(): int
    {
        return max(1, (int) config('gide.deliveries.max_attempts', 3));
    }

    /**
     * @param  array<string, mixed>  $extraContext  Mesclado no contexto de tags (ex.: ieducar_http_status).
     * @param  list<string>|null  $overrideRecipientDigits  E.164 só dígitos; se definido, ignora modo alunos/testes da integração.
     * @param  string  $triggerSource  Origem do envio (gravada em context.send_log em sms_deliveries).
     */
    public function sendPresenceSms(
        string $eventId,
        array $payload,
        array $analysis,
        ?CarbonInterface $occurredAt = null,
        string $templateKey = SmsTemplateKey::PRESENCE_CATRACA,
        array $extraContext = [],
        bool $allowResendWhenAlreadySent = false,
        ?array $overrideRecipientDigits = null,
        string $triggerSource = 'automated',
    ): SmsDelivery {
        $smsIntegration = Integration::query()->where('key', 'sms')->where('enabled', true)->first();
        if (! $smsIntegration) {
            throw new \RuntimeException('Integração SMS não habilitada.');
        }

        $resolvedKey = $this->resolvePresenceTemplateKey($templateKey);
        $template = SmsTemplate::query()->where('key', $resolvedKey)->where('enabled', true)->first();
        if (! $template) {
            throw new \RuntimeException('Template de SMS ('.$resolvedKey.') não configurado/ativo.');
        }

        if ($overrideRecipientDigits !== null) {
            $recipients = $this->normalizeRecipientDigitList($overrideRecipientDigits);
            if ($recipients === []) {
                throw new \RuntimeException('Nenhum destinatário válido na lista de telefones fornecida.');
            }
        } else {
            $recipients = $this->resolvePresenceSmsRecipients($smsIntegration, $payload);
            if ($recipients === []) {
                throw new \RuntimeException('Nenhum destinatário válido para SMS (verifique telefone no payload ou números de teste na configuração).');
            }
        }

        $last = null;
        foreach ($recipients as $to) {
            $last = $this->sendPresenceSmsToRecipient(
                $smsIntegration,
                $template,
                $eventId,
                $payload,
                $analysis,
                $occurredAt,
                $to,
                $extraContext,
                $allowResendWhenAlreadySent,
                $triggerSource,
            );
        }

        return $last ?? throw new \RuntimeException('Falha interna ao enviar SMS.');
    }

    /**
     * Telefones de responsáveis encontrados no payload (várias chaves comuns). E.164 só dígitos, únicos.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public static function extractGuardianRecipientDigitsFromPayload(array $payload): array
    {
        $rawCandidates = [];

        $scalarKeys = [
            'phone', 'telefone', 'celular', 'mobile', 'parent_phone', 'parentPhone',
            'responsible_phone', 'responsiblePhone', 'responsavel_phone',
        ];
        foreach ($scalarKeys as $k) {
            $v = data_get($payload, $k);
            if (is_string($v) || is_numeric($v)) {
                $rawCandidates[] = (string) $v;
            }
        }

        foreach (['responsavel.phone', 'responsavel.telefone', 'responsavel.celular', 'responsavel.mobile'] as $path) {
            $v = data_get($payload, $path);
            if (is_string($v) || is_numeric($v)) {
                $rawCandidates[] = (string) $v;
            }
        }

        foreach (['responsaveis', 'responsibles', 'parents', 'guardians'] as $listKey) {
            $list = data_get($payload, $listKey);
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                foreach (['phone', 'telefone', 'celular', 'mobile'] as $rk) {
                    $v = $row[$rk] ?? null;
                    if (is_string($v) || is_numeric($v)) {
                        $rawCandidates[] = (string) $v;
                    }
                }
            }
        }

        $out = [];
        foreach ($rawCandidates as $raw) {
            $n = BrPhoneNormalizer::toE164Digits($raw);
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<string>  $digits
     * @return list<string>
     */
    private function normalizeRecipientDigitList(array $digits): array
    {
        $out = [];
        foreach ($digits as $item) {
            $n = BrPhoneNormalizer::toE164Digits((string) $item);
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }

    private function resolvePresenceTemplateKey(string $requested): string
    {
        if ($requested === SmsTemplateKey::PRESENCE_CATRACA) {
            if (SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_CATRACA)->exists()) {
                return SmsTemplateKey::PRESENCE_CATRACA;
            }

            return SmsTemplateKey::LEGACY_PRESENCE_NOTIFICATION;
        }

        return $requested;
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
        array $extraContext = [],
        bool $allowResendWhenAlreadySent = false,
        string $triggerSource = 'automated',
    ): SmsDelivery {
        $from = (string) data_get($smsIntegration->extra, 'from', '');
        if ($from === '') {
            throw new \RuntimeException('Remetente do SMS não configurado (integrations.extra.from).');
        }

        $context = array_merge([
            'event_id' => $eventId,
            'aluno_id' => data_get($analysis, 'aluno_id') ?? data_get($payload, 'aluno_id'),
            'matricula_id' => data_get($analysis, 'matricula_id') ?? data_get($payload, 'matricula_id'),
            'window' => data_get($analysis, 'window.name'),
            'occurred_at' => $occurredAt?->toDateTimeString(),
            'date' => $occurredAt?->format('d/m/Y'),
            'time' => $occurredAt?->format('H:i'),
            'event_type' => data_get($payload, 'type') ?? data_get($payload, 'event_type'),
        ], $extraContext);

        $message = (new SmsTemplateRenderer)->render($template->body, $context);

        $delivery = SmsDelivery::query()->firstOrCreate(
            ['event_id' => $eventId, 'template_key' => $template->key, 'to' => $to],
            [
                'from' => $from,
                'message' => $message,
                'provider' => (string) data_get($smsIntegration->extra, 'provider', config('integrations.sms.default_provider', 'twilio')),
                'status' => 'pending',
                'aluno_id' => is_scalar($context['aluno_id'] ?? null) ? (string) $context['aluno_id'] : null,
                'matricula_id' => is_scalar($context['matricula_id'] ?? null) ? (string) $context['matricula_id'] : null,
                'window' => is_scalar($context['window'] ?? null) ? (string) $context['window'] : null,
                'event_type' => is_scalar($context['event_type'] ?? null) ? (string) $context['event_type'] : null,
                'occurred_at' => $occurredAt,
                'context' => $context,
            ],
        );

        if ($allowResendWhenAlreadySent) {
            $oldContext = is_array($delivery->context) ? $delivery->context : [];
            $delivery->message = $message;
            $delivery->context = array_merge($oldContext, $context);
            $delivery->from = $from;
            $delivery->occurred_at = $occurredAt;
            $delivery->sent_at = null;
            $delivery->provider_message_id = null;
            $delivery->provider_response = null;
            $delivery->last_http_status = null;
            $delivery->last_error = null;
            $delivery->next_retry_at = null;
            $delivery->status = 'pending';
            $delivery->attempts = 0;
            $delivery->save();
        }

        if ($delivery->sent_at) {
            return $delivery;
        }

        if ((int) $delivery->attempts >= $this->maxAttempts()) {
            $delivery->status = 'error';
            $delivery->last_error = $delivery->last_error ?: 'Máximo de tentativas atingido.';
            $delivery->next_retry_at = null;
            $delivery->save();
            $this->appendPresenceSmsSendLog(
                $delivery,
                $triggerSource,
                'error',
                null,
                $delivery->last_http_status,
                (string) $delivery->last_error,
            );

            return $delivery;
        }

        $delivery->attempts = (int) $delivery->attempts + 1;
        $delivery->last_error = null;
        $delivery->last_http_status = null;
        $delivery->save();

        $provider = (string) data_get($smsIntegration->extra, 'provider', config('integrations.sms.default_provider', 'twilio'));
        if ($provider === 'zenvia') {
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
            $this->finalizePresenceSmsSendLog($delivery, $triggerSource);

            return $delivery;
        }

        if ($provider === 'twilio') {
            $resp = (new TwilioSmsClient($smsIntegration))->sendText($to, $from, $message, $eventId);
            $delivery->last_http_status = $resp->status();
            $delivery->provider_response = $resp->json();

            if ($resp->successful()) {
                $delivery->provider_message_id = (string) ($resp->json('sid') ?? '');
                $delivery->sent_at = now();
                $delivery->status = 'sent';
                $delivery->next_retry_at = null;
            } else {
                $delivery->last_error = 'HTTP '.$resp->status().' body='.(string) $resp->body();
                $delivery->status = 'error';
                $delivery->next_retry_at = $delivery->attempts >= $this->maxAttempts() ? null : now()->addSeconds($this->backoffSeconds($delivery->attempts));
            }

            $delivery->save();
            $this->finalizePresenceSmsSendLog($delivery, $triggerSource);

            return $delivery;
        }

        $delivery->last_error = 'Provedor de SMS não suportado: '.$provider;
        $delivery->status = 'error';
        $delivery->next_retry_at = null;
        $delivery->save();
        $this->appendPresenceSmsSendLog(
            $delivery,
            $triggerSource,
            'error',
            null,
            $delivery->last_http_status,
            (string) $delivery->last_error,
        );

        return $delivery;
    }

    /**
     * Regista tentativa concluída em context.send_log (máx. 50 entradas) para auditoria no admin.
     */
    private function finalizePresenceSmsSendLog(SmsDelivery $delivery, string $triggerSource): void
    {
        if ($delivery->status === 'sent') {
            $this->appendPresenceSmsSendLog(
                $delivery,
                $triggerSource,
                'sent',
                $delivery->provider_message_id !== '' ? (string) $delivery->provider_message_id : null,
                $delivery->last_http_status,
                null,
            );

            return;
        }

        if ($delivery->status === 'error' && $delivery->next_retry_at === null) {
            $this->appendPresenceSmsSendLog(
                $delivery,
                $triggerSource,
                'error',
                null,
                $delivery->last_http_status,
                (string) ($delivery->last_error ?? ''),
            );
        }
    }

    private function appendPresenceSmsSendLog(
        SmsDelivery $delivery,
        string $triggerSource,
        string $resultStatus,
        ?string $providerMessageId,
        ?int $httpStatus,
        ?string $errorSnippet,
    ): void {
        $ctx = is_array($delivery->context) ? $delivery->context : [];
        $log = is_array($ctx['send_log'] ?? null) ? array_values($ctx['send_log']) : [];
        $to = (string) $delivery->to;
        $toMask = strlen($to) <= 4
            ? str_repeat('•', strlen($to))
            : str_repeat('•', max(0, strlen($to) - 4)).substr($to, -4);

        $entry = [
            'at' => now()->toIso8601String(),
            'trigger' => $triggerSource,
            'template_key' => (string) $delivery->template_key,
            'to_masked' => $toMask,
            'status' => $resultStatus,
            'message_preview' => mb_substr((string) $delivery->message, 0, 220),
        ];
        if ($providerMessageId !== null && $providerMessageId !== '') {
            $entry['provider_message_id'] = $providerMessageId;
        }
        if ($httpStatus !== null) {
            $entry['http_status'] = $httpStatus;
        }
        if ($errorSnippet !== null && $errorSnippet !== '') {
            $entry['error_snippet'] = mb_substr($errorSnippet, 0, 200);
        }
        $log[] = $entry;
        if (count($log) > 50) {
            $log = array_slice($log, -50);
        }

        $ctx['send_log'] = $log;
        $delivery->context = $ctx;
        $delivery->save();
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
