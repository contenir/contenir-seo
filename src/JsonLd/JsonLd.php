<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Seo\Organisation\Organisation;
use JsonException;

/**
 * The home page's structured data: the site's owner, as the business type
 * Office details gives it, at its primary office and with its locations, and
 * the site as a WebSite it publishes.
 *
 * @api
 */
final readonly class JsonLd
{
    /**
     * @param string $siteUrl  The site's origin, such as https://www.example.com
     * @param string $siteName The site's name
     */
    public function __construct(
        private string $siteUrl,
        private string $siteName,
        private Organisation $organisation,
    ) {}

    /**
     * The nodes as one graph, or an empty array when there are none.
     *
     * @return array<string, mixed>
     */
    public function graph(): array
    {
        $nodes = $this->nodes();

        return [] === $nodes ? [] : ['@context' => 'https://schema.org', '@graph' => $nodes];
    }

    /**
     * The site's owner and the site, or none when there is no site URL to
     * identify them by.
     *
     * @return list<array<string, mixed>>
     */
    public function nodes(): array
    {
        if ('' === $this->siteUrl) {
            return [];
        }

        $home = "{$this->siteUrl}/";

        return [
            OrganisationNode::of($this->organisation, $home, $this->siteName),
            [
                '@type'     => 'WebSite',
                '@id'       => "{$home}#website",
                'url'       => $home,
                'name'      => $this->siteName,
                'publisher' => ['@id' => "{$home}#organization"],
            ],
        ];
    }

    /**
     * The graph as a script element, or '' when there is none.
     *
     * @param ?string $nonce A CSP nonce for the element
     *
     * @throws JsonException Never in practice; see Graph::script().
     */
    public function script(?string $nonce = null): string
    {
        return (new Graph())->append(...$this->nodes())
            ->script($nonce);
    }
}
