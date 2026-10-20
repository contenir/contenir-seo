<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Verification;

use Contenir\Seo\Verification\Verifier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class VerifierTest extends TestCase
{
    #[Test]
    public function describesEachService(): void
    {
        static::assertSame(
            [
                [
                    'google-site-verification',
                    'google.site_verification',
                    'Google Search Console',
                    'Search Console: Settings, Ownership verification, HTML tag.',
                ],
                [
                    'msvalidate.01',
                    'extensions.seo.verify_bing',
                    'Bing Webmaster Tools',
                    'Bing Webmaster Tools: add the site and choose HTML Meta Tag.',
                ],
                [
                    'p:domain_verify',
                    'extensions.seo.verify_pinterest',
                    'Pinterest',
                    'Pinterest: Settings, Claimed accounts, Websites, Claim, then Add HTML tag.',
                ],
                [
                    'facebook-domain-verification',
                    'extensions.seo.verify_meta',
                    'Meta (Facebook and Instagram)',
                    'Meta Business Suite: Brand safety, Domains, then Meta-tag verification.',
                ],
            ],
            array_map(
                static fn(Verifier $verifier): array => [
                    $verifier->metaName(),
                    $verifier->settingKey(),
                    $verifier->label(),
                    $verifier->hint(),
                ],
                Verifier::cases(),
            ),
        );
    }
}
