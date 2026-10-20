<?php

declare(strict_types=1);

namespace Contenir\Seo\Title;

/**
 * Where the site's name goes in a page's title.
 *
 * @api
 */
enum TitlePosition: string
{
    /**
     * "About us | Example", the usual choice.
     */
    case After = 'after';

    /**
     * "Example | About us".
     */
    case Before = 'before';

    /**
     * "About us": the page's title alone.
     */
    case None = 'none';

    /**
     * Labels for a settings field, by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::After->value  => 'After the page title',
            self::Before->value => 'Before the page title',
            self::None->value   => 'Leave it out',
        ];
    }
}
