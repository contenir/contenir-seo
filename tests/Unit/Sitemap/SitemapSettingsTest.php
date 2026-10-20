<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sitemap;

use Contenir\Seo\Sitemap\SitemapSettings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SitemapSettingsTest extends TestCase
{
    #[Test]
    public function isEnabledAndLeavesNothingOutByDefault(): void
    {
        $settings = new SitemapSettings();

        static::assertSame([true, [], false], [$settings->enabled, $settings->excluded, $settings->excludes('page')]);
    }

    #[Test]
    public function leavesOutWorkflowsWhateverTheirCase(): void
    {
        $settings = new SitemapSettings(excluded: ['redirect', 'faq']);

        static::assertSame(
            [true, true, false, false],
            [
                $settings->excludes(' Redirect '),
                $settings->excludes('faq'),
                $settings->excludes('page'),
                $settings->excludes(null),
            ],
        );
    }

    #[Test]
    public function readsWorkflowNamesAsEditorsTypeThem(): void
    {
        static::assertSame(
            ['redirect', 'faq', 'news', 'gallery'],
            SitemapSettings::workflows(" Redirect, FAQ\r\nnews\tredirect,,gallery "),
        );
    }
}
