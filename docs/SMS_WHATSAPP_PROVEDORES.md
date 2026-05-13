# Serviços de envio SMS e WhatsApp (pesquisa e enquadramento no GIDE)

Este documento resume **opções de mercado** (com base em pesquisa web em 2026), **modelos de preço e compliance** relevantes para notificações escolares (ex.: presença na catraca / sincronização com iEducar) e **como isso encaixa no desenho atual do GIDE**.

> **Estado no código:** o envio de SMS usa **`integrations.extra.provider`**: **`twilio`** (padrão) com `App\Services\Sms\TwilioSmsClient` — `POST …/2010-04-01/Accounts/{AccountSid}/Messages.json`, Basic Auth (Account SID + Auth Token), corpo `application/x-www-form-urlencoded` (`To`, `From`, `Body`). **`zenvia`** continua disponível via `App\Services\Sms\ZenviaSmsClient`. Credenciais e URLs específicas persistem na integração `sms` no banco (`/integracoes/sms`); teste CLI: `php artisan sms:twilio-test` (usa só essa integração). **WhatsApp** não tem cliente dedicado; as secções abaixo servem para **planeamento**.

---

## 1. O que o GIDE já modela (SMS)

- **Integração** `integrations.key = sms`: `base_url` (opcional), `auth_token` (Auth Token Twilio ou X-API-TOKEN Zenvia), `enabled`, `extra` (`provider`, `account_sid` no Twilio, `from`, modo destinatário, templates).
- **Templates** com chaves `presence_catraca` e `presence_ieducar_sync` (`App\Support\SmsTemplateKey`).
- **Entregas** persistidas (`SmsDelivery`), filas, retries (`SendPresenceSms`, `DeliveryRetryDispatcher`), telas `/sms` e métricas no dashboard operacional.
- **Provedor:** `integrations.extra.provider` — `twilio` ou `zenvia`; `SmsService` despacha para o cliente correspondente.

### Auditoria por `event_id` (admin Gestor / catraca)

- Cada SMS de presença fica associado ao **`event_id`** do webhook (coluna `sms_deliveries.event_id`), juntamente com `template_key` e `to` (destino em E.164 só dígitos). A unicidade por envio é essa tripla; reenvios para o mesmo destino atualizam a mesma linha.
- O serviço `App\Services\Sms\SmsService` grava um histórico em **`context.send_log`** (JSON, até **50** entradas por linha): data ISO, `trigger`, `template_key`, destino mascarado, `status` (`sent` / `error`), pré-visualização do texto, `http_status`, `provider_message_id` ou excerto de erro quando aplicável. Em reenvio manual com `allowResendWhenAlreadySent`, o contexto novo é fundido com o anterior para **não apagar** entradas já registadas.
- Valores de **`trigger`**: `automated` — disparo pela fila (`App\Jobs\SendPresenceSms`); `admin_resend_config` — botão “conforme integração” em `GET /admin/gestor-access-events/{id}`; `admin_resend_guardians` — botão “responsáveis” no mesmo ecrã.
- Na página de detalhe da entrega (`admin/gestor-access-events/{id}`), o bloco **“SMS enviados neste evento”** lista o estado atual em `sms_deliveries` e a **cronologia** unificada a partir de `send_log` (útil para distinguir envio automático de reenvios manuais). Registos criados antes desta funcionalidade podem não ter linha de tempo até haver um envio concluído com código atual.

### Formulário `/integracoes/sms` (credencial)

- O campo **Credencial secreta** só persiste na base quando o utilizador introduz um valor novo; **em branco**, o gravar mantém o token já guardado na integração (sem ler `.env` para esse segredo).

---

## 2. SMS — fornecedores e notas de integração

### 2.1 Twilio (padrão no GIDE)

- **Papel:** API global; SMS + voz + WhatsApp (BSP); documentação madura.
- **Encaixe no GIDE:** `provider=twilio`, `extra.account_sid`, `auth_token` = Auth Token, `extra.from` = número E.164 Twilio; ver [Create a Message (Twilio)](https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource).
- **Teste:** `php artisan sms:twilio-test` — usa apenas credenciais gravadas na integração `sms` no banco (`/integracoes/sms`).

### 2.2 Zenvia (opcional / legado)

- **Papel:** CX / automação com SMS no Brasil.
- **Encaixe no GIDE:** `provider=zenvia`, `base_url` opcional na integração (padrão `https://api.zenvia.com/v2` em `config/integrations.php`), header `X-API-TOKEN` em `integrations.auth_token`; `App\Services\Sms\ZenviaSmsClient`.

### 2.3 Infobip

- **Papel:** plataforma **omnicanal** (SMS, RCS, e-mail, voz, WhatsApp, etc.) orientada a jornadas e volume enterprise.
- **No GIDE:** adequado se a escola quiser **um único contrato** para SMS e, mais tarde, WhatsApp com o mesmo fornecedor.

### 2.4 Outras referências citadas em listagens de gateways BR

- **Sinch / TWW (histórico Brasil):** atores consolidados em mercado corporativo de mensageria (verificar marca e produto atual na Sinch).
- **Gateways regionais / SMS “Brasil”:** úteis quando o requisito é **rota nacional** e suporte local; validar SLA, DLR (delivery report) e API REST.

### 2.5 AWS End User Messaging (SNS / Pinpoint)

- **Papel:** SMS via infra AWS; bom para quem já está 100% na AWS e quer faturação unificada.
- **No GIDE:** novo adaptador; atenção a **opt-out**, limites e registo de origem conforme regras da AWS e operadoras.

