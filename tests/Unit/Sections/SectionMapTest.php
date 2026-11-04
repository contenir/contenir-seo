<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\SectionMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function libxml_get_errors;
use function libxml_use_internal_errors;

#[Group('unit')]
final class SectionMapTest extends TestCase
{
    private const string XML = <<<'XML'
        <admin>
            <library>
                <category>
                    <projects>
                        <section>
                            <article>
                                <extensions>
                                    <audit/>
                                    <seo type="project" schema="CreativeWork"/>
                                </extensions>
                            </article>
                            <other>
                                <extensions><seo type="project"/></extensions>
                            </other>
                            <untyped>
                                <extensions><seo/></extensions>
                            </untyped>
                        </section>
                    </projects>
                    <news>
                        <section>
                            <article>
                                <extensions><seo type="news"/></extensions>
                            </article>
                        </section>
                    </news>
                </category>
            </library>
        </admin>
        XML;

    /**
     * @return array<string, array{bool}>
     */
    public static function errorSettingProvider(): array
    {
        return [
            'errors kept internally' => [true],
            'errors reported'        => [false],
        ];
    }

    #[Test]
    public function aFileThatCannotBeReadMapsNothing(): void
    {
        $broken = SectionMap::fromAdminXml('<admin>');
        $empty  = SectionMap::fromAdminXml('');

        static::assertSame(
            [[], ['The admin.xml could not be read'], [], ['The admin.xml could not be read']],
            [$broken->blocks, $broken->problems, $empty->blocks, $empty->problems],
        );
    }

    #[Test]
    public function mapsEachBlockByTheTypeItNames(): void
    {
        $map = SectionMap::fromAdminXml(self::XML);

        static::assertSame(
            [['project', 'news'], 'CreativeWork', null],
            [array_keys($map->blocks), $map->for('project')?->schema, $map->for('essay')],
        );
    }

    #[Test]
    public function namesTheBlocksItLeftOut(): void
    {
        static::assertSame(
            [
                'The <seo> block on library.projects.other maps project again',
                'The <seo> block on library.projects.untyped names no type',
            ],
            SectionMap::fromAdminXml(self::XML)->problems,
        );
    }

    #[Test]
    #[DataProvider('errorSettingProvider')]
    public function readingLeavesNoXmlErrorsBehindAndRestoresTheErrorSetting(bool $setting): void
    {
        $previous = libxml_use_internal_errors($setting);
        SectionMap::fromAdminXml('<admin><broken></admin>');

        static::assertSame([[], $setting], [libxml_get_errors(), libxml_use_internal_errors($previous)]);
    }
}
