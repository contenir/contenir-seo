<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\TestAsset\Factory;

use Contenir\Seo\SettingKeys;

use function array_replace_recursive;
use function array_reverse;
use function explode;

/**
 * Builds a site's settings as nested arrays, the way settings files hold
 * them, from dotted keys.
 */
final class SettingsFactory
{
    public const string SITE_URL = 'https://www.example.com';

    public const string SITE_TITLE = 'Example';

    /**
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function make(array $values): array
    {
        $settings = [];
        foreach ($values as $key => $value) {
            foreach (array_reverse(explode('.', $key)) as $segment) {
                $value = [$segment => $value];
            }

            $settings = array_replace_recursive($settings, $value);
        }

        return $settings;
    }

    /**
     * A site with its URL and name, and the values given.
     *
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function site(array $values = []): array
    {
        return self::make([
            SettingKeys::SITE_URL   => self::SITE_URL,
            SettingKeys::SITE_TITLE => self::SITE_TITLE,
            ...$values,
        ]);
    }
}
