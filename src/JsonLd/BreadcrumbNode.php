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
     *
     * @return array<string, mixed>
     */
    public static function of(string $home, string $siteName, string $path, ?string $title = null): array
    {
        $segments = array_values(array_filter(
            explode('/', $path),
            static fn(string $segment): bool => '' !== $segment,
        ));
        if ([] === $segments) {
            return [];
        }

        $steps = [['@type' => 'ListItem', 'position' => 1, 'name' => $siteName, 'item' => $home]];
        foreach ($segments as $index => $segment) {
            $last    = (count($segments) - 1) === $index;
            $steps[] = [
                '@type'    => 'ListItem',
                'position' => $index + 2,
                'name'     => $last && null !== $title && '' !== $title ? $title : self::name($segment),
                'item'     => $home . implode('/', array_slice($segments, offset: 0, length: $index + 1)),
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $steps];
    }

    private static function name(string $segment): string
    {
        return ucfirst(str_replace(['-', '_'], replace: ' ', subject: rawurldecode($segment)));
    }
}
