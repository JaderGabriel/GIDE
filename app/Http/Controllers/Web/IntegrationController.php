<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\SmsTemplate;
use App\Services\Gestor\GestorClient;
use App\Services\UserAuditLogger;
use App\Support\BrPhoneNormalizer;
use App\Support\GestorSigninProbeCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    public function catracaFrequencia(Request $request)
    {
        // Tela foi unificada em /integracoes/ieducar
        return redirect()->route('integrations.ieducar');
    }

    public function updateCatracaFrequencia(Request $request)
    {
        // Mantemos a rota por compatibilidade, mas atualiza via tela unificada.
        return redirect()->route('integrations.ieducar');
    }

    public function ieducar(Request $request)
    {
        $integration = Integration::query()->firstOrCreate(
            ['key' => 'ieducar'],
            ['name' => 'iEducar 2.11', 'enabled' => false, 'auth_type' => 'none'],
        );

        $catracaIntegration = Integration::query()->firstOrCreate(
            ['key' => 'catraca_frequencia'],
            ['name' => 'Catraca/Frequência (iEducar → GIDE)', 'enabled' => false, 'auth_type' => 'bearer'],
        );

        return view('integrations.ieducar', [
            'integration' => $integration,
            'catraca_integration' => $catracaIntegration,
        ]);
    }

    public function updateIeducar(Request $request)
    {
        $integration = Integration::query()->where('key', 'ieducar')->firstOrFail();
        $catracaIntegration = Integration::query()->firstOrCreate(
            ['key' => 'catraca_frequencia'],
            ['name' => 'Catraca/Frequência (iEducar → GIDE)', 'enabled' => false, 'auth_type' => 'bearer'],
        );

        try {
            $data = $request->validate([
                // GIDE → iEducar
                'enabled' => ['nullable'], // legacy: habilitar inbound HMAC antigo do iEducar
                'base_url' => ['nullable', 'string'],
                'access_key' => ['nullable', 'string'],
                'signature_ttl_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
                'api_token' => ['nullable', 'string'],
                'catraca_frequencia_confirmacao_token' => ['nullable', 'string'],
                // iEducar → GIDE (Catraca/Frequência inbound)
                'catraca_enabled' => ['nullable'],
                'catraca_inbound_token' => ['nullable', 'string'],
            ]);

            $integration->enabled = (bool) $request->boolean('enabled');
            $integration->base_url = $data['base_url'] !== '' ? $data['base_url'] : null;
            $integration->signature_ttl_seconds = (int) $data['signature_ttl_seconds'];

            $extra = (array) ($integration->extra ?? []);
            $extra['access_key'] = $data['access_key'] !== '' ? $data['access_key'] : null;
            $extra['catraca_frequencia'] = array_merge((array) ($extra['catraca_frequencia'] ?? []), [
                // token dedicado para callback/consulta do package (se vazio, usa auth_token)
                'confirmacao_token' => ($data['catraca_frequencia_confirmacao_token'] ?? '') !== '' ? $data['catraca_frequencia_confirmacao_token'] : (data_get($extra, 'catraca_frequencia.confirmacao_token')),
            ]);
            $integration->extra = $extra;

            // token principal para API do iEducar (fallback para confirmação/consulta). Se vazio, não altera.
            $integration->auth_type = 'bearer';
            if (($data['api_token'] ?? '') !== '') {
                $integration->auth_token = $data['api_token'];
            }

            $integration->save();

            // Atualiza iEducar → GIDE (Bearer inbound do pacote)
            $catracaIntegration->enabled = (bool) $request->boolean('catraca_enabled');
            $catracaIntegration->auth_type = 'bearer';
            if (($data['catraca_inbound_token'] ?? '') !== '') {
                $catracaIntegration->auth_token = $data['catraca_inbound_token'];
            }
            $catracaIntegration->save();
        } catch (\Throwable $e) {
            return back()->withErrors(['api_token' => $e->getMessage()]);
        }

        UserAuditLogger::recordAuthenticated('integration.ieducar.updated', [
            'ieducar_enabled' => (bool) $integration->enabled,
            'catraca_frequencia_enabled' => (bool) $catracaIntegration->enabled,
        ], 'integration', $integration->id);

        return redirect('/dashboard')->with('status', 'Integração iEducar atualizada.');
    }

    public function rotateIeducarHmac(Request $request)
    {
        $integration = Integration::query()->where('key', 'ieducar')->firstOrFail();

        // 32 bytes => 43/44 chars base64, bom para secret
        $integration->hmac_secret = base64_encode(random_bytes(32));
        $integration->save();

        UserAuditLogger::recordAuthenticated('integration.ieducar.hmac_rotated', [], 'integration', $integration->id);

        return back()->with('status', 'Segredo HMAC do iEducar gerado/rotacionado.');
    }

    public function gestor(Request $request)
    {
        $integration = Integration::query()->firstOrCreate(
            ['key' => 'gestor'],
            [
                'name' => 'Gestor (Porter/Kiper SDK)',
                'enabled' => false,
                'auth_type' => 'bearer',
                'base_url' => null,
            ],
        );

        return view('integrations.gestor', [
            'integration' => $integration,
            'catracaWebhookUrl' => url('/api/v1/catraca/access-events'),
            'catracaWebhookBearerConfigured' => filled(data_get($integration->extra, 'catraca_webhook_bearer_hash')),
        ]);
    }

    /**
     * Gera token Bearer para o webhook da catraca; o valor em claro só aparece nesta resposta (flash).
     */
    public function generateGestorCatracaWebhookBearer(Request $request)
    {
        $integration = Integration::query()->where('key', 'gestor')->firstOrFail();

        $plain = 'gide_cwc_'.Str::lower(Str::random(40));

        $extra = (array) ($integration->extra ?? []);
        $extra['catraca_webhook_bearer_hash'] = Hash::make($plain);
        $extra['catraca_webhook_bearer_created_at'] = now()->toIso8601String();
        $integration->extra = $extra;
        $integration->save();

        UserAuditLogger::recordAuthenticated('integration.gestor.webhook_bearer_generated', [], 'integration', $integration->id);

        return back()
            ->with('status', 'Novo token do webhook da catraca gerado. Copie abaixo agora: ele não voltará a ser exibido.')
            ->with('status_level', 'success')
            ->with('gestor_catraca_webhook_bearer_plaintext', $plain);
    }

    public function updateGestor(Request $request)
    {
        $integration = Integration::query()->where('key', 'gestor')->firstOrFail();

        try {
            $data = $request->validate([
                'enabled' => ['nullable'],
                'base_url' => ['nullable', 'string'],
                'application_key' => ['nullable', 'string'],
                'auth_username' => ['nullable', 'string'],
                'auth_password' => ['nullable', 'string'],
                'signature_ttl_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
                'outbound_enrollment_path' => ['nullable', 'string'],
                'unity_id' => ['nullable', 'string', 'max:32', 'regex:/^[0-9]*$/'],
                'access_profile_id' => ['nullable', 'string', 'max:32', 'regex:/^[0-9]*$/'],
                'ieducar_processing_environment' => ['required', Rule::in(['preview', 'homolog'])],
            ]);

            $integration->enabled = (bool) $request->boolean('enabled');
            $integration->base_url = $data['base_url'] !== '' ? $data['base_url'] : null;
            $integration->signature_ttl_seconds = (int) $data['signature_ttl_seconds'];

            $extra = (array) ($integration->extra ?? []);
            $extra['application_key'] = $data['application_key'] !== '' ? $data['application_key'] : null;
            $authPrev = (array) ($extra['auth'] ?? []);
            $extra['auth'] = [
                'username' => $data['auth_username'] !== '' ? $data['auth_username'] : null,
                'password' => ($data['auth_password'] ?? '') !== '' ? $data['auth_password'] : ($authPrev['password'] ?? null),
            ];
            $pathRaw = $data['outbound_enrollment_path'] ?? null;
            $enrollmentSyncPath = null;
            if (is_string($pathRaw) && trim($pathRaw) !== '') {
                $enrollmentSyncPath = trim($pathRaw);
            }
            $extra['endpoints'] = array_merge((array) ($extra['endpoints'] ?? []), [
                'enrollment_sync_path' => $enrollmentSyncPath,
            ]);

            $defaults = (array) ($extra['defaults'] ?? []);
            $unityIn = trim((string) ($data['unity_id'] ?? ''));
            if ($unityIn !== '' && (int) $unityIn > 0) {
                $defaults['unity_id'] = (int) $unityIn;
            } else {
                unset($defaults['unity_id']);
            }
            $profileIn = trim((string) ($data['access_profile_id'] ?? ''));
            if ($profileIn !== '' && (int) $profileIn > 0) {
                $defaults['access_profile_id'] = (int) $profileIn;
            } else {
                unset($defaults['access_profile_id']);
            }
            if ($defaults === []) {
                unset($extra['defaults']);
            } else {
                $extra['defaults'] = $defaults;
            }

            $extra['ieducar_processing'] = [
                'environment' => $data['ieducar_processing_environment'],
            ];

            $integration->extra = $extra;

            // invalidar token para forçar reauth quando credenciais mudarem
            $integration->auth_type = 'bearer';
            $integration->auth_token = null;

            $integration->save();
        } catch (\Throwable $e) {
            return back()->withErrors(['base_url' => $e->getMessage()]);
        }

        UserAuditLogger::recordAuthenticated('integration.gestor.updated', [
            'enabled' => (bool) $integration->enabled,
            'ieducar_processing_environment' => (string) data_get($integration->extra, 'ieducar_processing.environment', ''),
        ], 'integration', $integration->id);

        return redirect()
            ->route('integrations.gestor')
            ->with('status', 'Configuração Gestor salva no banco (SDK, convite, ambiente de presença e demais campos do formulário).')
            ->with('status_level', 'success');
    }

    public function rotateGestorHmac(Request $request)
    {
        $integration = Integration::query()->where('key', 'gestor')->firstOrFail();

        $integration->hmac_secret = base64_encode(random_bytes(32));
        $integration->save();

        UserAuditLogger::recordAuthenticated('integration.gestor.hmac_rotated', [], 'integration', $integration->id);

        return back()
            ->with('status', 'Segredo HMAC do Gestor gerado/rotacionado.')
            ->with('status_level', 'success');
    }

    public function testGestorAuth(Request $request)
    {
        $integration = Integration::query()->where('key', 'gestor')->firstOrFail();

        try {
            (new GestorClient($integration))->signIn();
            GestorSigninProbeCache::recordSuccess();
        } catch (\Throwable $e) {
            GestorSigninProbeCache::recordFailure();

            UserAuditLogger::recordAuthenticated('integration.gestor.test_auth', [
                'ok' => false,
                'error' => $e->getMessage(),
            ], 'integration', $integration->id);

            return back()->with([
                'status' => 'Falha no auth do Gestor: '.$e->getMessage(),
                'status_level' => 'error',
            ]);
        }

        UserAuditLogger::recordAuthenticated('integration.gestor.test_auth', [
            'ok' => true,
        ], 'integration', $integration->id);

        return back()->with([
            'status' => 'Auth do Gestor OK (token atualizado).',
            'status_level' => 'success',
        ]);
    }

    public function testGestorUnities(Request $request)
    {
        $integration = Integration::query()->where('key', 'gestor')->firstOrFail();

        try {
            $condominiumId = data_get($integration->extra, 'onboarding.condominium_id');
            $client = new GestorClient($integration);
            $resp = $condominiumId
                ? $client->listUnitiesByCondominium((string) $condominiumId)
                : $client->listUnitiesAll();

            if (! $resp->successful()) {
                UserAuditLogger::recordAuthenticated('integration.gestor.test_unities', [
                    'ok' => false,
                    'http_status' => $resp->status(),
                ], 'integration', $integration->id);

                return back()->with([
                    'status' => 'Falha ao listar Unities. HTTP '.$resp->status(),
                    'status_level' => 'error',
                ]);
            }

            $count = is_array($resp->json()) ? count($resp->json()) : null;
        } catch (\Throwable $e) {
            UserAuditLogger::recordAuthenticated('integration.gestor.test_unities', [
                'ok' => false,
                'error' => $e->getMessage(),
            ], 'integration', $integration->id);

            return back()->with([
                'status' => 'Erro ao listar Unities: '.$e->getMessage(),
                'status_level' => 'error',
            ]);
        }

        UserAuditLogger::recordAuthenticated('integration.gestor.test_unities', [
            'ok' => true,
            'items' => $count,
        ], 'integration', $integration->id);

        return back()->with([
            'status' => 'Unities OK'.($count !== null ? ' (itens: '.$count.')' : '').'.',
            'status_level' => 'success',
        ]);
    }

    public function sms(Request $request)
    {
        $integration = Integration::query()->firstOrCreate(
            ['key' => 'sms'],
            ['name' => 'SMS', 'enabled' => false, 'auth_type' => 'api_token', 'base_url' => (string) config('integrations.sms.default_base_url')],
        );

        $template = SmsTemplate::query()->firstOrCreate(
            ['key' => 'presence_notification'],
            ['name' => 'Presença registrada', 'body' => 'Presença registrada em {{date}} às {{time}}. Aluno: {{aluno_id}}. Matrícula: {{matricula_id}}.', 'enabled' => true],
        );

        $testPhones = (array) data_get($integration->extra, 'test_phone_numbers', []);
        $testPhoneLines = collect($testPhones)->filter(fn ($v) => is_string($v) && $v !== '')->implode("\n");

        return view('integrations.sms', [
            'integration' => $integration,
            'template' => $template,
            'testPhoneNumbersDisplay' => $testPhoneLines,
        ]);
    }

    public function updateSms(Request $request)
    {
        $integration = Integration::query()->where('key', 'sms')->firstOrFail();
        $template = SmsTemplate::query()->where('key', 'presence_notification')->firstOrFail();

        try {
            $data = $request->validate([
                'enabled' => ['nullable'],
                'base_url' => ['nullable', 'string'],
                'api_token' => ['nullable', 'string'],
                'from' => ['nullable', 'string'],
                'payload_phone_key' => ['nullable', 'string'],
                'sms_recipient_mode' => ['required', Rule::in(['alunos', 'test_numbers'])],
                'test_phone_numbers' => ['nullable', 'string'],
                'template_enabled' => ['nullable'],
                'template_body' => ['required', 'string', 'min:1'],
            ]);

            $integration->enabled = (bool) $request->boolean('enabled');
            $integration->base_url = $data['base_url'] !== '' ? $data['base_url'] : (string) config('integrations.sms.default_base_url');
            $integration->auth_type = 'api_token';

            if ($data['api_token'] !== '') {
                $integration->auth_token = $data['api_token'];
            }

            $extra = (array) ($integration->extra ?? []);
            $extra['provider'] = 'zenvia';
            $extra['from'] = $data['from'] !== '' ? $data['from'] : null;
            $extra['sms_recipient_mode'] = $data['sms_recipient_mode'];
            $testPhones = BrPhoneNormalizer::parseLinesToE164((string) ($data['test_phone_numbers'] ?? ''));
            if ($data['sms_recipient_mode'] === 'test_numbers' && $testPhones === []) {
                return back()->withErrors(['test_phone_numbers' => 'Informe ao menos um número válido (DDI+DDD+número, um por linha).'])->withInput();
            }
            $extra['test_phone_numbers'] = $testPhones;
            $extra['payload_map'] = array_merge((array) ($extra['payload_map'] ?? []), [
                'phone' => $data['payload_phone_key'] !== '' ? $data['payload_phone_key'] : 'phone',
            ]);
            $integration->extra = $extra;
            $integration->save();

            $template->enabled = (bool) $request->boolean('template_enabled');
            $template->body = $data['template_body'];
            $template->save();
        } catch (\Throwable $e) {
            return back()->withErrors(['api_token' => $e->getMessage()]);
        }

        UserAuditLogger::recordAuthenticated('integration.sms.updated', [
            'enabled' => (bool) $integration->enabled,
            'recipient_mode' => (string) data_get($integration->extra, 'sms_recipient_mode', ''),
        ], 'integration', $integration->id);

        return redirect('/dashboard')->with('status', 'Configuração de SMS atualizada.');
    }
}
