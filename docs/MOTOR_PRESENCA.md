# Motor de Presença (PresenceRuleEngine)

> **Classe**: `App\Services\Presence\PresenceRuleEngine`
> **Invocado por**: `GestorAccessEventWebhookService`, `GestorAccessEventAdminController@forceMarkPresence`, comando Artisan `gide:simulate-catraca-access-pipeline`.
> **Configuração**: `integrations(key=ieducar).extra.presence` — editável em `/integracoes/gestor`, seção 3.

O motor de presença é o componente central que decide se um evento de acesso (catraca / Gestor) deve resultar no registro de frequência junto ao iEducar. Ele recebe o payload original do webhook, o horário de ocorrência do evento e a configuração da integração iEducar, e retorna uma decisão com justificativa.

---

## Modos de operação

O motor suporta 4 modos configuráveis via interface (`/integracoes/gestor` → "Motor de presença"):

| Modo | Constante | Descrição |
|---|---|---|
| **Automático** | `auto` | Marca presença conforme janelas de horário. `action.mark_presence=true` no payload pode sobrepor e bypass janelas. Este é o padrão. |
| **Sempre marcar** | `always_mark` | Marca presença em todos os eventos com aluno identificado, ignorando janelas de horário. |
| **Somente explícito** | `explicit_only` | Só marca presença se `action.mark_presence=true` vier explicitamente no payload. Sem o flag, ignora. |
| **Desabilitado** | `disabled` | Nunca marca presença. O motor retorna `ignore` para tudo (exceto a verificação de evento de saída, que continua funcionando). |

O modo é armazenado em `ieducar.extra.presence.mode`.

---

## Fluxo de decisão (modo `auto`)

```
Payload + occurred_at + integração iEducar
  │
  ├─ 1. Evento de saída? ──(sim)──→ action=ignore
  │
  ├─ 2. Mode=disabled? ──(sim)──→ action=ignore
  │
  ├─ 3. action.mark_presence=false explícito? ──(sim)──→ action=ignore
  │
  ├─ 4. Mode=always_mark? ──(sim)──→ valida aluno_id → mark_presence
  │
  ├─ 5. Mode=explicit_only + flag ausente? ──(sim)──→ action=ignore
  │
  ├─ 6. action.mark_presence=true explícito? ──(sim)──→ mark_presence (bypass de janela)
  │
  ├─ 7. Sem occurred_at? ──(sim)──→ action=ignore
  │
  ├─ 8. Sem janelas configuradas? ──(sim)──→ action=ignore
  │
  ├─ 9. Fora de todas as janelas (±tolerância)? ──(sim)──→ action=ignore
  │
  └─ 10. Dentro de janela (±tolerância) + aluno_id/matricula_id? ──(sim)──→ action=mark_presence
          └─ sem identificadores? → action=ignore
```

### Variações por modo

**`always_mark`**: pula os passos 6–10. Após verificar evento de saída e `mark_presence=false`, vai direto para validar aluno e marcar presença.

**`explicit_only`**: se `action.mark_presence` não vier como `true` explícito, retorna `ignore` no passo 5. Se vier `true`, marca presença sem verificar janelas.

**`disabled`**: retorna `ignore` no passo 2, sem avaliar mais nada.

---

## Regra de `action.mark_presence`

O campo `action.mark_presence` no payload controla diretamente o comportamento do motor:

| Valor recebido | Interpretação | Efeito |
|---|---|---|
| **ausente / null** | Não declarado | Depende do modo: `auto` usa janelas; `always_mark` marca; `explicit_only` ignora |
| `true`, `1`, `"1"`, `"true"` | Explicitamente verdadeiro | Marca presença (em todos os modos exceto `disabled`) |
| `false`, `0`, `"0"`, `"false"` | Explicitamente falso | Bloqueia presença (em todos os modos) |
| `action="mark_presence"` (string) | Formato legado | Tratado como explicitamente verdadeiro |
| `action=true` (booleano raiz) | Formato legado | Tratado como explicitamente verdadeiro |

