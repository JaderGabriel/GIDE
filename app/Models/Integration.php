<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conector externo (uma linha por `key` em `integrations`).
 *
 * A coluna `extra` é texto criptografado com payload JSON (cast `encrypted:array`).
 * Não há migração por campo: novas chaves do Gestor/iEducar evoluem só no JSON.
 *
 * Contrato típico de `extra` quando `key` = {@see Integration::KEY_GESTOR}:
 * - `application_key` (string|null)
 * - `auth.username`, `auth.password` (string|null)
 * - `endpoints.enrollment_sync_path` (string|null) — path do POST de convite no SDK
 * - `onboarding.unity_id`, `onboarding.access_profile_id`, `onboarding.condominium_id` (int|null; ids ≤0 ignorados na lógica de negócio)
 * - `defaults.unity_id`, `defaults.access_profile_id` (int|null; gravados pela tela /integracoes/gestor)
 * - `ieducar_processing.environment`: `preview` | `homolog` (rótulo para auditoria; API iEducar = integração {@see Integration::KEY_IEDUCAR})
 * - `catraca_webhook_bearer_hash`, `catraca_webhook_bearer_created_at` (webhook Bearer catraca → GIDE)
 * - `endpoints.face_enroll_path` (opcional), outros endpoints conforme evolução
 *
 * Contrato parcial quando `key` = {@see Integration::KEY_IEDUCAR}: `access_key`, `presence.*`, `catraca_frequencia.*`, etc.
 */
class Integration extends Model
{
    public const KEY_IEDUCAR = 'ieducar';

    public const KEY_GESTOR = 'gestor';

    public const KEY_SMS = 'sms';

    protected $fillable = [
        'key',
        'name',
        'base_url',
        'enabled',
        'auth_type',
        'auth_token',
        'hmac_secret',
        'signature_ttl_seconds',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'signature_ttl_seconds' => 'integer',
            // Campos sensíveis
            'auth_token' => 'encrypted',
            'hmac_secret' => 'encrypted',
            // `extra` pode conter credenciais (ex.: username/password do gestor)
            'extra' => 'encrypted:array',
        ];
    }
}
