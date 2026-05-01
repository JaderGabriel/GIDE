# Testes — Parte 1 (IDA): ERP A (iEducar) → GIDE → ERP B (Controle de acessos / catracas)

Este documento descreve **o que precisa ser feito para testar** a **Parte 1 do sistema**: o fluxo **somente de ida** (origem → bridge → destino).

Escopo **incluído**:

- iEducar → GIDE (inbound com HMAC)
- GIDE → Gestor/SDK (outbound com Bearer + ApplicationKey)
- Abertura da UI de facial via **token gerado por API inbound**

Escopo **excluído** (não considerar aqui):

- Gestor → GIDE (webhook de presença/eventos)
- GIDE → iEducar (lançamento de presença/faltas)
- SMS
- callback de `valid_until` para iEducar

---

## 1) Pré‑requisitos

- GIDE rodando (web + API) e banco acessível
- Worker de fila rodando (para jobs outbound quando aplicável)
- Credenciais e endpoints do Gestor disponíveis (ou ambiente de homologação)
- Segredos HMAC configurados para o iEducar no GIDE

---

## 2) Configuração no GIDE (admin)

Faça login no GIDE e configure em:

### 2.1) Integração do iEducar

Tela: `GET /integracoes/ieducar`

Preencher/garantir:

- **Habilitar integração iEducar** (isso liga `verify.hmac:ieducar`)
- **Base URL do iEducar** (se necessário para buscar foto por URL ou por template)
- **access_key** (não é necessário para a Parte 1, mas pode existir no ambiente)
- **Rotacionar/gerar segredo HMAC** (botão)

Anotar:

- `integrations(key=ieducar).hmac_secret` (o secret que o pacote do iEducar usará para assinar)

### 2.2) Integração do Gestor (SDK)

Tela: `GET /integracoes/gestor`

Preencher/garantir:

- **enabled = true**
- **base_url** do SDK
- **application_key** (header obrigatório)
- **auth.username / auth.password** (para `POST /Auth/Signin`)
- **endpoints**:
  - `enrollment_sync_path` (se você for testar o envio “matrícula/aluno → Gestor”)
  - `face_enroll_path` (se você for testar o enroll facial — path real do SDK)

Testes rápidos:

- “**Testar auth**” (deve gravar `integrations.auth_token`)
- “**Testar listagem de Unities**” (opcional, só para validar conectividade)

---

## 3) Subir serviços (ambiente local)

### 3.1) Web/API

No GIDE:

```bash
php artisan serve
```

### 3.2) Worker de fila

Em outro terminal:

```bash
php artisan queue:listen --tries=3 --timeout=0
```

Observação:

- Jobs principais já têm **máximo 3 tentativas** no código.

---

## 4) Teste A — Evento de matrícula/aluno (iEducar → GIDE → Gestor)

Objetivo:

- Validar o inbound `POST /api/v1/ieducar/enrollments` (HMAC)
- Validar que o GIDE cria `enrollment_ingests`
- Validar que o GIDE dispara job e registra `outbound_deliveries` para envio ao Gestor (quando configurado)

### 4.1) Enviar request assinada (simulando iEducar)

Endpoint:

- `POST /api/v1/ieducar/enrollments`

Headers obrigatórios:

- `X-Event-Id`: id único (ex.: UUID)
- `X-Timestamp`: epoch seconds
- `X-Signature`: `HMAC_SHA256(secret, "{timestamp}.{eventId}.{rawBody}")`

Payload mínimo aceito hoje:

```json
{
  "aluno_id": "123",
  "matricula_id": "456",
  "escola_id": "10",
  "instituicao_id": "1",
  "dados": { "exemplo": true }
}
```

### 4.2) O que validar no GIDE

Banco:

- `enrollment_ingests`:
  - existe um registro com `event_id = X-Event-Id`
  - `payload` salvo
- `outbound_deliveries` (se Gestor enabled + enrollment_sync_path configurado):
  - existe registro `integration_key=gestor`, `event_type=enrollment_ingest`, `event_id`
  - `attempts` incrementa
  - `delivered_at` preenchido quando sucesso
  - erro registrado e **para após 3 tentativas**

Fila:

- worker executa `SendEnrollmentToAccessControl`

Gestor:

- confirmar no destino (se houver endpoint real) que os dados chegaram/foram aceitos

---

## 5) Teste B — Geração de token/URL da tela de facial (iEducar → GIDE)

Objetivo:

- Validar o fluxo “pacote do iEducar” gera um **botão por matrícula**
- Ao clicar, iEducar chama o GIDE para gerar uma URL com token

Endpoint:

- `POST /api/v1/ieducar/facial-requests` (HMAC)

Payload (mínimo):

```json
{
  "external_id": "ID_NO_GESTOR_OU_REFERENCIA",
  "aluno_id": "123",
  "matricula_id": "456",
  "photo_url": "<URL absoluta da foto na origem (iEducar ou CDN)>"
}
```

Resposta esperada:

- `ok=true`
- `token`
- `expires_at`
- `url` (formato `/facial/enviar?token=...`)

### O que validar no GIDE

Banco:

- `facial_send_requests`:
  - `event_id` salvo (idempotência)
  - `token` salvo
  - `expires_at` preenchido
  - `payload` contém os dados empacotados

UI:

- Abrir a `url` retornada (logado no GIDE):
  - deve carregar a tela com **campos somente leitura** preenchidos com o payload empacotado
  - deve permitir capturar foto pela câmera

---

## 6) Teste C — Enroll facial (GIDE UI → Gestor)

Objetivo:

- Validar que o GIDE **só envia** ao Gestor quando:
  - a tela foi aberta via token válido
  - o `POST /facial/enviar` inclui `request_token`

### 6.1) Comportamento esperado (segurança)

- Abrir `GET /facial/enviar` **sem token**
  - aparece “**MODO TESTE (admin)**”
  - botão de envio fica desabilitado
  - mesmo tentando POST manual, o backend bloqueia (token inválido/expirado/consumido)

### 6.2) Fluxo real

- Abrir `GET /facial/enviar?token=...` (token válido)
- Capturar foto e enviar

Validar:

- GIDE chama `GestorClient::enrollFace` (multipart stream)
- Se o Gestor responder sucesso:
  - `facial_send_requests.used_at` é preenchido (token consumido)
- Se o Gestor falhar:
  - a UI deve mostrar erro
  - `used_at` permanece vazio (token não consumido)

Dependência:

- `integrations(key=gestor).extra.endpoints.face_enroll_path` precisa estar configurado com o endpoint real do SDK.

---

## 7) Critérios de aceite (Parte 1)

Considere a Parte 1 validada quando:

- Inbound iEducar `enrollments` funciona com HMAC e persiste auditoria.
- Outbound matrícula/aluno ao Gestor:
  - tenta via fila
  - registra sucesso/erro
  - **para após 3 tentativas**
- Inbound iEducar `facial-requests` gera token e persiste `facial_send_requests`.
- UI de facial:
  - funciona via token
  - bloqueia modo teste/admin
  - consome token no sucesso
  - envia stream ao Gestor sem persistir imagem
