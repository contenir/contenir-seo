<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Organisation;

use Contenir\Seo\Organisation\OrganisationType;
use Contenir\Seo\Organisation\SocialProfiles;
use Contenir\Seo\Tests\TestAsset\Factory\SettingsFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SocialProfilesTest extends TestCase
{
    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function values(): array
    {
        return [
            'an https URL'          => [
                'social.social_facebook',
                'https://www.facebook.com/example/',
                ['https://www.facebook.com/example/'],
            ],
            'an upper-case scheme'  => ['social.social_facebook', 'HTTPS://Facebook.com/X', ['HTTPS://Facebook.com/X']],
            'an http URL'           => [
                'social.social_youtube',
                'http://youtube.com/@example',
                ['http://youtube.com/@example'],
            ],
            'a padded URL'          => [
                'social.social_linkedin',
                ' https://linkedin.com/company/example ',
                ['https://linkedin.com/company/example'],
            ],
            'an X handle'           => ['social.social_twitter', '@example_co', ['https://x.com/example_co']],
            'an Instagram handle'   => [
                'social.social_instagram',
                'example.co',
                ['https://www.instagram.com/example.co'],
            ],
            'a Pinterest handle'    => ['social.social_pinterest', '@example', ['https://www.pinterest.com/example']],
            'a handle with a space' => ['social.social_twitter', '@ex ample', []],
            'a handle too long'     => ['social.social_twitter', '@abcdefghijklmnopqrstuvwxyz12345', []],
            'a Facebook handle'     => ['social.social_facebook', 'example', []],
            'a URL with no dot'     => ['social.social_facebook', 'https://localhost/example', []],
            'a URL with a space'    => ['social.social_facebook', 'https://facebook.com/ex ample', []],
            'javascript'            => ['social.social_facebook', 'javascript:alert(1)', []],
            'blank'                 => ['social.social_bluesky', '', []],
        ];
    }

    #[Test]
    public function listsProfilesInNetworkOrderWithoutRepeats(): void
    {
        $settings = SettingsFactory::make([
            'social.social_mastodon'  => 'https://mastodon.social/@example',
            'social.social_facebook'  => 'https://www.facebook.com/example',
            'social.social_threads'   => 'https://www.threads.net/@example',
            'social.social_tiktok'    => 'https://www.tiktok.com/@example',
            'social.social_bluesky'   => 'https://bsky.app/profile/example.com',
            'social.social_instagram' => 'https://www.facebook.com/example',
        ]);

        static::assertSame(
            [
                'https://www.facebook.com/example',
                'https://www.tiktok.com/@example',
                'https://www.threads.net/@example',
                'https://bsky.app/profile/example.com',
                'https://mastodon.social/@example',
            ],
            SocialProfiles::fromSettings($settings),
        );
    }

    #[Test]
    public function offersEachOrganisationTypeWithALabel(): void
    {
        static::assertSame(
            ['Organization' => 'Organisation', 'LocalBusiness' => 'Local business'],
            OrganisationType::options(),
        );
    }

    /**
     * @param list<string> $profiles
     */
    #[DataProvider('values')]
    #[Test]
    public function readsUrlsAndTheHandlesSitesStoredBefore(string $key, string $value, array $profiles): void
    {
        static::assertSame($profiles, SocialProfiles::fromSettings(SettingsFactory::make([$key => $value])));
    }
}
