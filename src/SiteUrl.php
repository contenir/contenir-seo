<?php

declare(strict_types=1);

namespace Contenir\Seo;

use Contenir\Seo\Internal\Origin;

use function str_starts_with;
use function trim;

/**
 * The site's configured URL, such as https://www.example.com: the origin
 * robots.txt, the sitemap and JSON-LD build their links on. It is the
 * setting, never the request's Host header, which a client can spoof.
 *
 * @api
 */
final readonly class SiteUrl
{
    /**
     * The scheme, host and port, such as https://www.example.com, or '' when
     * the URL has no http(s) scheme and host.
     */
    public string $origin;

    /**
     * The host in lower case, or '' with no origin.
     */
    public string $host;

    public function __construct(string $url = '')
    {
        $origin       = Origin::of($url);
        $this->origin = $origin->origin;
        $this->host   = $origin->host;
    }

    /**
     * $url made absolute on the origin: a path gets the origin, a
     * protocol-relative URL the origin's scheme, and an http(s) URL is kept.
     * Null for any other scheme (javascript:, data:, mailto:…), and for a
     * path when there is no origin.
     */
    public function absolute(string $url): ?string
    {
        $url = trim($url);
        if (str_starts_with($url, '//')) {
            return $this->resolved("{$this->scheme()}:{$url}");
        }

        if (str_starts_with($url, '/')) {
            return '' === $this->origin ? null : "{$this->origin}{$url}";
        }

        return $this->resolved($url);
    }

    public function isSet(): bool
    {
        return '' !== $this->origin;
    }

    /**
     * Whether $url is on this site's host. Without an origin, nothing is.
     */
    public function owns(string $url): bool
    {
        return '' !== $this->host && Origin::of($url)->host === $this->host;
    }

    private function resolved(string $url): ?string
    {
        return '' === Origin::of($url)->origin ? null : $url;
    }

    private function scheme(): string
    {
        return str_starts_with($this->origin, 'http:') ? 'http' : 'https';
    }
}