**Bloqueio**: `action.mark_presence=false` sempre impede presença, **em qualquer modo** (inclusive `always_mark`).

---

## Condições de `ignore`

O motor retorna `action=ignore` (sem envio ao iEducar) quando qualquer uma destas condições for verdadeira:

1. **Evento de saída** — `type` contém "saida" ou "exit" (case-insensitive) e `ignore_exit_events=true` na configuração.
2. **Modo `disabled`** — motor desabilitado via configuração.
3. **`action.mark_presence=false` explícito** — o payload declara que presença não deve ser marcada.
4. **Modo `explicit_only` sem flag** — `action.mark_presence` não foi enviado como `true`.
5. **Sem `aluno_id` e `matricula_id`** — mesmo com presença explícita, o motor não consegue identificar o aluno.
6. **Sem `occurred_at`** — necessário para verificar janelas de horário (apenas no modo `auto` sem flag explícito).
7. **Sem janelas configuradas** — `presence.windows` vazio ou ausente (apenas no modo `auto`).
8. **Fora da janela** — o horário do evento não cai em nenhuma janela configurada, mesmo considerando a tolerância (apenas no modo `auto`).

---

## Condições de `mark_presence`

O motor retorna `action=mark_presence` (enviar ao iEducar) quando:

1. **Modo `always_mark`** + aluno identificado (ignora janelas e flag).
2. **Presença explícita** (`action.mark_presence=true`) em qualquer modo ativo + aluno identificado.
3. **Modo `auto`** + dentro de uma janela de horário (incluindo tolerância) + aluno identificado.

---

## Tolerância de minutos

Cada janela pode ter um valor `tolerance_minutes` (inteiro ≥ 0, padrão 0). A tolerância **expande** a janela nos dois lados:

- **Início efetivo** = `start - tolerance_minutes`
- **Fim efetivo** = `end + tolerance_minutes`

### Exemplo

Janela: `start=07:00`, `end=09:30`, `tolerance_minutes=15`

| Horário do evento | Janela efetiva | Resultado |
|---|---|---|
| 06:44 | 06:45–09:45 | `ignore` (1min antes da tolerância) |
| 06:45 | 06:45–09:45 | `mark_presence` (tolerância antes do início) |
| 08:00 | 06:45–09:45 | `mark_presence` (dentro da janela nominal) |
| 09:45 | 06:45–09:45 | `mark_presence` (tolerância após o fim) |
| 09:46 | 06:45–09:45 | `ignore` (1min após a tolerância) |

Quando o motor rejeita por estar fora da janela, o retorno inclui a janela mais próxima e a distância em minutos, facilitando o diagnóstico:

```
"reason": "Fora da janela de presença. Mais próxima: Matutino (3min de distância)."
```

Com `tolerance_minutes=0`, a janela funciona exatamente como `start <= H:i <= end` (comportamento rígido).

---

## Configuração da integração

O motor lê `integrations.extra.presence` da integração iEducar. Estrutura completa:

```json
{
  "presence": {
    "mode": "auto",
    "ignore_exit_events": true,
    "payload_map": {
      "aluno_id": "aluno_id",
      "matricula_id": "matricula_id",
      "event_type": "type"
    },
    "windows": [
      { "name": "Matutino", "start": "07:00", "end": "09:30", "tolerance_minutes": 15 },
      { "name": "Vespertino", "start": "13:00", "end": "14:30", "tolerance_minutes": 15 },
      { "name": "Noturno", "start": "18:00", "end": "20:30", "tolerance_minutes": 15 }
    ]
  }
}
```

