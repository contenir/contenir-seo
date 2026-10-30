<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use JsonException;
use Override;
use Stringable;

use function htmlspecialchars;
use function json_encode;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * A page's structured data, gathered as headMeta() gathers its tags: the SEO
 * head, other packages and the page's own template each append nodes, and
 * the layout writes them once, as one graph in one script element.
 *
 *     $graph->append(['@type' => 'CreativeWork', 'name' => $project->title]);
 *     echo $graph->script($nonce);
 *
 * One graph is one page's: under a long-running server, make one per request.
 *
 * @api
 */
final class Graph implements Stringable
{
    /** @var list<array<string, mixed>> */
    private array $nodes = [];

    /**
     * Adds nodes after those already in the graph. A node without properties is left out.
     *
     * @param array<string, mixed> ...$nodes
     */
    public function append(array ...$nodes): self
    {
        foreach ($nodes as $node) {
            if ([] === $node) {
                continue;
            }

            $this->nodes[] = $node;
        }

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * The graph as a script element, or '' when it has no nodes. The JSON
     * can't close the element: <, >, & and quotes are escaped.
     *
     * @param ?string $nonce A CSP nonce for the element
     *
     * @throws JsonException Never in practice: values are strings, and invalid UTF-8 is substituted.
     */
    public function script(?string $nonce = null): string
    {
        if ([] === $this->nodes) {
            return '';
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $this->nodes],
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR,
        );

        return sprintf(
            '<script type="application/ld+json"%s>%s</script>',
            null === $nonce || '' === $nonce
                ? ''
                : sprintf(' nonce="%s"', htmlspecialchars(
                    $nonce,
                    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
                    encoding: 'UTF-8',
                )),
            $json,
        );
    }

    /**
     * The script element without a nonce, so a template can echo the graph as it does headScript().
     *
     * @throws JsonException Never in practice; see script().
     */
    #[Override]
    public function __toString(): string
    {
        return $this->script();
    }
}
