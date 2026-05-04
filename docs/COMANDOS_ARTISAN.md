# Comandos Artisan do GIDE

Este documento lista **apenas** os comandos Artisan relevantes ao projeto (definidos em `app/Console/Commands/*.php` e em `routes/console.php`), com **propósito**, **motivo** de existirem, **exemplos** e **simulações** típicas.

**Onde estão definidos**

| Origem | Ficheiro |
|--------|-----------|
| Classes de comando | `app/Console/Commands/` (descoberta automática em `bootstrap/app.php`) |
| Comandos “inline” | `routes/console.php` (`Artisan::command(...)`) |
| Agendamento | `routes/console.php` (`Schedule::call` no final do ficheiro) |

**Pré-requisitos comuns**

- `.env` com `APP_KEY` e base de dados coerentes com a instância que a web usa (CLI e PHP-FPM devem alinhar).
- Para testes PHPUnit via `gide:test`: extensão PHP **`pdo_sqlite`** se o `phpunit.xml` usar SQLite em memória.
- Comandos que chamam iEducar ou Gestor na rede precisam de integrações **habilitadas** e URLs/credenciais válidas.

---

## Visão rápida

| Comando | Área | Resumo |
|---------|------|--------|
| `gide:test` | Qualidade | PHPUnit por tema (`--group`). |
| `gide:simulate-catraca-access-events` | Catraca / auditoria | Vários POSTs ao webhook Bearer + listagem de `gestor_access_event_deliveries`. |
| `gide:simulate-catraca-access-pipeline` | Catraca / integração | Um POST com etapas explicadas, JSON in/out, checklist de config e (opcional) `Http::fake` iEducar+SMS. |
| `ieducar:facial-catraca-flow:test` | iEducar catraca-frequência | Sequência consulta / confirmação / registro de frequência (documentado à parte). |
| `db:repair-users-id-sequence` | PostgreSQL | Corrige sequência `users.id` após restore. |
| `integrations:encrypt-existing` | Migração / segurança | Cifra colunas sensíveis já em claro. |
| `ieducar:rotate-hmac` | iEducar inbound | Gera `hmac_secret` para webhooks HMAC. |
| `ieducar:catraca-frequencia:*` | iEducar API nova | Consulta aluno, confirmação facial, registro frequência (preview/apply, série). |
| `gestor:import-postman` | Gestor | Importa collection Postman para `integrations.extra`. |
| `gestor:auth:test-config` / `test-setup` | Gestor | Signin com dados do banco vs parâmetros CLI. |
| `gestor:invite:*` | Gestor | Listar, obter e simular criação de Invite. |
| `gide:deliveries:retry-due` | Fila | Re-despacha entregas com retry vencido. |
| `gide:queue:work-once` | Fila | `queue:work` uma vez ou drenagem (cron). |

---

## `gide:test`

**O quê:** corre PHPUnit com grupos por “tema” (`--theme`), ou toda a suíte.

**Porquê:** a suíte é grande; agrupar por API, telas ou fluxo reduz tempo de feedback em CI e em desenvolvimento local.

**Opções principais**

- `--theme=tema1` (repetir ou `tema1,tema2`)
- `--list` — tabela de temas
- `--testdox` — saída legível
- `--no-structured-outcome` — desativa blocos de resumo estruturado

**Exemplos**

```bash
php artisan gide:test --list
php artisan gide:test --theme=telas-auth
php artisan gide:test --theme=api-gestor,api-catraca-webhook
php artisan gide:test
```

**Simulações possíveis**

- Regressão só da API da catraca (webhook): `--theme=api-catraca-webhook`.
- Fluxo de matrícula + API iEducar: `--theme=api-ieducar --theme=fluxo-enrollment`.
- Smoke de telas administrativas: `--theme=telas-integracoes,telas-dashboard`.

---

## `gide:simulate-catraca-access-events`

**O quê:** envia **≥10** POSTs para `POST /api/v1/catraca/access-events` com JSON tipo equipamento e Bearer; no fim lista linhas de auditoria (`gestor_access_event_deliveries`, `inbound_channel=catraca_bearer`).

**Porquê:** validar token, contrato JSON, respostas com `delivery_id` e visibilidade para TI sem Postman manual repetido.

**Opções**

| Opção | Significado |
|-------|-------------|
| `--count=` | Número de pedidos (mínimo 10; omissão 12). |
| `--token=` | Bearer em claro; senão `GIDE_CATRACA_ACCESS_TOKEN` no `.env`. |
| `--http` | Usar cliente HTTP contra `APP_URL` (ou `--url=`). |
| (default) | Kernel interno — não exige servidor à escuta. |

