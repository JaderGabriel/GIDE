# GIDE — Documento técnico (melhorias, otimizações e gargalos)

Este documento aponta **padrões atuais do projeto**, possíveis **gargalos**, riscos e um backlog técnico de **melhorias**.

---

## Padrão atual do projeto (como está hoje)

- **Laravel 13** com rotas web + API.
- **Autenticação web**: sessão (`Auth::attempt`) por `username`, com middleware `auth`.
- **Autorização admin**: middleware `admin` baseado em `users.is_admin`.
- **Integrações**: tabela única `integrations` (config + tokens + secrets); `extra` é armazenado como **TEXT criptografado** (PostgreSQL).
- **Inbound security**: HMAC via middleware `verify.hmac:{integrationKey}` + Bearer token (catraca).
- **Outbound**:
  - Gestor: client com bearer token + ApplicationKey e retry simples em 401.
  - iEducar: client legacy via `access_key` e callback "API nova" via Bearer token.
  - SMS: cliente HTTP atual via header `X-API-TOKEN` (token na integração).
- **Async**: jobs para outbound (matrícula→Gestor, SMS pós-presença). Execução depende do worker `queue:listen`.
- **Auditoria**: tabelas para ingests/events/outbound/sms + `reprocessing_log` JSON em deliveries.
- **Motor de presença**: `PresenceRuleEngine` com 4 modos (`auto`, `always_mark`, `explicit_only`, `disabled`), janelas de horário com tolerância (±min), mapeamento configurável de campos.
- **Tela de configuração Gestor**: interface com abas (Conexão/Convite, Motor de presença, Canais/Testes), salvamento independente por bloco, textos explicativos.

---

## Gargalos/risco operacional (onde pode quebrar ou degradar)

### 1) Confiabilidade de fila (jobs)

Risco:

- Se o worker não estiver rodando, jobs (SMS/outbound Gestor) **não executam**.
- Se houver falha temporária (rede/provedor), a lógica grava `next_retry_at`; o reenvio depende de **`php artisan gide:deliveries:retry-due`** (agendado em `routes/console.php` junto com `gide:queue:work-once`).

Sugestões (refino):

- Monitorar `failed_jobs` e alertar se crescer; opcionalmente rodar `gide:deliveries:retry-due --recover-stale` em horário de baixa.
- Definir **tentativas máximas** e "dead-letter" (ex.: `status=dead`) para evitar loop infinito.

Estado atual:

- Jobs principais (`SendEnrollmentToAccessControl`, `SendPresenceSms`) têm **máximo de 3 tentativas** (`$tries = 3`).
- As tabelas `outbound_deliveries` e `sms_deliveries` também param de agendar retry ao atingir 3 tentativas.

### 2) Integrações em `integrations.extra` (segurança e governança)

Risco:

- `extra` armazena credenciais (ex.: username/password do Gestor) em claro no banco.
- `auth_token` também fica em claro.

Sugestões:

- Usar **encriptação de atributos** (Laravel "encrypted cast") para `auth_token` e subcampos sensíveis no `extra`.
- Separar credenciais em colunas dedicadas (ou outra tabela) se a complexidade crescer.

Estado atual: **✅ Implementado**

- `auth_token` e `hmac_secret` usam cast `encrypted`
- `extra` usa cast `encrypted:array`
- Comando de migração de dados: `php artisan integrations:encrypt-existing` (com `--dry-run`)

### 3) Contratos externos ainda instáveis

Risco:

- Endpoints reais do Gestor para Invite/Guest/Face ainda não foram finalizados, então parte do fluxo é configurável mas **não validável em produção**.

Sugestões:

- Formalizar contratos via **OpenAPI** interno e fixtures (ex.: payload esperado).
- Criar "modo simulado" (`fake`) para testes end‑to‑end.

### 4) Presença no iEducar (enriquecimento insuficiente)

Risco:

- `PresenceMarker` pode "skipped" por falta de dados (instituicao_id/etapa/turmas). Isso reduz a efetividade do MVP.

Sugestões:

- Definir estratégia de **enriquecimento**:
  - buscar dados extras no iEducar com endpoints disponíveis
  - persistir mapeamentos (matricula→turma/etapa/instituição)
- Criar dashboard/admin para visualizar eventos **skipped** e motivo.

Estado atual: **✅ Implementado**

