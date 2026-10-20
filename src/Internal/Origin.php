<?php

declare(strict_types=1);

namespace Contenir\Seo\Internal;

use function in_array;
use function is_int;
use function is_string;
use function parse_url;
use function rtrim;
use function strtolower;
use function trim;

use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * The origin of an http(s) URL: its scheme, host and port.
 *
 * @internal
 */
final readonly class Origin
{
    /**
     * @param string $origin Such as https://www.example.com:8080, or '' when the URL has none
     * @param string $host   The host in lower case, or '' when the URL has no origin
     */
    private function __construct(
        public string $origin,
        public string $host,
    ) {}

    public static function of(string $url): self
    {
        $url    = trim($url);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host   = parse_url($url, PHP_URL_HOST);
        $port   = parse_url($url, PHP_URL_PORT);
        $scheme = is_string($scheme) ? strtolower($scheme) : '';
        $host   = is_string($host) ? strtolower(rtrim($host, characters: '.')) : '';
        if ('' === $host || ! in_array($scheme, ['http', 'https'], strict: true)) {
            return new self('', '');
        }

        $port = is_int($port) ? ":{$port}" : '';

        return new self("{$scheme}://{$host}{$port}", $host);
    }
}
