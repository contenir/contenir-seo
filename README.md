# contenir/seo

[![Continuous Integration](https://github.com/contenir/contenir-seo/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-seo/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-seo/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-seo)

Framework-free SEO for [Contenir](https://github.com/contenir) sites:

- page titles from a template, so sites stop hard-coding " | Site";
- the default description and share image, `og:site_name` and a robots meta tag;
- site verification for Google, Bing, Pinterest and Meta;
- JSON-LD Organization and WebSite on the home page;
- robots.txt from settings, with extra rules;
- an XML sitemap with images, which becomes an index past 50,000 URLs;
- hiding staging and local copies of a site from search engines.

It has no framework dependency. Sites use it through an adapter, and the CMS through an add-on:

- **Laminas MVC sites:** [contenir/seo-laminas-mvc](https://github.com/contenir/contenir-seo-laminas-mvc) serves
  `/sitemap.xml` and `/robots.txt`, and adds an `seo()` view helper for the head. It replaces
  contenir/contenir-sitemap-laminas-mvc.
- **The CMS:** the [SEO add-on](https://github.com/contenir-extension/seo) (`contenir-extension/seo`) holds the
  settings and checks each value as it is saved.

## Requirements

- PHP 8.3, 8.4 or 8.5, with the `xmlwriter` extension (on by default)

## Settings

`SeoConfig::fromSettings($settings)` reads a site's settings, as nested arrays (the CMS's `config['settings']`), under
these keys (`SettingKeys`). Values the CMS already had keep their keys, so moving a site to the add-on changes nothing:

| Key | Holds | When missing |
|---|---|---|
| `site.url` | The site's URL; every link robots.txt, the sitemap and JSON-LD write is on it | links fall back to the request in the adapter |
| `site.title` | The site's name, as the CMS's Site section holds it | |
| `extensions.seo.site_name` | The name titles and share previews use | `site.title` |
| `extensions.seo.title_separator` | `\|`, `–`, `-`, `·`, `•`, `/` or `:` | `\|` |
| `extensions.seo.title_position` | `after`, `before` or `none` | `after` |
| `extensions.seo.home_title` | The home page's whole title | built like any other |
| `extensions.seo.description` | The description for pages without their own | none |
| `extensions.seo.share_image` | The share image for pages without their own, ideally 1200 × 630 | none |
| `extensions.seo.share_image_alt` | Its alt text | none |
| `extensions.seo.robots` | `index, follow`, `noindex, follow`, `index, nofollow` or `noindex, nofollow` | `index, follow` |
| `extensions.seo.visibility` | `auto`, `hide` or `show`; kept in local settings | `auto` |
| `google.site_verification` | Google Search Console's token | none |
| `extensions.seo.verify_bing`, `verify_pinterest`, `verify_meta` | The other services' tokens | none |
| `extensions.seo.organisation_name` | The site's owner | the site's name |
| `extensions.seo.organisation_type` | `Organization` or `LocalBusiness` | `Organization` |
| `extensions.seo.organisation_logo` | Its logo | none |
| `social.social_*` | Profile URLs, the Social add-on's keys (`facebook`, `instagram`, `linkedin`, `twitter`, `youtube`, `tiktok`, `pinterest`, `threads`, `bluesky`, `mastodon`) | none |
| `site.sitemap.enabled` | Whether the site publishes a sitemap | on |
| `extensions.seo.sitemap_exclude` | Workflows to leave out of the sitemap (`redirect, faq`) | none |
| `extensions.seo.robots_rules` | Extra robots.txt lines | none |

A verification token or robots.txt line that isn't valid is left out, and `SeoConfig::$problems` says why, by key.
Images may be paths on the site or absolute URLs.

`SeoConfig::fromCoreSettings($settings)` reads only `site.url`, `site.title` and `site.sitemap.enabled`. Adapters use
it when the SEO add-on isn't enabled, so a site keeps today's sitemap and robots.txt and nothing is hidden.

## The head

```php
use Contenir\Seo\Head\HeadBuilder;
use Contenir\Seo\Head\HtmlRenderer;
use Contenir\Seo\Head\PageContext;
use Contenir\Seo\SeoConfig;

$config = SeoConfig::fromSettings($settings);
$head   = (new HeadBuilder($config))->build(new PageContext(
    title: $page->title,              // the page's own title
    home: $isHome,
    host: $request->getHost(),
    hasDescription: $pageHasDescription,
    hasImage: $pageHasImage,
));

echo (new HtmlRenderer())->render($head, $nonce);
```

`HeadBuilder` adds, in this order:

| Tag | When |
|---|---|
| `<title>` | always, from the template; the home page title as it is, when set |
| `og:site_name` | the site has a name |
| `robots` | this copy is hidden (`noindex, nofollow`), or the site's default isn't `index, follow` |
| `google-site-verification`, `msvalidate.01`, `p:domain_verify`, `facebook-domain-verification` | on the home page, for each token set |
| `description`, `og:description`, `twitter:description` | the page has no description of its own |
| `og:image`, `og:image:width`, `og:image:height`, `og:image:alt`, `twitter:card` (`summary_large_image`), `twitter:image`, `twitter:image:alt` | the page has no image of its own; the size when the site measured it (`SeoConfig::withShareImage()`) |
| JSON-LD Organization and WebSite | on the home page, when the site URL is set |

Every value is plain text, and `HtmlRenderer` escapes it. The JSON-LD can't close its own script element: `<`, `>`,
`&` and quotes are escaped as JSON.

## Hiding a copy of the site

A copy is hidden by `extensions.seo.visibility`, kept in each environment's local settings:

| Value | Hides |
|---|---|
| `auto` | every request whose host isn't the site URL's; nothing without a site URL, or without a host (a console script) |
| `hide` | everything |
| `show` | nothing |

A hidden copy sends `X-Robots-Tag: noindex, nofollow` (the adapter's job), writes a `noindex, nofollow` robots meta
tag, and its robots.txt disallows everything and names no sitemap.

## robots.txt

```php
use Contenir\Seo\Robots\RobotsTxt;

echo (new RobotsTxt(
    $config->site->origin,
    hidden: $config->visibility->hides($config->site, $host),
    sitemap: $config->sitemap->enabled ? "{$config->site->origin}/sitemap.xml" : null,
    rules: $config->robotsRules,
))->render();
```

```text
# robots.txt for https://www.example.com/

User-agent: *
Allow: /

Disallow: /search

Sitemap: https://www.example.com/sitemap.xml
```

Extra rules may use `User-agent:`, `Allow:`, `Disallow:`, `Crawl-delay:`, `Sitemap:` and `#` comments.
`RobotsRules::problems()` reports any other line, by number.

## The sitemap

```php
use Contenir\Seo\Sitemap\Page;
use Contenir\Seo\Sitemap\Sitemap;
use Contenir\Seo\Sitemap\SitemapFilter;
use Contenir\Seo\Sitemap\SitemapWriter;

$sitemap = new Sitemap(
    [new Page('/about', $modified, images: ['/img/team.jpg'], robots: null, workflow: 'page')],
    new SitemapFilter($config->site, $config->sitemap, $config->robots),
);

$writer = new SitemapWriter();
echo $sitemap->isIndex()
    ? $writer->index($sitemap->references('https://www.example.com/sitemap-%d.xml'))
    : $writer->urlset($sitemap->page(1));
```

A page is listed when it is on the site's own host, its workflow isn't left out, and search engines may index it
(its own robots value, or else the site's default). Pages hidden from the menu are listed like any other. Each
location is listed once, without its fragment, with `lastmod` from the record and up to 1,000 images. There is no
`changefreq` or `priority`, which Google ignores, and no ping, which Google retired in 2023.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