### 2.6 Critérios de escolha (SMS)

| Critério | Perguntas |
|----------|-----------|
| **DLR e retries** | O fornecedor devolve status por mensagem? O GIDE já grava HTTP status e reencaminha jobs. |
| **Remetente (from)** | Alphanumeric vs número curto vs long code — o que a operadora e o provedor permitem no Brasil. |
| **LGPD** | Base legal, minimização de dados, retention de logs; telefone só para finalidade informada. |
| **Custo** | Por segmento ou mensagem; plano mínimo mensal (ex.: referências de mercado a partir ~R$ 60/mês em players BR — valores mudam; pedir proposta). |

---

## 3. WhatsApp Business Platform — modelo e fornecedores

### 3.1 Quem “entrega” a mensagem

- A infraestrutura é da **Meta** (WhatsApp Business Platform).
- A escola/integrador escolhe entre:
  - **Cloud API (Meta):** integração direta à Graph API; mais controlo interno, mais responsabilidade operacional (webhooks, templates, fila).
  - **BSP (Business Solution Provider):** parceiro certificado que facilita onboarding, ferramentas, suporte e por vezes faturação agregada — em troca de fee ou compromisso mínimo.

Leitura útil sobre o trade-off: [OnSync — Cloud API vs BSP](https://onsync.co/blog/whatsapp-cloud-api-vs-business-solution-provider) (blog técnico, 2025–2026).

### 3.2 Categorias de mensagem (impactam custo e política)

A Meta cobra e regula conforme **categoria** (detalhes e atualizações em [Pricing — WhatsApp Business Platform](https://developers.facebook.com/docs/whatsapp/pricing/)):

| Categoria | Uso típico no contexto escolar / GIDE |
|-----------|----------------------------------------|
| **Utility** | Avisos transacionais: “presença registada”, “sincronizado com diário” — desde que encaixem nas políticas de modelo aprovado. |
| **Authentication** | OTP / códigos de verificação (menos comum para presença). |
| **Marketing** | Promoções, campanhas — **não** adequado para simples aviso de presença. |
| **Service** | Conversa iniciada pelo utilizador / janela de atendimento — relevante se no futuro houver “responda SIM para confirmar”. |

> O modelo antigo “conversation-based pricing” está **deprecated**; a plataforma evoluiu para **preço por mensagem** / estrutura por categoria — confirmar sempre a página oficial da Meta.

### 3.3 Fornecedores (BSP / API) frequentemente referenciados na web

*(Lista para **shortlist RFP**; não é endorsement — validar contrato, preço, suporte BR e roadmap.)*

| Fornecedor | Notas de pesquisa web |
|------------|----------------------|
| **Twilio** | WhatsApp como canal adicional à mesma conta; documentação ampla. |
| **Infobip** | Omnicanal enterprise; WhatsApp + SMS no mesmo stack. |
| **Zenvia** | Ecossistema CX no Brasil; WhatsApp Business costuma ser oferecido em pacote com SMS/atendimento (preços comerciais). |
| **Plivo** | API-first; WhatsApp Business API citado em materiais comerciais (verificar planos atuais). |
| **360dialog, Gupshup, MessageBird, etc.** | BSPs / agregadores comuns em integrações globais — comparar taxa de plataforma vs Cloud API direta. |

### 3.4 Encaixe futuro no GIDE (sugestão de desenho)

1. **Novo canal** paralelo a SMS: ex. `integrations.key = whatsapp` ou `channel` por template, sem misturar payload Twilio/Zenvia com Graph API.
2. **Templates** aprovados na Meta antes de envio ativo (subjects “utility” alinhados a presença).
3. **Idempotência e entrega:** tabela análoga a `SmsDelivery` ou generalização `MessageDelivery` com `channel`, `provider_message_id`, webhooks de status.
4. **Opt-in:** WhatsApp exige base de utilizadores que aceitaram contacto comercial; regras mais estritas que SMS transacional — alinhar jurídico.

---

## 4. Documentação oficial recomendada (links)

- **WhatsApp — preços e políticas:** [developers.facebook.com/docs/whatsapp/pricing/](https://developers.facebook.com/docs/whatsapp/pricing/)
- **WhatsApp — Cloud API / Graph:** [developers.facebook.com/docs/whatsapp/cloud-api](https://developers.facebook.com/docs/whatsapp/cloud-api)
- **Zenvia — developers:** [developers.zenvia.com](https://developers.zenvia.com/)
- **Twilio — SMS / WhatsApp:** [www.twilio.com/docs](https://www.twilio.com/docs)
- **Infobip — API:** [www.infobip.com/docs](https://www.infobip.com/docs)

---

## 5. Resumo executivo

- **Curto prazo (código atual):** SMS no GIDE = **Twilio** por omissão (`provider` na integração `sms`, Account SID em `extra`, Auth Token em `auth_token`, `from` em E.164); **Zenvia** permanece como opção. Teste: `php artisan sms:twilio-test`.
- **Médio prazo:** novos fornecedores SMS = novo cliente + ramo em `SmsService` (mesmo modelo de entregas/templates).
- **WhatsApp:** decisão entre **Cloud API direta** (mais engenharia) vs **BSP** (mais custo, menos fricção); categorias **Utility** (e eventualmente **Service**) são as mais coerentes com notificações de presença; **Marketing** não deve ser usado para esse fim.

---

*Documento gerado para apoio a arquitetura; preços e SKUs mudam — validar sempre com o comercial do fornecedor e com a documentação Meta na data do projeto.*