- `PresenceRuleEngine` agora oferece 4 modos configuráveis (`auto`, `always_mark`, `explicit_only`, `disabled`) — administradores escolhem o comportamento via interface.
- Modo `auto` usa **janelas de horário com tolerância** (±min) para decisão automática.
- Motor retorna `reason` detalhado (ex.: janela mais próxima, diferença em minutos), visível no admin.
- Mapeamento de campos do payload é configurável (resolve nomes diferentes de `aluno_id`, `matricula_id`, `type`).
- Administradores podem **reavaliar** eventos pelo motor via `/admin/gestor-access-events` com log completo.
- **Enriquecimento automático**: `StudentEnrichmentService` busca turma/etapa/nome no iEducar via `postCatracaFrequenciaAlunoConsulta` e cacheia em `student_enrichment_cache` (TTL 24h). Dados exibidos no card "Dados do aluno" na tela de detalhe e na timeline.

### 5) SMS: "enviado" vs "entregue"

Risco:

- status `sent` significa "API aceitou", não "entregue ao destinatário".

Sugestões:

- Implementar **webhook de delivery report** do provedor SMS para atualizar `sms_deliveries.status` com estados reais.
- Adicionar campos `delivered_at`, `delivery_status`, `delivery_error_code` etc.

### 6) Observabilidade (logs e correlação)

Risco:

- Diagnóstico de incidente fica difícil sem correlação (`event_id` em logs, request IDs, etc.).

Sugestões:

- Padronizar logs com `event_id` e `integration_key`.
- Para inbound/outbound HTTP, registrar `request_id`, status, latência e body truncado.
- Adicionar tela admin "Eventos" (access_events/enrollment_ingests) com filtros.

Estado atual: **✅ Implementado**

- Tela admin `/admin/gestor-access-events` implementada com listagem, filtros e detalhamento.
- Cada delivery armazena `reprocessing_log` (JSON) com histórico completo de ações administrativas: quem, quando, ação, status anterior/novo, motivo.
- Ações de retry, requeue, force-process e reavaliação pelo motor são registradas com auditoria (`UserAuditLogger`).
- **Correlação `request_id`**: middleware `AssignRequestId` gera UUID por request (API), propaga via `Log::shareContext`, grava em `analysis_json.request_id` e em `meta` do `UserAuditLogger`. Header `X-Request-Id` na response.
- **Timeline por aluno**: tela `/admin/timeline/{cod_aluno}` agrega access-events, SMS e facial em cronologia unificada com filtros por tipo. Card "Últimos alunos ativos" no dashboard com link direto.

---

## Otimizações e melhorias arquiteturais (backlog sugerido)

### Segurança

- ~~Encriptar tokens e segredos sensíveis no DB.~~ **✅ Feito** (encrypted cast)
- Rate limit e proteção adicional nas rotas inbound (além do HMAC).
- Rotacionar `auth_token` do Gestor automaticamente e armazenar `expires_at` (se o SDK fornecer).

### Robustez

- Retry scheduler para `outbound_deliveries` e `sms_deliveries`.
- Circuit breaker simples para provedores instáveis (reduz tempestade de requests).
- Timeout/limites por integração (configuráveis).

### Performance

- Evitar chamadas repetitivas no Gestor (cache de token, cache de Unity).
- Em `access-events`, evitar processamento pesado inline: mover análise+marcação para job quando volume for alto.

### Qualidade e testes

- Testes unitários para:
  - VerifyHmacSignature (casos TTL/assinatura)
  - SmsTemplateRenderer (tags)
  - Normalização de telefone BR
  - **PresenceRuleEngine** (modos, janelas, tolerância, payload map)
- Testes de integração com `Http::fake()` para Gestor/iEducar e API SMS.

### UX/Admin

- ~~Consolidar páginas de integrações em um "menu"/painel único.~~ **✅ Feito** (abas com salvamento independente em `/integracoes/gestor`)
- ~~Adicionar textos explicativos e agrupamento claro na configuração.~~ **✅ Feito** (callouts, descrições por campo, separação SDK vs Motor)
- Adicionar "preview" de template SMS com contexto de exemplo.
- ~~Adicionar visão "timeline" por aluno/matrícula.~~ **✅ Feito** (tela `/admin/timeline/{cod_aluno}` com cronologia unificada)
- ~~Visualização de reprocessamentos/ações admin em eventos de acesso.~~ **✅ Feito** (`reprocessing_log` na tela de detalhes)
- ~~Dashboard operacional consolidado.~~ **✅ Feito** (`/admin/operacional` — KPIs, volume diário, distribuição status/canal, filas SMS/outbound/frequência, saúde do sistema, detalhes técnicos)

---

## Gargalos potenciais (quando escalar)

- Volume alto de `access-events` pode saturar:
  - DB (writes em `access_events`)
  - chamadas outbound para iEducar/SMS
- Solução típica:
  - enfileirar processamento e usar workers escaláveis
  - particionar por integração/tenant (se houver múltiplas escolas)
  - indexar colunas de filtros usadas em admin