**Exemplos**

```bash
php artisan gide:simulate-catraca-access-events --token='gide_cwc_...'
php artisan gide:simulate-catraca-access-events --count=20 --token="$GIDE_CATRACA_ACCESS_TOKEN"
php artisan gide:simulate-catraca-access-events --http --url=http://127.0.0.1:8000 --token='gide_cwc_...'
```

**Simulações possíveis**

- Carga leve de auditoria: `--count=50` (se a base aguentar).
- Teste atrás de reverse proxy: `--http --url=https://homolog.example.org`.
- CI com token efémero: injetar `GIDE_CATRACA_ACCESS_TOKEN` no job (não commitar o valor).

Documentação de contrato: [`docs/CATRACA_WEBHOOK.md`](CATRACA_WEBHOOK.md).

---

## `gide:simulate-catraca-access-pipeline`

**O quê:** um único `POST /api/v1/catraca/access-events` com JSON de equipamento, seguido de resumo do pipeline **presença → preview iEducar (job se necessário) → SMS**, com texto por **etapas**, **JSON de entrada e de saída**, tabela de **lacunas de configuração** (níveis: `ok`, `aviso`, `bloqueio`, `info`) e ligações ao admin.

**Porquê:** validar de ponta a ponta sem Postman (ou com `--http` contra um servidor), perceber rapidamente o que falta (token catraca, `ieducar.enabled`, janelas `presence.windows`, Bearer do iEducar, `sms.extra.from`, templates ativos, `QUEUE_CONNECTION`).

**Opções**

| Opção | Significado |
|-------|-------------|
| `--token=` | Bearer em claro; senão `GIDE_CATRACA_ACCESS_TOKEN` no `.env`. |
| `--cod-aluno=` | Valor enviado em `name` no JSON (mapeado para aluno no motor); omissão `211`. |
| `--http` / `--url=` | Igual ao outro simulador: HTTP em vez do kernel interno. |
| `--real-ieducar` | Desativa **todo** o `Http::fake` deste comando: iEducar **e** SMS usam rede real se forem chamados. |
| `--diagnose-only` | Só imprime o checklist; **não** envia POST (útil antes de ter token). |

**Comportamento por omissão**

- **`Http::fake`**: respostas 200 simuladas para **registro** catraca-frequência e para **`/channels/sms/messages`** (Zenvia), para a CLI não depender de rede.
- **`creationDate`**: calculado a partir da **primeira janela** em `ieducar.extra.presence.windows` (fuso `APP_TIMEZONE`) para favorecer `action=mark_presence`; se não houver janelas, usa “agora” e o motor pode devolver `ignore` (visível na auditoria).
- Se a resposta tiver **`queued: true`**, corre **`ProcessGestorAccessEventDeliveryJob::dispatchSync`** para concluir o preview nesta execução.
- Se **`QUEUE_CONNECTION` ≠ `sync`**, tenta até **8** vezes `queue:work --once` na mesma conexão para apanhar SMS pendentes.

**Exemplos**

```bash
php artisan gide:simulate-catraca-access-pipeline --diagnose-only
php artisan gide:simulate-catraca-access-pipeline --token="$GIDE_CATRACA_ACCESS_TOKEN"
php artisan gide:simulate-catraca-access-pipeline --cod-aluno=305 --token='gide_cwc_...'
php artisan gide:simulate-catraca-access-pipeline --http --url=http://127.0.0.1:8000 --token='...'
php artisan gide:simulate-catraca-access-pipeline --token='...' --real-ieducar
```

**Documentação de contrato:** [`docs/CATRACA_WEBHOOK.md`](CATRACA_WEBHOOK.md).

---

## `ieducar:facial-catraca-flow:test`

**O quê:** executa em cadeia os POSTs catraca-frequência usados após fluxo facial (consulta, confirmação opcional, registro de frequência com Plan B / `meta.preview` alinhado ao Gestor).

**Porquê:** reproduzir o mesmo contrato que a UI `/facial/enviar` e documentar diferenças preview vs apply sem depender do browser.

**Exemplos** (simplificado; detalhe completo no ficheiro de doc do fluxo):

```bash
php artisan ieducar:facial-catraca-flow:test 211 --idpes=12345678
php artisan ieducar:facial-catraca-flow:test 211 --idpes=12345678 --skip-consulta
php artisan ieducar:facial-catraca-flow:test 211 --skip-confirmacao --dry-run
```

**Simulações possíveis**

