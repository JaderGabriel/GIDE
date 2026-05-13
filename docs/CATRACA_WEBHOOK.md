# Catraca → GIDE: `POST /api/v1/catraca/access-events` (token Bearer)

Documentação do **único** contrato deste ficheiro: o equipamento (catraca) envia eventos de acesso ao GIDE com **`Authorization: Bearer`** e corpo **JSON**. Não aborda o canal HMAC (`POST /api/v1/gestor/access-events`); para esse fluxo ver `README.md` (secção API e HMAC inbound) e `app/Http/Middleware/VerifyHmacSignature.php`.

## URL e método

| Item | Valor |
|------|--------|
| Método | `POST` |
| Caminho | `/api/v1/catraca/access-events` |
| URL completa | `https://<seu-dominio>/api/v1/catraca/access-events` |

## Token de acesso

- **Cabeçalho:** `Authorization: Bearer <token>` (única autenticação deste endpoint).
- **Onde o GIDE guarda o segredo:** na integração **`gestor`** (`integrations.key = gestor`), em `integrations.extra`:
  - **`catraca_access_token_hash`** — preferido (bcrypt do token).
  - **`catraca_access_token_created_at`** — ISO8601 da geração.
- **Legado:** `catraca_webhook_bearer_hash` ainda é aceite pelo middleware até migrar.
- **Geração do token:** Integrações → Gestor → secção da catraca (“Gerar token de acesso”). O valor em **texto claro** só aparece **uma vez** na resposta; na base fica só o hash.

Implementação: `App\Support\GestorCatracaAccessToken`, middleware `verify.catraca.webhook.bearer`, controlador `App\Http\Controllers\Api\CatracaAccessWebhookController`.

**Requisitos operacionais:** integração **gestor** existente e **`enabled=true`**; caso contrário o endpoint responde **403**. Se o token não estiver configurado: **503**.

## Cabeçalhos

```
Content-Type: application/json
Authorization: Bearer <token>
```

## Corpo JSON (equipamento)

Campos em **camelCase**. Exemplo válido (vírgula obrigatória entre propriedades JSON):

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
| `eventId` | Sim | Idempotência em `access_events` com `source=catraca_bearer`. |
| `creationDate` | Recomendado | Data/hora do acesso e motor de presença. **Recomenda-se** sufixo `Z` ou offset (`+03:00`, etc.). Se vier **sem** fuso explícito, o GIDE **assume UTC (±0)** e converte para `APP_TIMEZONE` (ver `App\Support\Presence\AccessEventOccurredAtResolver` e `analysis_json.timestamp_info.interpreted_as_utc` na auditoria admin). |
| `name` | Recomendado | Mapeado para análise como aluno quando não há `aluno_id` explícito. |
| Outros | Não | Persistidos no payload bruto e na auditoria. |

O GIDE **mantém o JSON bruto** em auditoria; para o motor de presença aplica normalização interna (ex.: `aluno_id` a partir de `name`, `type` a partir de `way` / `accessMedia`) sem substituir o payload guardado.

## Resposta JSON (sucesso)

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

- **`delivery_id`:** linha em `gestor_access_event_deliveries` (`inbound_channel = catraca_bearer`).
- **`created`:** novo registo em `access_events` para este `eventId` + origem catraca.
- **`processed`:** pipeline interno (motor + tentativa de preview iEducar quando as condições se cumprem).
- **`queued`:** (quando aplicável) o POST ao iEducar foi enfileirado; o processamento HTTP corre num job (`ProcessGestorAccessEventDeliveryJob`).

## Erros comuns

| HTTP | Situação |
|------|-----------|
| 401 | `Authorization` ausente ou token inválido. |
| 403 | Integração gestor desabilitada. |
| 503 | Token ainda não gerado na UI. |
| 400 | `eventId` ausente ou corpo não JSON. |

## Auditoria (TI)

- Lista: **`GET /admin/gestor-access-events`**
- Detalhe: **`GET /admin/gestor-access-events/{id}`**

Cada POST cria uma linha de auditoria com o JSON recebido. O envio em **preview** ao iEducar (catraca-frequência) depende de integração **ieducar** ativa e do motor de presença; detalhes em `docs/FLUXO_DO_SISTEMA.md` (nota sobre duas filas no admin).
