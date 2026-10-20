<?php

declare(strict_types=1);

namespace Contenir\Seo\Organisation;

use Contenir\Seo\Internal\SettingsArray;
use Contenir\Seo\SettingKeys;

use function array_values;
use function ltrim;
use function preg_match;
use function trim;

/**
 * The site's profiles on social networks, read from the Social add-on's
 * settings keys as plain data: neither add-on needs the other. A handle
 * (@example), as Twitter, Instagram and Pinterest were stored before the
 * Social add-on, becomes its profile URL.
 *
 * @api
 */
final class SocialProfiles
{
    /**
     * The profile URLs in $settings, in SettingKeys::SOCIAL_PROFILES order,
     * without repeats. Values that are neither an http(s) URL nor a handle
     * of a network that has them are left out.
     *
     * @param array<array-key, mixed> $settings
     *
     * @return list<string>
     */
    public static function fromSettings(array $settings): array
    {
        $values   = new SettingsArray($settings);
        $profiles = [];
        foreach (SettingKeys::SOCIAL_PROFILES as $network => $key) {
            $url = self::url($network, trim($values->text($key)));
            if (null !== $url) {
                $profiles[$url] = $url;
            }
        }

        return array_values($profiles);
    }

    /**
     * Where a handle's profile is, for the networks sites stored handles for.
     */
    private static function handleBase(string $network): ?string
    {
        return match ($network) {
            'x'         => 'https://x.com/',
            'instagram' => 'https://www.instagram.com/',
            'pinterest' => 'https://www.pinterest.com/',
            default     => null,
        };
    }

    private static function url(string $network, string $value): ?string
    {
        if (1 === preg_match('#^https?://[^\s/]+\.[^\s/]+(/\S*)?$#i', $value)) {
            return $value;
        }

        $handle = ltrim($value, characters: '@');
        $base   = self::handleBase($network);
        if (null === $base || 1 !== preg_match('/^[A-Za-z0-9_.]{1,30}$/', $handle)) {
            return null;
        }

        return "{$base}{$handle}";
    }
}
