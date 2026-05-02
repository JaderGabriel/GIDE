# Registro de frequência GIDE → i-Educar

O GIDE envia ao endpoint do package **Serventec Catraca Frequência** no i-Educar usando **apenas o formato B (por aluno)** — `cod_aluno` + `data_ref` (e opcionalmente `idpes`); o i-Educar resolve turma, matrícula e turno. O formato legado por `ref_cod_turma` + `ref_cod_matricula` **não** é aceito nesta tela/comando.

## Endpoint no i-Educar

| Item | Valor |
|------|--------|
| Método | `POST` |
| Caminho | `/api/catraca-frequencia/gide/frequencia/registro` |
| Auth | `Authorization: Bearer …` — `integrations.extra.catraca_frequencia.confirmacao_token` ou, se vazio, `integrations.auth_token`. |

## Ferramenta no GIDE (admin)

1. **Integrações → iEducar** — configure `base_url` e token Bearer.
2. **Abrir envio de frequência** — `/integracoes/ieducar/frequencia-registro`
3. **Enfileirar preview** — cria registro `mode=preview`, job na fila; o worker envia com `meta.preview: true` (validação/proposta sem gravar no i-Educar).
4. **Enfileirar gravação** — cria registro `mode=apply`, job na fila; o worker envia com `meta.preview: false`.

**Preview e gravação** passam pela mesma fila e ficam em `ieducar_frequencia_registro_deliveries` (status, HTTP, JSON de resposta).

Requisitos: integração **iEducar habilitada**, `php artisan queue:work` (ou `QUEUE_CONNECTION=sync` em dev).

## Tabela `ieducar_frequencia_registro_deliveries`

| Coluna | Uso |
|--------|-----|
| `mode` | `preview` ou `apply`. |
| `status` | `pending` → `processing` → `completed` ou `failed`. |
| `payload` | JSON enviado (plano B). |
| `response_json` | Corpo JSON da resposta do i-Educar, quando houver. |

## Contrato JSON (plano B — resumo)

- `meta.contract_version`: `"1.0"`
- `meta.preview`: definido pelo GIDE ao enviar (`true` preview / `false` gravação).
- `fonte`: `"gide"` \| `"outras"` (padrão do lote).
- `presente`: boolean (padrão do lote).

**Envio unitário:** `identificacao.cod_aluno` (inteiro), `identificacao.idpes` (opcional), `data_ref` (`Y-m-d`). Não enviar `registros` junto.

**Lote:** `registros[]` (1…2000) com `cod_aluno`, `data_ref`; `fonte` e `presente` opcionais por linha (senão usam os da raiz).

Schema de referência: `gide-frequencia-registro-v1.schema.json` (ramo “Por aluno”).

## Código no GIDE

- Validação/normalização plano B: `App\Support\Ieducar\GideFrequenciaRegistroPlanB::validateAndNormalize()`
- Cliente HTTP: `App\Services\Ieducar\IeducarClient::postCatracaFrequenciaRegistro()`
- Job: `App\Jobs\SendIeducarFrequenciaRegistroJob` (preview e apply)

## CLI

Cada execução **sem `--dry-run`** cria uma linha em `ieducar_frequencia_registro_deliveries` por tentativa, executa o mesmo `SendIeducarFrequenciaRegistroJob` de forma **síncrona** (`dispatchSync`) e imprime o URL do detalhe em **Admin → Fila frequência iEducar**. O preview continua sendo decidido pela API do i-Educar (`meta.preview`); o GIDE só registra e rastreia. Logs estruturados: `ieducar_frequencia_registro.job_*` no canal Laravel padrão.

```bash
php artisan ieducar:catraca-frequencia:frequencia-registro --cod-aluno=211 --dry-run
php artisan ieducar:catraca-frequencia:frequencia-registro --cod-aluno=211 --tentativas=12 --intervalo=1
```