- Só registro em preview: `--skip-consulta --skip-confirmacao`.
- Forçar `meta.preview`: `--force-preview` (ver `--help` do comando).

Referência: [`docs/IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md`](IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md).

---

## `db:repair-users-id-sequence`

**O quê:** em **PostgreSQL**, sincroniza a sequência de `users.id` com o maior `id` existente.

**Porquê:** após restore/import dump, inserts podem falhar com `duplicate key value violates unique constraint "users_pkey"`.

**Exemplo**

```bash
php artisan db:repair-users-id-sequence
```

**Simulações possíveis**

- Não aplicável a SQLite/MySQL (o comando recusa e explica o driver).

---

## `integrations:encrypt-existing`

**O quê:** percorre `integrations` e cifra `auth_token`, `hmac_secret` e `extra` que ainda estejam em texto decifrável.

**Porquê:** migração gradual quando se ativam casts `encrypted` sem regravar todas as linhas pela UI.

**Exemplos**

```bash
php artisan integrations:encrypt-existing --dry-run
php artisan integrations:encrypt-existing
```

**Simulações possíveis**

- Sempre correr `--dry-run` primeiro em cópia da base de produção.

---

## `ieducar:rotate-hmac`

**O quê:** cria/atualiza a linha `integrations` com `key=ieducar` e gera novo `hmac_secret` (base64 de 32 bytes).

**Porquê:** o iEducar precisa do mesmo segredo para assinar `POST /api/v1/ieducar/*`; rotação por CLI evita depender só da UI.

**Exemplo**

```bash
php artisan ieducar:rotate-hmac
```

**Simulações possíveis**

- Após rotação, atualizar o segredo no **lado iEducar**; senão os webhooks passam a responder 401.

---

## `ieducar:catraca-frequencia:aluno-consulta`

**O quê:** `POST` de consulta de aluno via `IeducarClient` (identificação por `cod_aluno` e/ou `idpes`).

**Porquê:** validar credenciais, path e resposta JSON do iEducar sem passar pelo GIDE web.

**Exemplos**

```bash
php artisan ieducar:catraca-frequencia:aluno-consulta 211
php artisan ieducar:catraca-frequencia:aluno-consulta --idpes=12345678
```

---

## `ieducar:catraca-frequencia:confirmacao`

**O quê:** envia confirmação de coleta facial (`data_coleta` ISO8601).

**Porquê:** espelha o callback pós-coleta; `--idpes` é obrigatório (contrato iEducar).

**Exemplo**

```bash
php artisan ieducar:catraca-frequencia:confirmacao 211 --idpes=12345678
php artisan ieducar:catraca-frequencia:confirmacao 211 --idpes=12345678 --data=2026-05-03T15:00:00Z
```

---

## `ieducar:catraca-frequencia:frequencia-registro`

**O quê:** monta um ou vários payloads Plan B, opcionalmente grava entrega em `ieducar_frequencia_registro_deliveries`, despacha `SendIeducarFrequenciaRegistroJob::dispatchSync` e imprime URL de admin.

**Porquê:** testar **série** de registros (datas/fontes/presente variados), **preview vs apply**, e **dry-run** sem HTTP.

**Opções notáveis**

| Opção | Efeito |
|-------|--------|
| `--cod-aluno=` | Modo série (com `--tentativas`, até 500). |
| `--json=` | Um payload a partir de ficheiro (validação Plan B). |
| `--tentativas=` | Número de pedidos na série (default 12). |
| `--intervalo=` | Pausa em segundos entre pedidos. |
| `--apply` | `meta.preview=false` (gravação real no iEducar — cuidado). |
| `--dry-run` | Só imprime JSONs, sem fila nem HTTP. |

**Exemplos**

```bash
php artisan ieducar:catraca-frequencia:frequencia-registro --cod-aluno=211 --tentativas=5 --dry-run
php artisan ieducar:catraca-frequencia:frequencia-registro --cod-aluno=211 --tentativas=12 --intervalo=0.5
php artisan ieducar:catraca-frequencia:frequencia-registro --json=/tmp/payload.json
```

**Simulações possíveis**

- Stress controlado: `--tentativas=100` com `--intervalo=1` em homologação.
- Validar ficheiro antes de enviar: `--json=... --dry-run` (o comando força uma única tentativa com `--json`).

---

## `gestor:import-postman`

**O quê:** lê uma collection Postman v2.1 (JSON), extrai método+URL dos pedidos e grava em `integrations.key=gestor` dentro de `extra.postman`.

**Porquê:** documentação viva de endpoints SDK no próprio GIDE, útil para onboarding e cruzamento com código.

**Exemplo**

