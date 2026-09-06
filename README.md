# View Counter — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.3.0.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/viewcounter/releases/download/1.3.0.0/viewcounter-1.3.0.0.tar.gz) · [OJS 3.4](https://github.com/OJSBR/viewcounter/releases/download/1.1.1.0/viewcounter-1.1.1.0.tar.gz) — or browse all [Releases](../../releases).

A generic plugin for **Open Journal Systems (OJS)** that displays each article's
**abstract views** and **downloads** (sum of galleys) on the article summary lists and on
the article landing page — discreetly, as icons with a tooltip near the title.

> **Maintained by [OJSBR](https://ojsbr.com.br).** Rewritten and adapted from an original
> OJS 3.3 access/downloads counter by **STI-FFLCH/USP** and **ABCD/USP**. See the full
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.3.0.0 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 1.1.1.0 |

Pick the branch matching your OJS version. Each branch is installable as-is.

## Installation

1. Download the release for your OJS version (or clone the matching branch).
2. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the
   folder into `plugins/generic/` so you get `plugins/generic/viewcounter/`.
3. Enable **View counter** under the *Generic* plugins list.

## Configuration

Go to **Settings → Website → Plugins → View counter → Settings**:

- **Show in summary** (article lists)
- **Show on the article landing page**

Both are enabled by default and take effect on the next page load (no cache to clear).

## How it works

- **No core template is replaced.** The badge is injected through the content hooks
  `Templates::Issue::Issue::Article` (summary lists: issue TOC, journal home, search
  results) and `Templates::Article::Main` (article landing page), so core fixes to the
  templates keep reaching your site.
- A small stylesheet and script (`css/viewcounter.css`, `js/viewcounter.js`) move the
  badge next to the title when the theme uses the default markup
  (`.obj_article_summary .title` / `h1.page_title`). If the markup differs, the badge
  simply stays where the hook printed it.
- Counts come from the statistics service (`publicationStats`) and are **cached per
  submission for 24 hours** (`Cache::remember`). On list pages the cache is primed in
  bulk with a single grouped query per metric type, so an issue TOC with 30 articles does
  not run 60 aggregation queries.
- Themes that copied the old templates can keep using `{viewcounterStats submission=$article}`;
  the Smarty function is still registered.

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

Plugin genérico para o **Open Journal Systems (OJS)** que mostra a quantidade de
**visualizações do resumo** e de **downloads** (soma das galés) de cada artigo, nas listas
de resumo e na página do artigo — de forma discreta, com ícones e tooltip próximos ao
título.

> **Mantido pela [OJSBR](https://ojsbr.com.br).** Reescrito e adaptado a partir de um
> contador de acessos/downloads original do OJS 3.3 da **STI-FFLCH/USP** e do **ABCD/USP**.
> Veja a seção [Créditos e autoria](#créditos-e-autoria) abaixo.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | `stable-3_5_0` *(padrão)* | 1.3.0.0 |
| OJS 3.4.x     | `stable-3_4_0` | 1.1.1.0 |

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/viewcounter/`). Depois ative o
**Contador de visualizações** na lista de plugins *Genéricos*.

### Configuração

Em **Configurações → Website → Plugins → Contador de visualizações → Configurações**,
escolha exibir no resumo (listas) e/ou na página do artigo (padrão: ambos ativos). A
mudança vale no próximo carregamento de página, sem limpar cache algum.

### Como funciona

- **Nenhum template do núcleo é substituído.** O selo é injetado pelos hooks de conteúdo
  `Templates::Issue::Issue::Article` (listas: sumário da edição, capa da revista, busca) e
  `Templates::Article::Main` (página do artigo); as correções do núcleo continuam chegando.
- Um CSS e um JS pequenos (`css/viewcounter.css`, `js/viewcounter.js`) movem o selo para
  junto do título quando o tema usa a marcação padrão. Se a marcação for outra, o selo fica
  onde o hook o imprimiu.
- As contagens vêm do serviço de estatísticas e ficam em **cache por 24 horas, por
  submissão**. Nas listas o cache é preenchido em lote, com uma única consulta agrupada por
  tipo de métrica.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com.br) — reescrita e adaptação para OJS 3.4/3.5.
- **Trabalho original:** contador de acessos/downloads do OJS 3.3 da **STI-FFLCH/USP**
  (Seção Técnica de Informática da FFLCH/USP) e do **ABCD/USP** (Agência de Bibliotecas e
  Coleções Digitais da USP). Repositórios abertos da FFLCH: <https://github.com/fflch>.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
