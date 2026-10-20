<?php

declare(strict_types=1);

namespace Contenir\Seo\Robots;

use function implode;

/**
 * Writes a site's robots.txt. Everything is allowed, the site's extra rules
 * follow, and the sitemap is named on the site's own origin. A hidden copy
 * of the site disallows everything instead, with no rules and no sitemap.
 * /.well-known/ is never blocked.
 *
 * @api
 */
final readonly class RobotsTxt
{
    /**
     * @param string  $origin  The site's scheme, host and port, such as https://www.example.com
     * @param ?string $sitemap The sitemap's absolute URL, or null when the site publishes none
     */
    public function __construct(
        private string $origin,
        private bool $hidden = false,
        private ?string $sitemap = null,
        private RobotsRules $rules = new RobotsRules(),
    ) {}

    public function render(): string
    {
        $blocks = ["# robots.txt for {$this->origin}/"];
        if ($this->hidden) {
            $blocks[] = "# This copy of the site is hidden from search engines.\nUser-agent: *\nDisallow: /";

            return implode("\n\n", $blocks) . "\n";
        }

        $blocks[] = "User-agent: *\nAllow: /";
        if ([] !== $this->rules->lines) {
            $blocks[] = $this->rules->text();
        }

        if (null !== $this->sitemap) {
            $blocks[] = "Sitemap: {$this->sitemap}";
        }

        return implode("\n\n", $blocks) . "\n";
    }
}
