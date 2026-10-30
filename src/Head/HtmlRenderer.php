<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

use Contenir\Seo\JsonLd\Graph;
use JsonException;

use function array_filter;
use function htmlspecialchars;
use function implode;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Writes a Head as HTML for any template engine: output it unescaped in
 * <head>. Sites on laminas-view write it through the head helpers instead
 * (contenir/contenir-seo-laminas-mvc).
 *
 * @api
 */
final class HtmlRenderer
{
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }

    /**
     * The <title> (when there is one), the meta tags, then the JSON-LD
     * script with $nonce.
     *
     * @throws JsonException Never in practice; see Graph::script().
     */
    public function render(Head $head, ?string $nonce = null): string
    {
        $script = (new Graph())->append(...$head->jsonLd)
            ->script($nonce);

        return implode("\n", array_filter(
            [$this->tags($head), $script],
            static fn(string $part): bool => '' !== $part,
        ));
    }

    /**
     * The <title> (when there is one) and the meta tags, for a template that
     * gathers the JSON-LD in a Graph of its own.
     */
    public function tags(Head $head): string
    {
        $lines = '' === $head->title ? [] : [sprintf('<title>%s</title>', self::escape($head->title))];
        foreach ($head->tags as $tag) {
            $lines[] = sprintf(
                '<meta %s="%s" content="%s">',
                $tag->attribute,
                self::escape($tag->key),
                self::escape($tag->content),
            );
        }

        return implode("\n", $lines);
    }
}
