<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sitemap;

use Contenir\Seo\Sitemap\SitemapReference;
use Contenir\Seo\Sitemap\SitemapUrl;
use Contenir\Seo\Sitemap\SitemapWriter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SitemapWriterTest extends TestCase
{
    #[Test]
    public function writesAnEmptySitemap(): void
    {
        static::assertSame(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"/>

                XML,
            (new SitemapWriter())->urlset([]),
        );
    }

    #[Test]
    public function writesAnIndexOfSitemaps(): void
    {
        $xml = (new SitemapWriter())->index([
            new SitemapReference(
                'https://www.example.com/sitemap-1.xml',
                new DateTimeImmutable('2026-10-01T09:30:00+10:00'),
            ),
            new SitemapReference('https://www.example.com/sitemap-2.xml'),
        ]);

        static::assertSame(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                  <sitemap>
                    <loc>https://www.example.com/sitemap-1.xml</loc>
                    <lastmod>2026-10-01T09:30:00+10:00</lastmod>
                  </sitemap>
                  <sitemap>
                    <loc>https://www.example.com/sitemap-2.xml</loc>
                  </sitemap>
                </sitemapindex>

                XML,
            $xml,
        );
    }

    #[Test]
    public function writesUrlsWithTheirDatesAndImagesEscaped(): void
    {
        $xml = (new SitemapWriter())->urlset([
            new SitemapUrl(
                'https://www.example.com/search?q=a&b=<c>',
                new DateTimeImmutable('2026-10-01T09:30:00+00:00'),
                ['https://www.example.com/a.jpg', 'https://cdn.example.net/b.jpg?w=1&h=2'],
            ),
            new SitemapUrl('https://www.example.com/'),
        ]);

        static::assertSame(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
                  <url>
                    <loc>https://www.example.com/search?q=a&amp;b=&lt;c&gt;</loc>
                    <lastmod>2026-10-01T09:30:00+00:00</lastmod>
                    <image:image>
                      <image:loc>https://www.example.com/a.jpg</image:loc>
                    </image:image>
                    <image:image>
                      <image:loc>https://cdn.example.net/b.jpg?w=1&amp;h=2</image:loc>
                    </image:image>
                  </url>
                  <url>
                    <loc>https://www.example.com/</loc>
                  </url>
                </urlset>

                XML,
            $xml,
        );
    }
}
