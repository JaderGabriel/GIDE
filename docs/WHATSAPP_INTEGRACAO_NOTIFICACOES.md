# GIDE — Integração WhatsApp e envio de notificações

Este documento descreve o **objetivo**, o **estado atual no código**, o **fluxo de notificação já existente (SMS)** como referência, e uma **linha de implementação sugerida** para WhatsApp (API oficial / BSP), alinhada ao desenho descrito na área de integrações da aplicação.

---

## 1. Estado atual no GIDE

| Canal | Estado | Onde aparece |
|--------|--------|----------------|
| **SMS** | Implementado (provedor Zenvia v2) | `GET /integracoes/sms`, fila `SendPresenceSms`, tabela `sms_deliveries`, admin `GET /sms` |
| **WhatsApp** | **Planejado** (sem cliente HTTP nem job no repositório) | Cartão “WhatsApp (rascunho)” em `resources/views/integrations/overview.blade.php` — mesmo objetivo do SMS: notificar após presença, com fila e auditoria no servidor |

Ou seja: **hoje as notificações pós-presença saem apenas por SMS**, quando a integração `sms` está habilitada e o template `presence_notification` está ativo.

---

## 2. Objetivo de negócio

Enviar **mensagens transacionais** aos responsáveis (ou números de teste) quando o GIDE concluir o fluxo de **marcação de presença** no iEducar a partir de um evento de acesso (webhook Gestor **HMAC** ou catraca **Bearer**), de forma:

- **Assíncrona** (fila), para não bloquear o webhook inbound.
- **Auditável** (registro por `event_id`, tentativas, último erro, HTTP).
- **Configurável** na tabela `integrations` (como já ocorre com SMS).

O WhatsApp, quando existir, deve seguir **os mesmos princípios**: mensagem montada no servidor, envio via provedor, rastreio e retries coordenados com o agendador/fila do Laravel (ver `docs/FLUXO_DO_SISTEMA.md` e `App\Services\Integrations\DeliveryRetryDispatcher`).

---

## 3. Quando a notificação dispara (hoje: SMS)

O job `App\Jobs\SendPresenceSms` é enfileirado **somente** quando:

1. O evento de acesso é **novo** (`wasRecentlyCreated` no registro do ingest).
2. A integração **iEducar** está habilitada (para análise e marcação).
3. O resultado da análise tem `action === 'mark_presence'` (presença dentro da janela configurada e fluxo que marca falta/presença).
4. A integração com `key = sms` está **habilitada** (`enabled = true`).

Pontos de entrada que disparam o mesmo job (com `eventId`, `payload`/`normalized`, `analysis` e `occurred_at`):

- `POST /api/v1/gestor/access-events` (HMAC) — `GestorWebhookController` → `GestorAccessEventWebhookService`
- `POST /api/v1/catraca/access-events` (Bearer) — `CatracaAccessWebhookController` → o mesmo `GestorAccessEventWebhookService` (canal `catraca_bearer`; SMS quando `mark_presence` e evento novo, como no HMAC)

Para o **WhatsApp futuro**, o gatilho deve ser **idêntico** (mesmo momento do pipeline), evitando duplicar regras de negócio: ou um job orquestra SMS + WhatsApp, ou dois jobs distintos escutam o mesmo critério, com idempotência por `event_id` + canal.

---

## 4. Referência: como o SMS funciona (modelo a espelhar)

### 4.1 Configuração

- Registro em `integrations` com `key = sms` (criado/atualizado em `IntegrationController::sms` / `updateSms`).
- Campos relevantes:
  - `base_url` — base da API (padrão `config('integrations.sms.default_base_url')`, tipicamente `https://api.zenvia.com/v2`).
  - `auth_token` — token enviado em `X-API-TOKEN`.
  - `extra`: `provider` (`zenvia`), `from`, `sms_recipient_mode` (`alunos` | `test_numbers`), `test_phone_numbers`, `payload_map.phone` (chave no JSON do evento para o telefone do responsável).

### 4.2 Template e corpo da mensagem

- Modelo `App\Models\SmsTemplate`, chave `presence_notification`.
- Corpo com placeholders interpretados por `App\Services\Sms\SmsTemplateRenderer` (ex.: `{{date}}`, `{{time}}`, `{{aluno_id}}`, `{{matricula_id}}`, `{{event_id}}`, `{{window}}`, `{{event_type}}`).
- Contexto montado em `App\Services\Sms\SmsService::sendPresenceSmsToRecipient`.

### 4.3 Envio e persistência

- `SmsService::sendPresenceSms` resolve destinatários (`BrPhoneNormalizer::toE164Digits`), cria/atualiza `SmsDelivery` (`firstOrCreate` por `event_id` + `template_key` + `to`), chama `ZenviaSmsClient::sendText` (`POST .../channels/sms/messages`).
- Retries: `attempts`, `next_retry_at`, `max_attempts` em `config/gide.php` — reprocessamento via `DeliveryRetryDispatcher` / `schedule` (ver `routes/console.php`).

### 4.4 UI administrativa

- Configuração: `GET/POST /integracoes/sms`.
- Auditoria: `GET /sms`, `GET /sms/{id}` (`SmsDeliveryController`).

Este desenho é o **contrato funcional** que a documentação de WhatsApp assume como “já validado” no produto.

---

## 5. WhatsApp: requisitos do canal (API oficial)

Independentemente do BSP (Twilio, Zenvia, 360dialog, Gupshup, etc.), a **WhatsApp Business Platform (Cloud API da Meta)** impõe:

