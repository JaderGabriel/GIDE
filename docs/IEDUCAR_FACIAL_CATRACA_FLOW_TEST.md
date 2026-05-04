# Teste do fluxo iEducar pós-facial (consulta, confirmação, frequência)

Este documento descreve o comando Artisan **`ieducar:facial-catraca-flow:test`**, que reproduz em sequência as chamadas HTTP **GIDE → iEducar** usadas no ecossistema **catraca-frequência**, alinhadas ao fluxo da tela de envio de facial (`FacialSendController`) e ao registro de frequência (contrato Plan B).

## Comando

```bash
php artisan ieducar:facial-catraca-flow:test {cod_aluno} [--idpes=...] [opções]
```

Ajuda interativa:

```bash
php artisan ieducar:facial-catraca-flow:test --help
```

## Pré-requisitos

- Integração **`integrations.key = ieducar`** com `base_url` do i-Educar, **habilitada** (exceto em `--dry-run`, que só monta JSON).
- Bearer para os três endpoints: `integrations.extra.catraca_frequencia.confirmacao_token` **ou** `integrations.auth_token` (mesma regra que `IeducarClient`).
- Para o passo **2 (confirmação)**, o iEducar exige **`idpes`** no payload (igual à tela `/facial/enviar` após sucesso no Gestor).

Para o modo **`meta.preview`** do passo **3 (frequência)**:

- O comando lê o rótulo **`integrations.key = gestor`**, campo **`extra.ieducar_processing.environment`** (`preview` ou `homolog`), configurável em **`/integracoes/gestor`**.
- **Não** altera a URL do iEducar: quem define o ambiente (servidor) é sempre `integrations.base_url` da integração **ieducar**. O campo do Gestor só escolhe **simulação vs gravação** no contrato de frequência.

## Etapas executadas

| Ordem | Nome | Método e path (relativo à `base_url` do iEducar) | Quando é omitido |
|------:|------|---------------------------------------------------|-------------------|
| 1 | Consulta de aluno | `POST` `IeducarClient::CAT_FREQUENCIA_ALUNO_CONSULTA_PATH` | `--skip-consulta` |
| 2 | Confirmação facial (coleta) | `POST` `IeducarClient::CAT_FREQUENCIA_CONFIRM_PATH` | `--skip-confirmacao` |
| 3 | Registro de frequência (Plan B) | `POST` `IeducarClient::CAT_FREQUENCIA_REGISTRO_PATH` | `--skip-frequencia` |

O passo **3** envia um registro **unitário** com:

- `fonte`: **`gide`** (rastreio como origem GIDE / fluxo com facial).
- `presente`: **`true`**.
- `identificacao.cod_aluno`: argumento posicional.
- `identificacao.idpes`: opcional; incluído se `--idpes` for um número.
- `data_ref`: dia civil (`--data-ref` ou hoje); antes do POST o GIDE aplica o mesmo ajuste de relógio aleatório que o job (`GideFrequenciaRegistroPlanB::refreshDataRefsWithRandomClock`), para alinhar ao comportamento de **`SendIeducarFrequenciaRegistroJob`**.

## Parâmetros

### Argumento posicional

| Nome | Obrigatório | Descrição |
|------|-------------|-----------|
| `cod_aluno` | Sim | Código do aluno no iEducar, **inteiro ≥ 1**. Usado na consulta (string no JSON), na confirmação e como `identificacao.cod_aluno` no Plan B de frequência. |

### Opções

| Opção | Obrigatório | Descrição |
|-------|-------------|-----------|
| `--idpes` | Sim, se o passo 2 não for pulado | IDPES do aluno (string numérica típica). Obrigatório para **confirmação facial** no mesmo contrato usado na coleta. |
| `--data-ref` | Não | Data **`Y-m-d`** usada como `data_ref` do registro de frequência. Default: **data atual** no fuso `config('app.timezone')`. |
| `--skip-consulta` | Não | Não chama o passo 1. |
| `--skip-confirmacao` | Não | Não chama o passo 2 (útil se só quiser testar frequência). Se usar, `--idpes` deixa de ser obrigatório. |
| `--skip-frequencia` | Não | Não chama o passo 3. |
| `--force-preview` | Não | Força **`meta.preview = true`** no passo 3, **ignorando** o ambiente gravado no Gestor. |
| `--force-apply` | Não | Força **`meta.preview = false`** no passo 3. **Tem precedência** sobre `--force-preview`. |
| `--dry-run` | Não | Imprime URLs (base + path), JSONs finais e a decisão de `meta.preview`; **não envia HTTP**. Não exige integração iEducar habilitada. |

## Regra `meta.preview` (preview vs “homologação” na API)

Implementação: `App\Support\Ieducar\IeducarFrequenciaPreviewMode::resolveMetaPreview`.

| Prioridade | Condição | `meta.preview` no POST frequência |
|------------|----------|-----------------------------------|
| 1 | `--force-apply` | `false` |
| 2 | `--force-preview` | `true` |
| 3 | Gestor `extra.ieducar_processing.environment === 'preview'` | `true` |
| 4 | Caso contrário (`homolog`, vazio ou Gestor inexistente) | `false` |

Interpretação:

- **`meta.preview: true`**: contrato de **simulação** no i-Educar (não persistir alterações de frequência, conforme documentação do módulo catraca-frequência).
- **`meta.preview: false`**: **gravação** no servidor configurado em `integrations` iEducar — use apenas em ambiente de homologação ou produção conforme a sua política.

## Códigos de saída

- **0** (`SUCCESS`): todas as etapas **executadas** (não puladas) terminaram com HTTP **bem-sucedido**, ou só `--dry-run`.
- **1** (`FAILURE`): validação, integração em falta, iEducar desabilitado (sem `--dry-run`), ou qualquer etapa HTTP com erro / exceção.

## Exemplos

```bash
# Sequência completa (consulta + confirmação + frequência), preview conforme Gestor
php artisan ieducar:facial-catraca-flow:test 211 --idpes=12345678

# Só frequência, forçando simulação no i-Educar
php artisan ieducar:facial-catraca-flow:test 211 --skip-consulta --skip-confirmacao --force-preview

# Ver payloads sem rede
php artisan ieducar:facial-catraca-flow:test 999 --idpes=1 --dry-run

# Data de referência da frequência explícita
php artisan ieducar:facial-catraca-flow:test 211 --idpes=12345678 --data-ref=2026-05-01
```

## Relação com outros comandos

- **`ieducar:catraca-frequencia:confirmacao`** e **`ieducar:catraca-frequencia:aluno-consulta`**: testam **um** endpoint isolado cada.
- **`ieducar:catraca-frequencia:frequencia-registro`**: bateria / série de registros e fila administrativa (`IeducarFrequenciaRegistroDelivery`).
- **`ieducar:facial-catraca-flow:test`**: **cadeia única** documentada para o cenário “pós-facial” + uma frequência com `fonte=gide`, com **`meta.preview`** amarrado ao flag do Gestor.

## Referências no código

- `App\Console\Commands\IeducarFacialCatracaFlowTestCommand`
- `App\Services\Ieducar\IeducarClient`
- `App\Support\Ieducar\GideFrequenciaRegistroPlanB`
- `App\Jobs\SendIeducarFrequenciaRegistroJob`
- `App\Http\Controllers\Web\FacialSendController` (consulta na abertura; confirmação após enroll OK no Gestor)
