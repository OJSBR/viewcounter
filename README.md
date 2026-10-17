# View Counter — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.1.2.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/viewcounter/releases/download/1.3.1.0/viewcounter-1.3.1.0.tar.gz) · [OJS 3.4](https://github.com/OJSBR/viewcounter/releases/download/1.1.2.0/viewcounter-1.1.2.0.tar.gz) — or browse all [Releases](../../releases).

A generic plugin for **Open Journal Systems (OJS)** that displays each article's
**abstract views** and **downloads** (galley downloads, the same number as Statistics ›
Articles) on the article summary lists and on the article landing page — discreetly, as
icons with a tooltip near the title.

> **Since 1.3.1.0 / 1.1.2.0** downloads of supplementary files are no longer added to the
> count, so the public number matches the one editors see in Statistics › Articles. Where a
> journal has supplementary files, the downloads shown go down after the update: no data is
> lost, the number is just the core one now.

> **Maintained by [OJSBR](https://ojsbr.com).** Rewritten and adapted from an original
> OJS 3.3 access/downloads counter by **STI-FFLCH/USP** and **ABCD/USP**. See the full
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.3.1.0 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 1.1.2.0 |

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
- **Accessibility.** A heading's accessible name is everything inside it, so the badge never
  goes into the title: the copy shown there is hidden from assistive technology
  (`aria-hidden`), and the badge itself — with a visually hidden text for each count,
  e.g. "Views: 12" — sits right after the heading. Screen readers read the title alone,
  then the counts.
- Downloads are galley downloads only (`ASSOC_TYPE_SUBMISSION_FILE`), as in Statistics ›
  Articles; supplementary files are left out.
- When the counts cannot be read, the error is logged and **no badge is shown** — never a
  made-up "0 views, 0 downloads".
- Counts come from the statistics service (`publicationStats`) and are **cached per
  submission for 24 hours** (`Cache::remember`). On list pages (issue TOC, journal home,
  search results and category pages) the cache is primed in bulk with a single grouped
  query per metric type, so an issue TOC with 30 articles does not run 60 aggregation
  queries.
- The stylesheet and the script are loaded with the plugin version in their URL, so a
  browser never keeps the script of a release that expected another markup.
- Themes that copied the old templates can keep using `{viewcounterStats submission=$article}`;
  the Smarty function is still registered.

## Tests

- **PHPUnit** (`tests/*Test.php`, on `PKP\tests\PKPTestCase`): the badge markup and its visually
  hidden texts (no `aria-label` on generic spans), downloads counted from galleys only, no badge
  and a log line when the counts cannot be read, one grouped query per metric for a list — the
  category page included — and the 24-hour cache afterwards, submissions without metrics, the site level without settings, no constant that only exists outside PKP's
  strict mode, the plugin classes against the installed PKP, the 38 translations and the template.
  From the installation root:

  ```bash
  lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage "$PWD/plugins/generic/viewcounter/tests"
  ```

- **Cypress** (`cypress/tests/functional/Viewcounter.cy.js`, run by
  [pkp-github-actions](https://github.com/pkp/pkp-github-actions) on every push): enables the
  plugin, checks the badge next to the titles of the current issue and of an article page — a copy
  hidden from assistive technology in the title, the counts as visually hidden text right after
  the heading, and the heading's accessible name without them — and saves each place on its
  own, putting the setting back.
- Verified on OJS 3.5.0.3 and 3.4.0.10, in the browser too: issue, article and category pages;
  an article with 15 galley downloads and 7 supplementary ones shows 15 (the previous release
  showed 22).

Tests are kept in the repository and are not part of the release package.

## Credits & authorship

- **Maintained by** [OJSBR](https://ojsbr.com) — rewrite and adaptation to OJS 3.4/3.5.
- **Original work:** OJS 3.3 access/downloads counter by **STI-FFLCH/USP** (Seção Técnica
  de Informática da FFLCH/USP) and **ABCD/USP** (Agência de Bibliotecas e Coleções Digitais
  da USP). See the FFLCH open-source repositories at <https://github.com/fflch>.
- Distributed under the **GNU GPL v3**, consistent with the original licensing.

## AI use

Generative AI (Claude, by Anthropic) was used to write and run tests, improve the code and bring
it in line with PKP standards. Every change is reviewed and tested by OJSBR, which is responsible
for the published releases.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version you
are working against.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que mostra a quantidade de
**visualizações do resumo** e de **downloads** (downloads das composições, o mesmo número de
Estatísticas › Artigos) de cada artigo, nas listas de resumo e na página do artigo — de
forma discreta, com ícones e tooltip próximos ao título.

> **Desde a 1.3.1.0 / 1.1.2.0** os downloads de arquivos suplementares deixaram de entrar na
> conta, para o número público bater com o que o editor vê em Estatísticas › Artigos. Nas
> revistas com arquivos suplementares, o número de downloads cai depois da atualização:
> nenhum dado se perde, o número só passa a ser o do núcleo.

> **Mantido pela [OJSBR](https://ojsbr.com).** Reescrito e adaptado a partir de um
> contador de acessos/downloads original do OJS 3.3 da **STI-FFLCH/USP** e do **ABCD/USP**.
> Veja a seção [Créditos e autoria](#créditos-e-autoria) abaixo.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | `stable-3_5_0` *(padrão)* | 1.3.1.0 |
| OJS 3.4.x     | `stable-3_4_0` | 1.1.2.0 |

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
- **Acessibilidade.** O nome acessível de um título é tudo o que está dentro dele, então o
  selo nunca entra no título: a cópia mostrada ali fica oculta para tecnologias assistivas
  (`aria-hidden`), e o selo em si — com um texto visualmente oculto para cada número, ex.
  "Visualizações: 12" — fica logo depois do título. O leitor de tela lê o título sozinho e
  depois os números.
- Downloads são só os das composições (`ASSOC_TYPE_SUBMISSION_FILE`), como em
  Estatísticas › Artigos; arquivos suplementares ficam de fora.
- Quando as contagens não podem ser lidas, o erro vai para o log e **nenhum selo aparece** —
  nunca um "0 visualizações, 0 downloads" inventado.
- As contagens vêm do serviço de estatísticas e ficam em **cache por 24 horas, por
  submissão**. Nas listas (sumário, capa, busca e páginas de categoria) o cache é preenchido
  em lote, com uma única consulta agrupada por tipo de métrica.
- O CSS e o JS são carregados com a versão do plugin na URL, para o navegador nunca ficar
  com o script de uma release que esperava outra marcação.

### Testes

PHPUnit em `tests/` (sobre `PKP\tests\PKPTestCase`) e Cypress em `cypress/tests/functional/`
(rodado pelo [pkp-github-actions](https://github.com/pkp/pkp-github-actions) a cada push), com os
comandos da seção em inglês. A suíte cobre o selo e seus textos visualmente ocultos, downloads só
das composições, nenhum selo e uma linha de log quando a contagem falha, uma consulta agrupada por
métrica nas listas (inclusive a página de categoria) e o cache de 24 horas, submissões sem métricas, o nível do site sem
configurações, as classes contra o PKP instalado, as 38 traduções e o template; o Cypress confere o
selo junto aos títulos do fascículo atual e de um artigo — sem entrar no nome acessível do título —
e o salvamento de cada lugar. Verificado no OJS 3.5.0.3 e 3.4.0.10, também no navegador: um artigo
com 15 downloads de composição e 7 de suplementar mostra 15 (a release anterior mostrava 22).

Os testes ficam no repositório e não fazem parte do pacote da release.

### Créditos e autoria

- **Mantido pela** [OJSBR](https://ojsbr.com) — reescrita e adaptação para OJS 3.4/3.5.
- **Trabalho original:** contador de acessos/downloads do OJS 3.3 da **STI-FFLCH/USP**
  (Seção Técnica de Informática da FFLCH/USP) e do **ABCD/USP** (Agência de Bibliotecas e
  Coleções Digitais da USP). Repositórios abertos da FFLCH: <https://github.com/fflch>.
- Distribuído sob a **GNU GPL v3**, coerente com o licenciamento original.

### Uso de IA

Foi usada IA generativa (Claude, da Anthropic) para escrever e rodar testes, melhorar o código e
alinhá-lo aos padrões da PKP. Toda mudança é revisada e testada pela OJSBR, que responde pelas
releases publicadas.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
