<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use function array_filter;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function implode;
use function rawurldecode;
use function str_replace;
use function ucfirst;

/**
 * A page's trail from the home page as a JSON-LD BreadcrumbList, one step
 * for each segment of its path. A step is named from its segment ("our-work"
 * is "Our work"), and the last from the page's own title when it has one.
 *
 * Given the site's pages, a step takes the name its page has there, and a
 * step between the home page and this one that is no page of the site, as
 * /work is not where projects at /work/a-project have no index, is left out.
 *
 * @internal
 */
final class BreadcrumbNode
{
    /**
     * The trail, or none for the home page.
     *
     * @param string  $home     The home page's address, with its trailing slash
     * @param string  $siteName The home page's step's name
     * @param string  $path     The page's path, such as /work/a-project
     * @param ?string $title    The page's own title, for the last step
     * @param ?array<string, string> $pages The site's pages' names by path, such as ['/work' => 'Our work']
     *
     * @return array<string, mixed>
     */
    public static function of(
        string $home,
        string $siteName,
        string $path,
        ?string $title = null,
        ?array $pages = null,
    ): array {
        $segments = array_values(array_filter(
            explode('/', $path),
            static fn(string $segment): bool => '' !== $segment,
        ));
        if ([] === $segments) {
            return [];
        }

        $steps = [['@type' => 'ListItem', 'position' => 1, 'name' => $siteName, 'item' => $home]];
        foreach ($segments as $index => $segment) {
            $trail = implode('/', array_slice($segments, offset: 0, length: $index + 1));
            $named = null === $pages ? self::name($segment) : $pages["/{$trail}"] ?? null;
            $last  = (count($segments) - 1) === $index;
            if (! $last && null === $named) {
                continue;
            }

            $steps[] = [
                '@type'    => 'ListItem',
                'position' => count($steps) + 1,
                'name'     => $last && null !== $title && '' !== $title ? $title : $named ?? self::name($segment),
                'item'     => $home . $trail,
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $steps];
    }

    private static function name(string $segment): string
    {
        return ucfirst(str_replace(['-', '_'], replace: ' ', subject: rawurldecode($segment)));
    }
}
