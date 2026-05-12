# GIDE — Fluxo do sistema (ponta‑a‑ponta)

Este documento descreve o **fluxo completo de dados** entre iEducar ↔ GIDE ↔ Gestor (Porter/Kiper SDK) e integrações auxiliares (SMS), indicando:

- **o que já foi implementado**
- **o que ainda falta** para o fluxo operacional completo (dependências de endpoints/documentação externa)
- **endpoints internos** do GIDE e **comunicações externas**

---

## Visão geral (alto nível)

O GIDE atua como **ponte** entre:

- **iEducar (origem)**: matrícula/aluno e contexto para presença
- **Gestor/SDK (destino)**: controle de acesso e eventos
- **SMS (provedor configurável)**: notificação pós-presença (opcional)

Premissas:

- **Tráfego via API** (sem acesso direto ao banco de sistemas externos)
- **Imagem facial não é persistida** no GIDE (trafega em stream/memória)
- **Segurança inbound por HMAC** (iEducar→GIDE e Gestor→GIDE)
- **Configurações/tokens** centralizados na tabela `integrations`

---

## Fluxograma de dados (Mermaid)

```mermaid
flowchart TD
  subgraph iEducar[iEducar 2.11]
    iEnroll[Evento matrícula/aluno]
    iBtn[Botão "Enviar facial"]
    iApiNew[API iEducar (nova) — validade facial]
    iApiLegacy[API iEducar (module/Api)]
  end

  subgraph GIDE[GIDE (Laravel 13)]
    gUi[Tela /facial/enviar]
    gInEnroll[POST /api/v1/ieducar/enrollments]
    gInAccess[POST /api/v1/gestor/access-events]
    gStore[(DB: ingests/events/deliveries)]
    gPresence[PresenceRuleEngine + PresenceMarker]
    gOutGestor[Outbound → Gestor (sync pessoa)]
    gOutSms[Outbound → API SMS]
    gOutIeducar[Outbound → iEducar (presença + validade facial)]
  end

  subgraph Gestor[Gestor (Porter/Kiper SDK)]
    mSdk[SDK API]
    mEvt[Eventos de acesso/presença]
  end

  subgraph SmsApi[API SMS (base_url da integração)]
    zSms[POST /channels/sms/messages]
  end

  iEnroll -->|"POST JSON + HMAC"| gInEnroll
  gInEnroll --> gStore
  gInEnroll -->|"Job"| gOutGestor

  iBtn -->|"Abre"| gUi
  gUi -->|"stream foto"| mSdk
  mSdk -->|"OK"| gOutIeducar
  gOutIeducar -->|"POST (Bearer) valid_until"| iApiNew

  mEvt -->|"POST JSON + HMAC"| gInAccess
  gInAccess --> gStore
  gInAccess --> gPresence
  gPresence -->|"POST (access_key) faltas/presença"| iApiLegacy
  gPresence -->|"Job"| gOutSms
  gOutSms --> zSms
```

---

## Endpoints do GIDE (internos)

### Web (sessão)

- `GET /login` / `POST /login` (login por `username`)
- `POST /logout`
- `GET /dashboard` (auth)
- `GET /facial/enviar` (auth) — UI abre câmera e captura em memória
- `POST /facial/enviar` (auth) — **somente via token do iEducar**: envia stream ao Gestor e opcionalmente chama callback de validade no iEducar  
  - Se aberto sem token: **modo teste** (admin) e envio bloqueado

### Admin (auth + admin)

- `GET /integracoes/ieducar` / `POST /integracoes/ieducar`
- `POST /integracoes/ieducar/rotacionar-hmac`
- `GET /integracoes/gestor` / `POST /integracoes/gestor`
- `POST /integracoes/gestor/rotacionar-hmac`
- `POST /integracoes/gestor/testar-auth`
- `POST /integracoes/gestor/testar-unities`
- `GET /integracoes/sms` / `POST /integracoes/sms`
- `GET /sms` (lista e filtros)
- `GET /sms/{id}` (detalhe)

### API (v1) — inbound (HMAC)

- `POST /api/v1/ieducar/enrollments`
  - **Auth**: `verify.hmac:ieducar`
  - **Persistência**: `enrollment_ingests`
  - **Outbound**: dispara job de envio ao Gestor (se configurado)
- `POST /api/v1/ieducar/facial-requests`
  - **Auth**: `verify.hmac:ieducar`
  - **Objetivo**: criar uma requisição de envio facial (token + URL) para abrir a tela do GIDE
  - **Persistência**: `facial_send_requests` (idempotência por `event_id`, expiração e consumo)
- `POST /api/v1/gestor/access-events`
  - **Auth**: `verify.hmac:gestor` (`X-Event-Id`, `X-Timestamp`, `X-Signature` + corpo JSON assinado; ver `VerifyHmacSignature`)
  - **Persistência**: `access_events` e auditoria `gestor_access_event_deliveries` (resposta com `delivery_id`; admin `GET /admin/gestor-access-events`)
  - **Processamento**: motor de presença (`PresenceRuleEngine`) com 4 modos configuráveis (`auto`, `always_mark`, `explicit_only`, `disabled`) — configuração via `/integracoes/gestor`, seção "Motor de presença". Documentação: `docs/MOTOR_PRESENCA.md`.
  - **Notificação**: job de SMS (se integração SMS habilitada)
  - **Catraca (token)**: `POST /api/v1/catraca/access-events` — Bearer + JSON equipamento; mesma tabela de auditoria `gestor_access_event_deliveries` (`inbound_channel=catraca_bearer`).

