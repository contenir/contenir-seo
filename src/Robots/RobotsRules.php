<?php

declare(strict_types=1);

namespace Contenir\Seo\Robots;

use function array_filter;
use function array_key_exists;
use function array_map;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Extra robots.txt lines a site adds to the ones built from its settings:
 * rules for the paths it wants kept out (Disallow: /search), or groups for
 * other crawlers (User-agent: GPTBot, then Disallow: /). Only the
 * directives RFC 9309 defines, and the common Crawl-delay and Sitemap, are
 * accepted.
 *
 * @api
 */
final readonly class RobotsRules
{
    /**
     * @param list<string> $lines The rules, one per line, without blank lines at either end
     */
    public function __construct(
        public array $lines = [],
    ) {}

    /**
     * The rules in $text, each directive written as robots.txt spells it.
     * Lines that aren't rules are left out; problems() says which.
     */
    public static function parse(string $text): self
    {
        $rules = trim(implode("\n", array_filter(
            array_map(self::rule(...), self::split($text)),
            static fn(?string $line): bool => null !== $line,
        )));

        return new self(
            '' === $rules
                ? []
                : explode(
                    separator: "\n",
                    string: $rules,
                ),
        );
    }

    /**
     * What is wrong with $text, a message for each line that isn't a rule.
     *
     * @return list<string>
     */
    public static function problems(string $text): array
    {
        $problems = [];
        foreach (self::split($text) as $number => $line) {
            if (null !== self::rule($line)) {
                continue;
            }

            $problems[] = sprintf(
                'Line %d isn\'t a robots.txt rule. Start each line with User-agent:, Allow:, Disallow:,'
                    . ' Crawl-delay: or Sitemap:, or with # for a comment.',
                $number + 1,
            );
        }

        return $problems;
    }

    /**
     * The directive $name is, as robots.txt spells it, or null when it isn't
     * one this accepts.
     */
    private static function field(string $name): ?string
    {
        return match (strtolower(trim($name))) {
            'user-agent'  => 'User-agent',
            'allow'       => 'Allow',
            'disallow'    => 'Disallow',
            'crawl-delay' => 'Crawl-delay',
            'sitemap'     => 'Sitemap',
            default       => null,
        };
    }

    private static function isPath(string $value): bool
    {
        return str_starts_with($value, '/') || str_starts_with($value, '*');
    }

    /**
     * $line as robots.txt writes it, or null when it isn't a rule, a
     * comment or blank.
     */
    private static function rule(string $line): ?string
    {
        $line = trim($line);
        if ('' === $line || str_starts_with($line, '#')) {
            return $line;
        }

        $parts = explode(
            separator: ':',
            string: $line,
            limit: 2,
        );
        $field = self::field($parts[0]);
        $value = trim($parts[1] ?? '');
        if (null === $field || ! array_key_exists(1, $parts)) {
            return null;
        }

        if (1 === preg_match('/[\x00-\x1f\x7f]/', $value)) {
            return null;
        }

        if (in_array($field, ['Allow', 'Disallow'], strict: true) && '' !== $value && ! self::isPath($value)) {
            return null;
        }

        return rtrim("{$field}: {$value}");
    }

    /**
     * @return list<string>
     */
    private static function split(string $text): array
    {
        return explode(
            separator: "\n",
            string: str_replace(
                search: ["\r\n", "\r"],
                replace: "\n",
                subject: $text,
            ),
        );
    }

    /**
     * The rules, one per line, or '' when there are none.
     */
    public function text(): string
    {
        return implode("\n", $this->lines);
    }
}
