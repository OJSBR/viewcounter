# View Counter — OJS plugin (OJS 3.4 branch)

[![OJS](https://img.shields.io/badge/OJS-3.4-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.1.0.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

> **This is the `stable-3_4_0` branch (OJS 3.4).** For OJS 3.5 use the
> [`stable-3_5_0`](../../tree/stable-3_5_0) branch.

A generic plugin for **Open Journal Systems (OJS)** that displays each article's
**abstract views** and **downloads** (per-galley) on the article summary lists and on
the article landing page.

> Maintained by **[OJSBR](https://ojsbr.com.br)**. Rewritten and adapted from the
> original OJS 3.3 plugin by **STI FFLCH** and **ABCD/USP**.

## Compatibility / branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.2.0.0 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) *(this branch)* | 1.1.0.0 |

## Installation

Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the
folder into `plugins/generic/` (giving `plugins/generic/viewcounter/`). Then enable
**View counter** under the *Generic* plugins list.

## Configuration

Go to **Settings → Website → Plugins → View counter → Settings** and choose whether to
show the counters in the summary lists and/or on the article landing page.

## Notes (OJS 3.4)

- PSR-4 namespaced class `APP\plugins\generic\viewcounter\ViewcounterPlugin`
  (the class name must be `ucfirst(directory)+Plugin` for 3.4 autoload).
- Uses `PKP\plugins\Hook` (the 3.3 `HookRegistry` was replaced).
- No `index.php` — OJS 3.4 autoloads the class via `version.xml`.
- Templates extend the native OJS 3.4 templates, injecting the counters
  (`$article->getViews()` and `$galley->getViews()`).

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE).

---

## 🇧🇷 Português

> **Esta é a branch `stable-3_4_0` (OJS 3.4).** Para OJS 3.5 use a branch
> [`stable-3_5_0`](../../tree/stable-3_5_0).

Plugin genérico para o **Open Journal Systems (OJS)** que mostra as **visualizações do
resumo** e os **downloads** (por galé) de cada artigo, nas listas de resumo e na página
do artigo.

> Mantido pela **[OJSBR](https://ojsbr.com.br)**. Reescrito e adaptado a partir do
> plugin original (OJS 3.3) da **STI FFLCH** e do **ABCD/USP**.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/viewcounter/`). Depois ative o
**Contador de visualizações** na lista de plugins *Genéricos*.

### Notas (OJS 3.4)

- Classe com namespace PSR-4 `APP\plugins\generic\viewcounter\ViewcounterPlugin`.
- `HookRegistry` → `Hook` (`PKP\plugins\Hook`).
- Sem `index.php` (3.4 usa autoload via `version.xml`).
- Templates baseados nos nativos do OJS 3.4, com os contadores injetados
  (`$article->getViews()` e `$galley->getViews()`).

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE).