| Campo | Tipo | Default | Descrição |
|---|---|---|---|
| `mode` | `string` | `auto` | Modo de operação: `auto`, `always_mark`, `explicit_only`, `disabled`. |
| `ignore_exit_events` | `bool` | `true` | Ignora eventos cujo `type` contenha "saida"/"exit". |
| `payload_map` | `object` | `{aluno_id, matricula_id, type}` | Mapeamento de campos no payload do webhook. |
| `windows` | `array` | `[]` | Janelas de horário. Usadas apenas no modo `auto`. |
| `windows[].name` | `string` | `""` | Nome descritivo da janela (ex: "Matutino"). |
| `windows[].start` | `string` | — | Hora de início (`H:i`, ex: `"07:00"`). |
| `windows[].end` | `string` | — | Hora de fim (`H:i`, ex: `"09:30"`). |
| `windows[].tolerance_minutes` | `int` | `0` | Tolerância em minutos. Expande a janela: `start - N` e `end + N`. Ex: janela 07:00–09:30 com 15min aceita 06:45–09:45. |

### Interface de configuração

A configuração completa é editável em `/integracoes/gestor`, seção **"Motor de presença"**:

- **Modo do motor** — radio buttons com os 4 modos e descrição de cada um
- **Ignorar eventos de saída** — checkbox
- **Janelas de horário** — editor dinâmico para adicionar/remover janelas com nome, hora início, hora fim e tolerância (±min)
- **Mapeamento de campos** — 3 campos de texto para `aluno_id`, `matricula_id` e `event_type`
- **Ambiente iEducar** — preview ou homologação

Ao salvar, a configuração é gravada em `ieducar.extra.presence` (integração iEducar) e os demais campos do Gestor em `gestor.extra`.

---

## Ambiente iEducar (preview vs homologação)

Após a decisão do motor, o envio ao iEducar utiliza `meta.preview` determinado pela configuração em `/integracoes/gestor`:

- **`extra.ieducar_processing.environment = "preview"`** → `meta.preview=true` (simulação, iEducar não persiste)
- **`extra.ieducar_processing.environment = "homolog"`** (ou ausente) → `meta.preview=false` (gravação efetiva)

Isso é resolvido por `IeducarFrequenciaPreviewMode::resolveMetaPreview()`.

---

## Reavaliação administrativa

Na interface admin (`/admin/gestor-access-events/{id}`), o botão **"Reavaliar presença"** executa:

1. Carrega o payload original da entrega (`inbound_payload`).
2. Normaliza campos do payload (compatibilidade com canal `catraca_bearer`).
3. Submete ao `PresenceRuleEngine::analyze()` **sem forçar `mark_presence=true`** — usa os dados reais do payload e o modo atual da configuração.
4. **Se o motor decidir `action=mark_presence`**:
   - Atualiza `analysis_json` com a nova decisão.
   - Reseta status para `pending` e limpa dados de envio anterior.
   - Despacha `ProcessGestorAccessEventDeliveryJob` para enviar ao iEducar.
5. **Se o motor decidir `action=ignore`**:
   - Atualiza apenas `analysis_json`.
   - **Não** enfileira envio ao iEducar.
   - Retorna mensagem informando a razão da rejeição.

Todas as ações administrativas (reavaliação, retry, requeue, forçar processamento) são registadas na coluna `reprocessing_log` da entrega, com timestamp, usuário e detalhes da operação.

---

## Retorno do motor

```php
[
    'action' => 'mark_presence' | 'ignore',
    'reason' => 'Texto descritivo da decisão.',
    'mode' => 'auto' | 'always_mark' | 'explicit_only' | 'disabled',
    'window' => [ 'name' => '...', 'start' => 'HH:MM', 'end' => 'HH:MM' ],  // quando mark_presence
    'aluno_id' => mixed,        // quando mark_presence
    'matricula_id' => mixed,    // quando mark_presence
    'time' => 'HH:MM',         // quando fora da janela (modo auto)
]
```

---

## Cenários de uso típicos

### Escola com horários fixos (modo `auto`)
Janelas configuradas para entrada manhã (07:00–09:30), tarde (13:00–14:30) e noite (18:00–20:30). Eventos fora dessas faixas não geram presença. Catracas não precisam enviar `action.mark_presence`.

### Escola com registro livre (modo `always_mark`)
Toda passagem na catraca com aluno identificado marca presença, independente do horário. Útil para escolas com horários flexíveis ou controle integral.

