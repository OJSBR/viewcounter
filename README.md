# View Counter — OJS plugin (OJS 3.4 branch)

[![OJS](https://img.shields.io/badge/OJS-3.4-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.1.0.1-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/viewcounter/releases/download/1.2.0.3/viewcounter-1.2.0.3.tar.gz) · [OJS 3.4](https://github.com/OJSBR/viewcounter/releases/download/1.1.0.1/viewcounter-1.1.0.1.tar.gz) — or browse all [Releases](../../releases).

> **This is the `stable-3_4_0` branch (OJS 3.4).** For OJS 3.5 use the
> [`stable-3_5_0`](../../tree/stable-3_5_0) branch.

A generic plugin for **Open Journal Systems (OJS)** that displays each article's
**abstract views** and **downloads** (per galley) on the article summary lists and on the
article landing page.

> **Maintained by [OJSBR](https://ojsbr.com.br).** Rewritten and adapted from an original
> OJS 3.3 access/downloads counter by **STI-FFLCH/USP** and **ABCD/USP**. See the full
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.2.0.3 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) *(this branch)* | 1.1.0.1 |

## Installation

Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
into `plugins/generic/` (giving `plugins/generic/viewcounter/`). Then enable **View
counter** under the *Generic* plugins list.

## Configuration

Go to **Settings → Website → Plugins → View counter → Settings** and choose whether to show
the counters in the summary lists and/or on the article landing page. On OJS 3.4 the counts
use `$article->getViews()` and `$galley->getViews()`.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com.br) — rewrite and adaptation to OJS 3.4/3.5.
- **Original work:** OJS 3.3 access/downloads counter by **STI-FFLCH/USP** (Seção Técnica
  de Informática da FFLCH/USP) and **ABCD/USP** (Agência de Bibliotecas e Coleções Digitais
  da USP). See the FFLCH open-source repositories at <https://github.com/fflch>.
- Distributed under the **GNU GPL v3**, consistent with the original licensing.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version you
are working against.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

> **Esta é a branch `stable-3_4_0` (OJS 3.4).** Para OJS 3.5 use a branch
> [`stable-3_5_0`](../../tree/stable-3_5_0).

Plugin genérico para o **Open Journal Systems (OJS)** que mostra as **visualizações do
resumo** e os **downloads** (por galé) de cada artigo, nas listas de resumo e na página do
artigo.

> **Mantido pela [OJSBR](https://ojsbr.com.br).** Reescrito e adaptado a partir de um
> contador de acessos/downloads original do OJS 3.3 da **STI-FFLCH/USP** e do **ABCD/USP**.
> Veja a seção [Créditos e autoria](#créditos-e-autoria) abaixo.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/viewcounter/`). Depois ative o
**Contador de visualizações** na lista de plugins *Genéricos*.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com.br) — reescrita e adaptação para OJS 3.4/3.5.
- **Trabalho original:** contador de acessos/downloads do OJS 3.3 da **STI-FFLCH/USP** e do
  **ABCD/USP**. Repositórios abertos da FFLCH: <https://github.com/fflch>.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