```bash
php artisan gestor:import-postman /caminho/para/collection.json
```

---

## `gestor:auth:test-config`

**O quê:** `GestorClient::signIn()` usando **só** a integração `gestor` na base.

**Porquê:** confirmar username/password/application_key/base_url sem expor segredos na linha de comandos.

**Exemplo**

```bash
php artisan gestor:auth:test-config
```

---

## `gestor:auth:test-setup`

**O quê:** HTTP direto ao endpoint de Signin com `--base`, `--appkey`, `--username`, `--password` (não grava na base).

**Porquê:** depurar rede, TLS ou path antes de persistir credenciais na UI.

**Exemplo**

```bash
php artisan gestor:auth:test-setup \
  --base=https://gestor.example.com \
  --path=/SDK/Auth/Signin \
  --appkey=... \
  --username=... \
  --password=...
```

---

## `gestor:invite:list` e `gestor:invite:get`

**O quê:** listagem experimental de Invites (`GET /SDK/Invite?limit=` com fallback a `/SDK/Invites`) e consulta por ID.

**Porquê:** inspeção manual quando a UI ainda não cobre listagens do SDK.

**Exemplos**

```bash
php artisan gestor:invite:list --limit=20
php artisan gestor:invite:get 12345
```

---

## `gestor:invite:create-simulate`

**O quê:** monta o mesmo JSON de Invite que o outbound de matrícula (`AccessControlOutboundService`), opcionalmente sobrescreve path/unity/profile, faz **POST** real ao Gestor e tenta extrair `guest_id` / `invite_id` (atualiza `gestor_guest_links` quando encontra).

**Porquê:** simular matrícula → Gestor sem passar pelo webhook iEducar; mensagens longas no comando explicam de onde vêm `unityId` e path.

**Exemplos**

```bash
php artisan gestor:invite:create-simulate --cod_aluno=211
php artisan gestor:invite:create-simulate --cod_aluno=211 --cod_matricula=999 --unityId=5
php artisan gestor:invite:create-simulate --cod_aluno=211 --path=/SDK/Invite
```

**Simulações possíveis**

- Forçar `accessProfileId` null no JSON: `--accessProfileId=0`.
- Testar outro ano letivo: `--ano=2025`.

---

## `gide:deliveries:retry-due`

**O quê:** instancia `DeliveryRetryDispatcher` e re-enfileira entregas outbound/SMS com `next_retry_at` vencido; `--recover-stale` inclui casos com `attempts=0` antigos.

**Porquê:** recuperação operacional sem esperar pelo próximo minuto do schedule, ou após downtime da fila.

**Exemplos**

```bash
php artisan gide:deliveries:retry-due
php artisan gide:deliveries:retry-due --recover-stale
```

---

## `gide:queue:work-once`

**O quê:** encapsula `php artisan queue:work`: por omissão **uma iteração** (`--once`); com `--drain` esvazia até `max-time` / `max-jobs` (config `queue.schedule_drain_*`).

**Porquê:** o `Schedule` do GIDE chama este comando no **mesmo processo** que `schedule:run` (ver comentário em `routes/console.php`) para evitar subprocessos que não drenam a fila em alguns crons.

**Exemplos**

```bash
php artisan gide:queue:work-once
php artisan gide:queue:work-once --drain
```

**Simulações possíveis**

- Ambiente com `QUEUE_CONNECTION=sync`: o comando avisa que não há fila a drenar.

---

## Agendamento (`schedule`)

No final de `routes/console.php`, **cada minuto** (com `withoutOverlapping(120)`):

1. `gide:deliveries:retry-due`
2. `gide:queue:work-once --drain`

**Motivo:** retries de entregas + processamento da fila `database` (ou outra default) sem depender de dois `Schedule::command` separados que spawnam subprocessos.

**Simulação local do cron**

```bash
php artisan schedule:work
# ou
php artisan schedule:run
```

---

## Comando Laravel `inspire`

`php artisan inspire` existe por defeito no skeleton Laravel; imprime uma citação. Não tem função específica do domínio GIDE.

---

## Mapa de documentos relacionados

| Comando / tema | Documento |
|----------------|------------|
| Webhook catraca (Bearer) | [`docs/CATRACA_WEBHOOK.md`](CATRACA_WEBHOOK.md) |
| Fluxo facial + catraca-frequência | [`docs/IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md`](IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md) |
| Fluxo geral do sistema | [`docs/FLUXO_DO_SISTEMA.md`](FLUXO_DO_SISTEMA.md) |
| Testes PHPUnit / CI | [`README.md`](../README.md) (secção de testes) |
