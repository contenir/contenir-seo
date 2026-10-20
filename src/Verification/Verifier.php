<?php

declare(strict_types=1);

namespace Contenir\Seo\Verification;

use Contenir\Seo\SettingKeys;

/**
 * A service that confirms who owns the site with a meta tag on the home
 * page.
 *
 * @api
 */
enum Verifier: string
{
    case Google = 'google';

    case Bing = 'bing';

    case Pinterest = 'pinterest';

    case Meta = 'meta';

    /**
     * Where the service says to find the token.
     */
    public function hint(): string
    {
        return match ($this) {
            self::Google => 'Search Console: Settings, Ownership verification, HTML tag.',
            self::Bing => 'Bing Webmaster Tools: add the site and choose HTML Meta Tag.',
            self::Pinterest => 'Pinterest: Settings, Claimed accounts, Websites, Claim, then Add HTML tag.',
            self::Meta => 'Meta Business Suite: Brand safety, Domains, then Meta-tag verification.',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google Search Console',
            self::Bing => 'Bing Webmaster Tools',
            self::Pinterest => 'Pinterest',
            self::Meta => 'Meta (Facebook and Instagram)',
        };
    }

    /**
     * The name attribute of the service's meta tag.
     */
    public function metaName(): string
    {
        return match ($this) {
            self::Google => 'google-site-verification',
            self::Bing => 'msvalidate.01',
            self::Pinterest => 'p:domain_verify',
            self::Meta => 'facebook-domain-verification',
        };
    }

    /**
     * The settings key the token is stored under. Google's keeps the key the
     * CMS used.
     */
    public function settingKey(): string
    {
        return match ($this) {
            self::Google => SettingKeys::VERIFY_GOOGLE,
            self::Bing => SettingKeys::VERIFY_BING,
            self::Pinterest => SettingKeys::VERIFY_PINTEREST,
            self::Meta => SettingKeys::VERIFY_META,
        };
    }
}
