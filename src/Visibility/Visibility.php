<?php

declare(strict_types=1);

namespace Contenir\Seo\Visibility;

use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\SiteUrl;

use function rtrim;
use function strtolower;
use function trim;

/**
 * Whether search engines see this copy of the site (SEO-3). A copy that is
 * hidden sends X-Robots-Tag: noindex, nofollow on every response, and its
 * robots.txt disallows everything and names no sitemap. The setting is kept
 * in each environment's local settings.
 *
 * @api
 */
enum Visibility: string
{
    /**
     * Hidden unless the request's host is the configured site URL's, so a
     * staging or local copy is hidden without anyone saving anything, and
     * production never is by accident.
     */
    case Automatic = 'auto';

    case Hide = 'hide';

    case Show = 'show';

    /**
     * What a hidden copy sends search engines.
     */
    public const RobotsDirective HIDDEN = RobotsDirective::NoindexNofollow;

    /**
     * Labels for a settings field, by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Automatic->value => 'Automatic: hide every copy that isn\'t at the site URL',
            self::Hide->value      => 'Hide this copy from search engines',
            self::Show->value      => 'Show this copy to search engines',
        ];
    }

    /**
     * The stored value, or Automatic for one that is missing or unknown.
     */
    public static function parse(?string $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Automatic;
    }

    /**
     * Whether a request to $host is hidden. Automatic can't tell production
     * from a copy without a site URL, so it shows every host then.
     */
    public function hides(SiteUrl $site, string $host): bool
    {
        return match ($this) {
            self::Hide => true,
            self::Show => false,
            self::Automatic => $site->isSet() && strtolower(rtrim(trim($host), characters: '.')) !== $site->host,
        };
    }
}
