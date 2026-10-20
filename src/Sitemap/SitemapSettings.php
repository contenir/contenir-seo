<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use function array_filter;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Whether a site publishes a sitemap, and which workflows it leaves out of
 * it.
 *
 * @api
 */
final readonly class SitemapSettings
{
    /**
     * @param list<string> $excluded Workflow names, in lower case
     */
    public function __construct(
        public bool $enabled = true,
        public array $excluded = [],
    ) {}

    /**
     * Workflow names as an editor types them, separated by commas, spaces or
     * new lines.
     *
     * @return list<string>
     */
    public static function workflows(string $text): array
    {
        $names = explode(
            separator: ',',
            string: str_replace(
                search: [' ', "\t", "\r", "\n"],
                replace: ',',
                subject: strtolower($text),
            ),
        );

        return array_values(array_unique(array_filter($names, static fn(string $name): bool => '' !== $name)));
    }

    public function excludes(?string $workflow): bool
    {
        return null !== $workflow && in_array(strtolower(trim($workflow)), $this->excluded, strict: true);
    }
}
