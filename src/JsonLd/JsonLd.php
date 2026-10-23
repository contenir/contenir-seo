<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Seo\Organisation\Organisation;
use JsonException;

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
 * The home page's structured data: the site's owner, as the business type
 * Office details gives it, at its primary office and with its locations, and
 * the site as a WebSite it publishes, in one graph.
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
     * The graph, or an empty array when there is no site URL to identify
     * its nodes by.
     *
     * @return array<string, mixed>
     */
    public function graph(): array
    {
        if ('' === $this->siteUrl) {
            return [];
        }

        $home         = "{$this->siteUrl}/";
        $organisation = OrganisationNode::of($this->organisation, $home, $this->siteName);

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                $organisation,
                [
                    '@type'     => 'WebSite',
                    '@id'       => "{$home}#website",
                    'url'       => $home,
                    'name'      => $this->siteName,
                    'publisher' => ['@id' => "{$home}#organization"],
                ],
            ],
        ];
    }

    /**
     * The graph as a script element, or '' when there is none. The JSON
     * can't close the element: <, >, & and quotes are escaped.
     *
     * @param ?string $nonce A CSP nonce for the element
     *
     * @throws JsonException Never in practice: values are strings, and invalid UTF-8 is substituted.
     */
    public function script(?string $nonce = null): string
    {
        $graph = $this->graph();
        if ([] === $graph) {
            return '';
        }

        $json = json_encode(
            $graph,
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
}
