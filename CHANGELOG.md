# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.3.0] - 2026-10-30

### Added

- `JsonLd\Graph`: a page's structured data gathered as `headMeta()` gathers its tags. The SEO head, other packages
  and the page's template append nodes; the layout writes them once, as one graph in one script element.
- JSON-LD on pages other than the home page: an `Article` (give `PageContext` an `Article`) published by the site's
  owner, and a `BreadcrumbList` from the page's `path`, each step named from its segment and the last from the
  page's title.
- `HtmlRenderer::tags()`: the title and meta tags without the script, for a template that writes its own `Graph`.
- `JsonLd::nodes()`: the home page's nodes, for a `Graph`.

### Changed

- `Head::$jsonLd` is the page's list of JSON-LD nodes, in place of a `JsonLd` or null. `HtmlRenderer::render()`
  writes them as before.

## [0.2.0] - 2026-10-23

### Changed

- `Organisation` holds the `Business` from Office details, read by contenir/contenir-contact, with the logo and
  profiles, in place of its `name` and `type`. Code that built an `Organisation` passes a `Business`, or null.
  contenir/contenir-contact `^0.1` is required.

### Deprecated

- `SettingKeys::ORGANISATION_NAME`, `SettingKeys::ORGANISATION_TYPE` and `OrganisationType`: no longer read; the
  business's name and type are Office details'. They stay for the SEO add-on's settings form until it drops them.

### Added

- The home page's JSON-LD describes the business from Office details: its business type and additional types, legal
  name and ABN, and, at its primary office, its address, telephone, email, ContactPoint, position, opening hours and
  type-specific values, as far as its type takes them. With more than one office shown to search engines, each is
  listed under `location` with an `@id` of its own.

## [0.1.0] - 2026-10-21

### Changed

- The package is named `contenir/contenir-seo`, after its repository, as Contenir's other libraries are. Require it
  as `contenir/contenir-seo`. Namespaces are unchanged.

### Added

- `SeoConfig::fromSettings()`, which reads a site's SEO settings under the keys in `SettingKeys`. Values the CMS
  already had keep their keys (`site.title`, `site.url`, `site.sitemap.enabled`, `google.site_verification`); the
  rest sit under `extensions.seo`. `SeoConfig::fromCoreSettings()` reads only the core keys, for sites without the
  SEO add-on.
- Title templates (`Title\TitleTemplate`): the site's name after, before or left out of each page's title, with a
  chosen separator and an optional home page title.
- Robots directives (`Robots\RobotsDirective`) and robots.txt (`Robots\RobotsTxt`): everything allowed, the site's
  extra rules (`Robots\RobotsRules`, checked line by line), and the sitemap on the configured site URL.
  `/.well-known/` is never blocked.
- Hiding a copy of the site from search engines (`Visibility\Visibility`, SEO-3): Automatic, the default, hides every
  host that isn't the configured site URL's, so a staging or local copy is hidden without anyone saving anything and
  production never is by accident. Without a host to compare, such as in a console script, nothing is hidden.
- Site verification tokens for Google, Bing, Pinterest and Meta (`Verification\*`). A pasted meta tag is reduced to
  its token.
- JSON-LD for the home page (`JsonLd\JsonLd`): the site's owner as an Organization or LocalBusiness, with its logo
  and its profiles from the Social add-on's keys as `sameAs`, and the site as a WebSite it publishes. Handles stored
  before the Social add-on become profile URLs.
- Head tags (`Head\HeadBuilder`, `Head\HtmlRenderer`): the templated title, `og:site_name`, a robots meta tag, the
  verification tags, and the default description and share image (with its alt text and size) for pages without
  their own.
- An XML sitemap (`Sitemap\*`): pages hidden from the menu are listed, chosen workflows and noindex pages are left
  out, only the site's own host is listed, images use Google's image extension, `lastmod` comes from the record, and
  past 50,000 URLs `/sitemap.xml` becomes an index of numbered pages. There is no `changefreq`, `priority` or ping.
