# Webhook da catraca → GIDE (JSON + Bearer)

Este fluxo complementa o webhook com **HMAC** (`POST /api/v1/gestor/access-events`). Aqui a catraca envia um **JSON** e autentica com **Bearer token** gerado na tela **Integrações → Gestor**.

## URL e método

| Item | Valor |
|------|--------|
| Método | `POST` |
| Caminho | `/api/v1/catraca/access-events` |
| URL completa | `https://<seu-dominio>/api/v1/catraca/access-events` |

## Cabeçalhos obrigatórios

```
Content-Type: application/json
Authorization: Bearer <token>
```

O `<token>` é o valor mostrado **uma única vez** após clicar em **Gerar token do webhook** (ou **Gerar novo token**) em `/integracoes/gestor`. Na base guarda-se apenas o **hash** (bcrypt); não é possível recuperar o texto depois.

## Corpo JSON (exemplo)

Campos em **camelCase** (como no equipamento). Todos os exemplos abaixo são JSON **válido** (atenção às vírgulas).

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

### Campos

| Campo | Obrigatório | Descrição |
|--------|-------------|-----------|
| `eventId` | Sim | UUID ou identificador único do evento (idempotência). |
| `creationDate` | Recomendado | ISO-8601; usado como data/hora do acesso nas regras de presença. |
| `name` | Recomendado | Identificador do visitante/aluno; o GIDE replica para `aluno_id` nas regras de presença quando não há `aluno_id` explícito. |
| `profile`, `place`, `unity`, `unityGroup`, `condominium`, `way`, `accessMedia` | Não | Persistidos no `payload` e podem ser usados em mapeamentos futuros. |

## Resposta JSON (sucesso)

HTTP **200**:

```json
{
  "ok": true,
  "created": true,
  "processed": false,
  "eventId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

- `created`: `true` se o evento foi **criado** agora; `false` se já existia (mesmo `eventId` + origem `catraca_bearer`).
- `processed`: `true` se o motor de presença já analisou o evento (depende de integração iEducar habilitada e regras).

## Erros comuns

| HTTP | Corpo típico |
|------|----------------|
| 401 | `{"message":"Token inválido."}` ou Bearer ausente |
| 403 | Integração Gestor desabilitada |
| 503 | Token do webhook ainda não gerado na UI |
| 400 | `eventId` ausente ou JSON inválido |

## Geração do token (admin)

1. Aceda a **Integrações → Gestor** (`/integracoes/gestor`).
2. Garanta que a integração Gestor está **habilitada** e gravada.
3. Em **Webhook JSON da catraca (Bearer)**, clique em **Gerar token do webhook**.
4. Copie o valor do campo **Bearer (uso único na tela)** e configure na catraca.
5. Voltar à página **não** mostra o token outra vez; para rotacionar, use **Gerar novo token** (invalida o anterior).

## HMAC vs Bearer

| Autenticação | Rota | Uso típico |
|--------------|------|------------|
| HMAC + cabeçalhos `X-Signature`, `X-Timestamp`, `X-Event-Id` | `POST /api/v1/gestor/access-events` | Integrações que assinam o corpo |
| Bearer | `POST /api/v1/catraca/access-events` | Equipamentos que só enviam JSON + token fixo |

Ambos gravam em `access_events` com `source` distinto (`gestor` vs `catraca_bearer`) para o mesmo `eventId` poder coexistir apenas se for explicitamente o mesmo identificador em sistemas diferentes — em geral use **um** canal por ambiente.

## Presença / SMS

O processamento replica a lógica do webhook Gestor: integração **iEducar** habilitada, janelas em `integrations.extra.presence` e disparo de SMS quando a ação for `mark_presence`. O campo `name` é mapeado para `aluno_id` interno quando útil; pode afinar mapeamentos com `payload_map` na configuração de presença do iEducar.
