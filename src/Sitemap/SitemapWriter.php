<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use XMLWriter;

use const DATE_ATOM;

/**
 * Writes sitemaps and sitemap indexes as XML, with Google's image
 * extension for page images. Every value is escaped by XMLWriter.
 *
 * @api
 */
final class SitemapWriter
{
    public const string XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public const string IMAGE_XMLNS = 'http://www.google.com/schemas/sitemap-image/1.1';

    private static function end(XMLWriter $xml): string
    {
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private static function start(string $root): XMLWriter
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($root);
        $xml->writeAttribute('xmlns', self::XMLNS);

        return $xml;
    }

    /**
     * A sitemap index listing $references.
     *
     * @param list<SitemapReference> $references
     */
    public function index(array $references): string
    {
        $xml = self::start('sitemapindex');
        foreach ($references as $reference) {
            $xml->startElement('sitemap');
            $xml->writeElement('loc', $reference->location);
            if (null !== $reference->modified) {
                $xml->writeElement('lastmod', $reference->modified->format(DATE_ATOM));
            }

            $xml->endElement();
        }

        return self::end($xml);
    }

    /**
     * A sitemap listing $urls.
     *
     * @param list<SitemapUrl> $urls
     */
    public function urlset(array $urls): string
    {
        $xml = self::start('urlset');
        $xml->writeAttribute('xmlns:image', self::IMAGE_XMLNS);
        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url->location);
            if (null !== $url->modified) {
                $xml->writeElement('lastmod', $url->modified->format(DATE_ATOM));
            }

            foreach ($url->images as $image) {
                $xml->startElement('image:image');
                $xml->writeElement('image:loc', $image);
                $xml->endElement();
            }

            $xml->endElement();
        }

        return self::end($xml);
    }
}
