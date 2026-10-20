<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Integration;

use Contenir\Seo\Head\HeadBuilder;
use Contenir\Seo\Head\HtmlRenderer;
use Contenir\Seo\Head\PageContext;
use Contenir\Seo\Robots\RobotsTxt;
use Contenir\Seo\SeoConfig;
use Contenir\Seo\SettingKeys;
use Contenir\Seo\Sitemap\Page;
use Contenir\Seo\Sitemap\Sitemap;
use Contenir\Seo\Sitemap\SitemapFilter;
use Contenir\Seo\Sitemap\SitemapWriter;
use Contenir\Seo\Tests\TestAsset\Factory\SettingsFactory;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A site's settings, as the CMS saves them, through to what its pages,
 * robots.txt and sitemap say.
 */
#[Group('integration')]
final class PageTest extends TestCase
{
    /**
     * @return array<array-key, mixed>
     */
    private static function settings(): array
    {
        return SettingsFactory::site([
            SettingKeys::DESCRIPTION       => 'Handmade furniture from Point Lonsdale.',
            SettingKeys::SHARE_IMAGE       => '/asset/img/seo/share.jpg',
            SettingKeys::SHARE_IMAGE_ALT   => 'The workshop',
            SettingKeys::VERIFY_GOOGLE     => 'g-token',
            SettingKeys::ORGANISATION_TYPE => 'LocalBusiness',
            SettingKeys::SITEMAP_EXCLUDE   => 'redirect',
            SettingKeys::ROBOTS_RULES      => 'Disallow: /search',
            'social.social_instagram'      => 'example',
        ]);
    }

    #[Test]
    public function hidesAStagingCopyEverywhere(): void
    {
        $config = SeoConfig::fromSettings(self::settings());
        $head   = (new HeadBuilder($config))->build(new PageContext('About', host: 'staging.example.com'));
        $robots = new RobotsTxt(
            'https://staging.example.com',
            $head->hidden,
            'https://staging.example.com/sitemap.xml',
        );

        static::assertSame(
            [
                true,
                "# robots.txt for https://staging.example.com/\n\n# This copy of the site is hidden from search engines.\nUser-agent: *\nDisallow: /\n",
            ],
            [$head->hidden, $robots->render()],
        );
    }

    #[Test]
    public function listsTheSitesPagesInItsSitemap(): void
    {
        $config  = SeoConfig::fromSettings(self::settings());
        $sitemap = new Sitemap(
            [
                new Page('/', new DateTimeImmutable('2026-10-20T08:00:00+00:00'), ['/asset/img/home.jpg']),
                new Page('/old-page', workflow: 'redirect'),
                new Page('/thanks', robots: 'noindex'),
            ],
            new SitemapFilter($config->site, $config->sitemap, $config->robots),
        );

        static::assertSame(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
                  <url>
                    <loc>https://www.example.com/</loc>
                    <lastmod>2026-10-20T08:00:00+00:00</lastmod>
                    <image:image>
                      <image:loc>https://www.example.com/asset/img/home.jpg</image:loc>
                    </image:image>
                  </url>
                </urlset>

                XML,
            (new SitemapWriter())->urlset($sitemap->page(1)),
        );
    }

    #[Test]
    public function writesTheHomePagesHead(): void
    {
        $head = (new HeadBuilder(SeoConfig::fromSettings(self::settings())))->build(
            new PageContext('Home', home: true, host: 'www.example.com'),
        );

        static::assertSame(
            <<<'HTML'
                <title>Home | Example</title>
                <meta property="og:site_name" content="Example">
                <meta name="google-site-verification" content="g-token">
                <meta name="description" content="Handmade furniture from Point Lonsdale.">
                <meta property="og:description" content="Handmade furniture from Point Lonsdale.">
                <meta name="twitter:description" content="Handmade furniture from Point Lonsdale.">
                <meta property="og:image" content="https://www.example.com/asset/img/seo/share.jpg">
                <meta property="og:image:alt" content="The workshop">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:image" content="https://www.example.com/asset/img/seo/share.jpg">
                <meta name="twitter:image:alt" content="The workshop">
                <script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"LocalBusiness","@id":"https://www.example.com/#organization","name":"Example","url":"https://www.example.com/","sameAs":["https://www.instagram.com/example"]},{"@type":"WebSite","@id":"https://www.example.com/#website","url":"https://www.example.com/","name":"Example","publisher":{"@id":"https://www.example.com/#organization"}}]}</script>
                HTML,
            (new HtmlRenderer())->render($head),
        );
    }

    #[Test]
    public function writesTheLiveSitesRobotsTxt(): void
    {
        $config = SeoConfig::fromSettings(self::settings());
        $robots = new RobotsTxt(
            $config->site->origin,
            $config->visibility->hides($config->site, 'www.example.com'),
            'https://www.example.com/sitemap.xml',
            $config->robotsRules,
        );

        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nAllow: /\n\nDisallow: /search\n\n"
                . "Sitemap: https://www.example.com/sitemap.xml\n",
            $robots->render(),
        );
    }
}
