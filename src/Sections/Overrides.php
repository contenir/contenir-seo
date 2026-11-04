<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use function is_array;
use function is_string;
use function json_decode;
use function trim;

/**
 * A record's own overrides, as its block's advanced column holds them: a
 * JSON object whose canonical, robots and schema are each a string.
 *
 * @api
 */
final class Overrides
{
    private const array NAMES = ['canonical', 'robots', 'schema'];

    /**
     * Each override $json gives that is a string with something in it, trimmed,
     * by name; none when $json isn't a JSON object.
     *
     * @return array<string, string>
     */
    public static function from(mixed $json): array
    {
        /** @var mixed $decoded */
        $decoded   = is_string($json) ? json_decode($json, associative: true) : null;
        $overrides = [];
        foreach (is_array($decoded) ? self::NAMES : [] as $name) {
            /** @var mixed $value */
            $value = $decoded[$name] ?? null;
            if (is_string($value) && '' !== trim($value)) {
                $overrides[$name] = trim($value);
            }
        }

        return $overrides;
    }
}
