<?php

declare(strict_types=1);

namespace Contenir\Seo\Robots;

use function array_map;
use function explode;
use function in_array;
use function strtolower;
use function trim;

/**
 * Whether search engines may index a page and follow its links: the
 * content of a robots meta tag or an X-Robots-Tag header.
 *
 * @api
 */
enum RobotsDirective: string
{
    case IndexFollow = 'index, follow';

    case NoindexFollow = 'noindex, follow';

    case IndexNofollow = 'index, nofollow';

    case NoindexNofollow = 'noindex, nofollow';

    /**
     * Labels for a settings field, by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::IndexFollow->value     => 'Show pages in search results and follow their links',
            self::NoindexFollow->value   => 'Keep pages out of search results, but follow their links',
            self::IndexNofollow->value   => 'Show pages in search results, but don\'t follow their links',
            self::NoindexNofollow->value => 'Keep pages out of search results and don\'t follow their links',
        ];
    }

    /**
     * Reads a robots value as sites and editors write it ("noindex",
     * "NOINDEX,NOFOLLOW", "none", "all"). Anything it doesn't mention
     * stays allowed, as search engines treat it; null for a blank value.
     */
    public static function parse(?string $value): ?self
    {
        $words = array_map(
            static fn(string $word): string => strtolower(trim($word)),
            explode(
                separator: ',',
                string: (string) $value,
            ),
        );
        if ([''] === $words) {
            return null;
        }

        $none   = in_array('none', $words, strict: true);
        $index  = ! $none && ! in_array('noindex', $words, strict: true);
        $follow = ! $none && ! in_array('nofollow', $words, strict: true);

        return match (true) {
            $index && $follow => self::IndexFollow,
            $follow => self::NoindexFollow,
            $index  => self::IndexNofollow,
            default => self::NoindexNofollow,
        };
    }

    public function indexes(): bool
    {
        return self::IndexFollow === $this || self::IndexNofollow === $this;
    }
}