1. **Número de negócio** e **conta WABA** aprovados.
2. **Mensagens iniciadas pelo negócio** fora da janela de atendimento de 24 horas exigem **templates** pré-aprovados pela Meta (categoria tipicamente `UTILITY` ou `AUTHENTICATION` para avisos transacionais).
3. O destinatário deve estar **opt-in** (consentimento explícito para receber mensagens da escola/instituição no WhatsApp).
4. Identificadores comuns na API Cloud:
   - `phone_number_id` do remetente,
   - token de acesso (de curta duração ou sistema com refresh),
   - opcionalmente `waba_id` para administração.

Variáveis do template na Meta costumam ser posicionais (`{{1}}`, `{{2}}`) ou nomeadas, conforme o template cadastrado — o GIDE precisará **mapear** o contexto atual do SMS (`aluno_id`, data, hora, etc.) para as variáveis aprovadas no template.

### 5.1 Diferenças práticas em relação ao SMS

| Aspeto | SMS (atual) | WhatsApp (típico) |
|--------|-------------|-------------------|
| Conteúdo livre | Sim (texto do template no GIDE) | Não fora da janela 24h; template fixo na Meta |
| Opt-in | Boas práticas / política interna | **Obrigatório** para conformidade com políticas Meta |
| Formato do número | E.164 sem `+` no envio Zenvia | E.164 com `+` na Cloud API (`to`) |
| Entrega | HTTP 2xx = aceito pelo provedor | Pode exigir **webhook** de status (`sent`, `delivered`, `read`, `failed`) |
| Custo | Por segmento SMS | Por conversa / categoria de template |

---

## 6. Proposta de desenho no GIDE (implementação futura)

Objetivo: **não** duplicar lógica de “quem notificar e quando” — extrair ou reutilizar:

- Resolução de destinatário e modo teste (espelhar `sms_recipient_mode` / `test_phone_numbers` ou unificar em `extra` de uma integração `whatsapp`).
- Construção de **contexto** idêntico ao de `SmsService` (mesmos campos para preencher template).

Sugestão de componentes (nomes ilustrativos):

| Peça | Função |
|------|--------|
| `integrations.key = whatsapp` | Credenciais, `phone_number_id`, versão da API, flags de opt-in |
| `whatsapp_templates` ou reutilização de `sms_templates` com `channel` | Corpo **lógico** + nome do template na Meta + ordem das variáveis |
| `whatsapp_deliveries` | Espelho de `sms_deliveries` (status, `provider_message_id`, erros, retries) |
| `SendPresenceWhatsApp` (job) | `ShouldBeUnique` por `event_id` + canal, como `SendPresenceSms` |
| `WhatsAppCloudClient` | `POST https://graph.facebook.com/v{version}/{phone-number-id}/messages` com JSON de template |
| Webhook opcional `POST /api/v1/.../whatsapp` | Verificação `hub.challenge` + assinatura `X-Hub-Signature-256` para atualizar status de entrega |

**Idempotência:** chave única lógica `(event_id, template_name_or_key, to)` análoga ao SMS, para replays de webhook não gerarem mensagens duplicadas.

---

## 7. Variáveis de ambiente (sugestão)

Até existir tela dedicada, pode-se seguir o padrão do SMS (`integrations` + opcionalmente `.env`):

```env
# Exemplo — nomes ilustrativos; alinhar ao que for implementado
WHATSAPP_CLOUD_API_VERSION=v21.0
WHATSAPP_DEFAULT_PHONE_NUMBER_ID=
WHATSAPP_SYSTEM_USER_TOKEN=
# ou OAuth / BSP específico
```

Credenciais sensíveis devem permanecer em **integração no banco** ou **secrets** do ambiente, nunca em repositório.

---

## 8. Conformidade e operação

- **LGPD / consentimento:** registrar base legal e evidência de opt-in por responsável/aluno conforme política da instituição.
- **Rate limits** da Meta e falhas temporárias: usar fila + backoff como no SMS.
- **Observabilidade:** logs estruturados, mesma visão de “ponte” na página de integrações (probe manual), e lista de entregas no admin.

---

## 9. Documentos e ficheiros relacionados

- Fluxo geral e diagrama: [`docs/FLUXO_DO_SISTEMA.md`](FLUXO_DO_SISTEMA.md)
- Eventos Gestor → GIDE (HMAC): [`docs/CATRACA_WEBHOOK.md`](CATRACA_WEBHOOK.md)
- Código: `App\Jobs\SendPresenceSms`, `App\Services\Sms\SmsService`, `App\Services\Sms\ZenviaSmsClient`, `App\Http\Controllers\Web\IntegrationController` (`sms`, `updateSms`), `config/integrations.php` (`sms.default_base_url`)

---

## 10. Resumo executivo

- **Hoje:** notificação pós-presença **só por SMS** (Zenvia), após `mark_presence`, com template e auditoria no GIDE.
- **WhatsApp:** reservado na UI como canal futuro; a implementação recomendada é **paralela ao SMS** (mesmo gatilho, novo conector, templates Meta + opt-in, tabela de entregas e job dedicado).
- **Próximo passo de engenharia:** definir BSP ou Cloud API direta, modelo de dados (`whatsapp_deliveries`), assinatura de webhook de status e mapeamento de variáveis do template aprovado para o contexto já produzido por `PresenceRuleEngine` / payload do Gestor.