> **Duas filas no admin (não confundir):** `/admin/gestor-access-events` mostra `gestor_access_event_deliveries` — uma linha por POST do webhook Gestor/catraca. O POST ao iEducar (catraca-frequência) **só é tentado** se existir integração `ieducar` com **`enabled=true`** e o motor de presença devolver **`action=mark_presence`** com `cod_aluno` válido. O comportamento depende do modo configurado: `auto` (janelas de horário), `always_mark` (marca sempre), `explicit_only` (exige `action.mark_presence=true`), `disabled` (nunca marca). Em qualquer modo, `action.mark_presence=false` bloqueia presença. Já `/admin/frequencia-ieducar` lista **`ieducar_frequencia_registro_deliveries`** (registros enfileirados por outro fluxo, ex. comando Artisan ou job); uma pode mostrar sucessos enquanto a outra, para o mesmo dia, mostra “sem POST” se o evento de acesso não cumpriu as condições acima ou foi processado quando o iEducar estava desligado.

---

## Comunicações externas (GIDE → sistemas)

### 1) GIDE → Gestor (SDK)

Implementado:

- **Auth**: `POST {base}/Auth/Signin` (username/password) → `auth_token`
- **Requests autenticados**: `Authorization: Bearer ...` + `ApplicationKey: ...` (com retry de 401 + reauth 1x)
- **Unity**: `GET {base}/SDK/Unity`, `GET {base}/SDK/unity/{id}`

Parcial/depende de endpoint final:

- **Enroll facial** (`GestorClient::enrollFace`)
  - Endpoint configurável em `integrations(key=gestor).extra.endpoints.face_enroll_path`
  - Envio em **multipart** com stream (sem persistir arquivo)
  - **Falta** confirmar o path e o schema real do SDK

### 2) GIDE → iEducar (legacy module/Api)

Implementado (por `access_key`):

- `POST /module/Api/Diario?oper=post&resource=faltas-geral`
- `POST /module/Api/Diario?oper=post&resource=faltas-por-componente`

Limitação atual:

- o `PresenceMarker` exige `instituicao_id`, `etapa` e `turmas[...]` completos; se faltarem, ele **skipa** com motivo.

### 3) GIDE → iEducar (API nova — validade do facial)

Implementado (configurável):

- Callback pós-enroll facial no Gestor, enviando `valid_until` (ISO8601)
- **Auth**: Bearer token (`integrations(key=ieducar).auth_token`)
- **Endpoint**: `integrations(key=ieducar).extra.facial.validity_path`
- **Validade padrão**: `integrations(key=ieducar).extra.facial.validity_days`

Observação:

- Se `validity_path` ou `auth_token` estiverem vazios, o callback é **ignorado** (não quebra o envio).

### 4) GIDE → SMS

Implementado:

- `POST {base_url}/channels/sms/messages` — `base_url` vem de `integrations(key=sms).base_url` ou de `SMS_DEFAULT_BASE_URL` / `config/integrations.php`
- **Auth**: `X-API-TOKEN` (`integrations(key=sms).auth_token`)
- Template com tags (`sms_templates`) e auditoria (`sms_deliveries`)

Status:

- o sistema registra se a requisição foi **aceita pela API** (`sent`) ou falhou (`error`)
- **não** há confirmação “entregue ao handset” (faltaria delivery report/webhook do provedor)

---

## Persistência/auditoria (DB)

Implementado:

- `integrations`: URLs, tokens, credenciais (via `extra`) e segredos HMAC
- `enrollment_ingests`: eventos inbound do iEducar (idempotência por `event_id`)
- `access_events`: eventos inbound do Gestor (idempotência por `event_id`) + `analysis`
- `outbound_deliveries`: auditoria/retry de envio outbound para Gestor (matrícula)
- `facial_send_requests`: requisições de envio facial criadas pelo iEducar (token/URL, expiração, consumo)
- `sms_templates`: templates editáveis com tags
- `sms_deliveries`: auditoria de SMS (status/HTTP/contexto/resposta)

Observação de segurança:

- `integrations.auth_token`, `integrations.hmac_secret` e `integrations.extra` são armazenados **criptografados** (Laravel encrypted casts).

---

## O que foi feito vs o que falta (checklist lógico)

### Implementado (MVP funcional parcial)

- HMAC inbound em `/api/v1/*`
- UI admin de integrações (iEducar/Gestor/SMS) e rotação de segredos
- UI de envio facial via câmera (captura em memória) + fallback via URL, **com envio permitido apenas via token do iEducar**
- Pipeline de eventos do Gestor → análise de janela → tentativa de marcar presença
- Notificação por SMS após “mark_presence” (dependendo do payload ter telefone)
- Callback para iEducar com `valid_until` pós-enroll facial (quando configurado)
- Processamento assíncrono com **máximo de 3 tentativas** (jobs) para outbound de matrícula e SMS

### Falta / depende de documentação externa (para operação completa)

Gestor (SDK):

- endpoints oficiais para: **Invite/Guest** (criar/atualizar/remover) e **facial enroll/update/delete**
- payload e contrato do webhook de eventos (campos, telefone do responsável, id do aluno/matrícula, timezone)

iEducar:

- endpoint “API nova” para receber `valid_until` (definir schema/headers/response)
- enriquecimento do payload de presença com dados obrigatórios (instituição/etapa/turmas)

SMS:

- opcional: implementar **delivery report** (webhook) para status real de entrega
