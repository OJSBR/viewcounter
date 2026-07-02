# View Counter — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.2.0.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

A generic plugin for **Open Journal Systems (OJS)** that displays each article's
**abstract views** and **downloads** (sum of galleys) on the article summary lists
and on the article landing page — discreetly, as icons with a tooltip near the title.

> Maintained by **[OJSBR](https://ojsbr.com.br)**. Rewritten and adapted from the
> original OJS 3.3 plugin by **STI FFLCH** and **ABCD/USP**.

## Compatibility / branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.2.0.0 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 1.1.0.0 |

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

Both are enabled by default. Saving clears the template cache automatically.

## Compatibility notes (OJS 3.5)

- Namespaced class `APP\plugins\generic\viewcounter\ViewcounterPlugin` (PSR-4 autoload).
- In OJS 3.5 `Submission::getViews()` and `Galley::getViews()` were removed. Counts are
  computed by the plugin via the statistics service (`publicationStats`) and exposed to
  Smarty through the `{viewcounterStats}` function. Queries are wrapped in `try/catch`:
  if statistics are unavailable it shows `0` instead of breaking the page.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version
you are working against.

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE).

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que mostra a quantidade de
**visualizações do resumo** e de **downloads** (soma das galés) de cada artigo, nas
listas de resumo e na página do artigo — de forma discreta, com ícones e tooltip
próximos ao título.

> Mantido pela **[OJSBR](https://ojsbr.com.br)**. Reescrito e adaptado a partir do
> plugin original (OJS 3.3) da **STI FFLCH** e do **ABCD/USP**.

### Compatibilidade / branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | `stable-3_5_0` *(padrão)* | 1.2.0.0 |
| OJS 3.4.x     | `stable-3_4_0` | 1.1.0.0 |

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a
pasta em `plugins/generic/` (ficando `plugins/generic/viewcounter/`). Depois ative o
**Contador de visualizações** na lista de plugins *Genéricos*.

### Configuração

Em **Configurações → Website → Plugins → Contador de visualizações → Configurações**:

- **Exibir no resumo** (listas de artigos)
- **Exibir na página de metadados do artigo**

(Padrão: ambos ativos.) Ao salvar, o cache de templates é limpo automaticamente.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE).