### Integração com sistema externo (modo `explicit_only`)
O sistema externo é responsável por decidir quando marcar presença. Só envia `action.mark_presence=true` nos eventos que devem gerar registro. Eventos sem o flag são ignorados pelo GIDE.

### Implantação gradual (modo `disabled`)
Durante fase de instalação e testes de catraca, o motor fica desabilitado. Eventos são recebidos e auditados, mas nenhuma presença é registrada no iEducar.

---

## Exemplos práticos

### Evento com presença implícita (modo `auto`)
```json
{
  "aluno_id": 42,
  "matricula_id": 100,
  "type": "entrada",
  "occurred_at": "2026-05-12T08:15:00-03:00"
}
```
→ Motor verifica janelas. Se `08:15` cai em uma janela: **`mark_presence`**. Senão: **`ignore`**.

### Mesmo evento em modo `always_mark`
→ **`mark_presence`** imediatamente (janelas ignoradas).

### Mesmo evento em modo `explicit_only`
→ **`ignore`** — `action.mark_presence` não foi enviado.

### Evento com presença explícita
```json
{
  "aluno_id": 42,
  "matricula_id": 100,
  "type": "entrada",
  "occurred_at": "2026-05-12T22:00:00-03:00",
  "action": { "mark_presence": true }
}
```
→ **`mark_presence`** em todos os modos exceto `disabled` (bypass de janela — mesmo às 22h).

### Evento com bloqueio explícito
```json
{
  "aluno_id": 42,
  "matricula_id": 100,
  "type": "entrada",
  "occurred_at": "2026-05-12T08:15:00-03:00",
  "action": { "mark_presence": false }
}
```
→ **`ignore`** — presença bloqueada pelo chamador, **em qualquer modo**.

### Evento de saída
```json
{
  "aluno_id": 42,
  "type": "saida",
  "occurred_at": "2026-05-12T08:15:00-03:00"
}
```
→ **`ignore`** — evento de saída (se `ignore_exit_events=true`).

### Evento na tolerância (modo `auto`, janela 07:00–09:30 ±15min)
```json
{
  "aluno_id": 42,
  "matricula_id": 100,
  "type": "entrada",
  "occurred_at": "2026-05-12T06:48:00-03:00"
}
```
→ **`mark_presence`** — 06:48 está dentro da tolerância (janela efetiva: 06:45–09:45).

### Evento fora da tolerância
```json
{
  "aluno_id": 42,
  "matricula_id": 100,
  "type": "entrada",
  "occurred_at": "2026-05-12T06:40:00-03:00"
}
```
→ **`ignore`** — 06:40 está fora da tolerância. Retorno inclui: "Mais próxima: Matutino (5min de distância)."

### Evento sem aluno_id
```json
{
  "type": "entrada",
  "occurred_at": "2026-05-12T08:15:00-03:00"
}
```
→ **`ignore`** — sem aluno_id/matricula_id, em qualquer modo.

---

## Enriquecimento do aluno

Após a análise do motor, se `aluno_id` for válido (≥ 1), o serviço `StudentEnrichmentService` consulta o iEducar via `postCatracaFrequenciaAlunoConsulta` para buscar dados adicionais (nome, turma, série, etapa, situação). O resultado é:

- **Cacheado** na tabela `student_enrichment_cache` (TTL: 24h)
- **Gravado** em `analysis_json.enrichment` da delivery
- **Exibido** no card "Dados do aluno" na tela admin de detalhe e na timeline

Isso permite visibilidade completa sem alterar o fluxo de marcação de presença (Plan B continua enviando apenas `cod_aluno`).

## Correlação de requests

Toda request à API recebe um `X-Request-Id` (UUID v4) via middleware `AssignRequestId`. Este ID é:

- Retornado no header `X-Request-Id` da response
- Gravado em `analysis_json.request_id` de cada delivery
- Propagado ao `UserAuditLogger` (campo `meta.request_id`)
- Compartilhado com logs do Laravel via `Log::shareContext()`

Permite rastrear o caminho completo de um evento desde o POST até o resultado no iEducar.
