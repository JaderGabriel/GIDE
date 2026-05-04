# Eventos de acesso → GIDE

Dois pontos de entrada gravam o mesmo tipo de auditoria (`gestor_access_event_deliveries` + `access_events`) e o mesmo pipeline de preview no iEducar:

| Canal | Rota | Autenticação |
|-------|------|----------------|
| Gestor (SDK) | `POST /api/v1/gestor/access-events` | HMAC: cabeçalhos `X-Event-Id`, `X-Timestamp`, `X-Signature` e segredo em `integrations.hmac_secret` (ver `VerifyHmacSignature`). |
| Catraca (equipamento) | `POST /api/v1/catraca/access-events` | Somente **`Authorization: Bearer <token>`**. O hash do token fica em `integrations.extra.catraca_access_token_hash` (geração em **Integrações → Gestor**). Instalações antigas podem ainda ter `catraca_webhook_bearer_hash` — o middleware aceita ambos. |

Auditoria para TI: **`GET /admin/gestor-access-events`** e detalhe **`GET /admin/gestor-access-events/{id}`**. O campo `inbound_channel` distingue `gestor_hmac` de `catraca_bearer`. O JSON bruto do pedido está em `inbound_payload`.

---

## Catraca — `POST /api/v1/catraca/access-events`

### Cabeçalhos

```
Content-Type: application/json
Authorization: Bearer <token>
```

### Corpo JSON (contrato típico do equipamento)

Campos em **camelCase**. Exemplo válido (note a vírgula após `condominium`):

```json
{
  "eventId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "creationDate": "2026-04-30T14:10:36Z",
  "name": "897555",
  "profile": "guest",
  "place": "Portaria Principal",
  "unity": "Aluno",
  "unityGroup": "Escola",
  "condominium": "Escola xxx",
  "way": "Entrance",
  "accessMedia": "facial"
}
```

| Campo | Obrigatório | Uso |
|--------|-------------|-----|
| `eventId` | Sim | Idempotência (par único com `source=catraca_bearer` em `access_events`). |
| `creationDate` | Recomendado | Data/hora do acesso (`occurred_at`) e motor de presença. |
| `name` | Recomendado | Replicado internamente como `aluno_id` nas regras quando não há `aluno_id` explícito. |
| Demais (`profile`, `place`, `unity`, …) | Não | Persistidos no payload e disponíveis para mapeamentos / análise TI. |

O GIDE normaliza para o motor de presença (`aluno_id` ← `name` se faltar, `type` ← `way` ou `accessMedia`, etc.) sem alterar o JSON guardado em auditoria (mantém o bruto recebido).

### Resposta JSON (sucesso)

HTTP **200**:

```json
{
  "ok": true,
  "created": true,
  "processed": true,
  "delivery_id": 1,
  "eventId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

- `delivery_id`: correlaciona com a lista admin acima.
- `created` / `processed`: mesmo significado que no fluxo HMAC Gestor.

### Erros comuns

| HTTP | Situação |
|------|-----------|
| 401 | Bearer ausente ou token inválido. |
| 403 | Integração Gestor desabilitada. |
| 503 | Token ainda não gerado na UI. |
| 400 | `eventId` ausente ou JSON inválido. |

---

## Gestor — `POST /api/v1/gestor/access-events` (HMAC)

Contrato de assinatura: ver `README.md` (secção HMAC inbound) e `app/Http/Middleware/VerifyHmacSignature.php`.

A resposta inclui `delivery_id` e o canal em auditoria é `gestor_hmac`.

---

## Simulação pela CLI (vários POSTs + auditoria)

Com a integração **gestor** habilitada e o token de acesso já gerado na UI:

```bash
php artisan gide:simulate-catraca-access-events --token='gide_cwc_...'
```

- Por omissão envia **12** pedidos (mínimo **10**). Ajuste com `--count=15`.
- Modo **interno** (default): usa o HTTP kernel da app, sem servidor à escuta.
- Modo **HTTP** (`--http`): chama `APP_URL` (ou `--url=https://...`) — útil para testar nginx/PHP-FPM.
- Alternativa ao `--token`: variável de ambiente `GIDE_CATRACA_ACCESS_TOKEN`.

No fim o comando imprime uma tabela com `delivery_id` por pedido e outra com as linhas em `gestor_access_event_deliveries` (`inbound_channel=catraca_bearer`). Detalhe no admin: `/admin/gestor-access-events`.
