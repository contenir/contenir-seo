<?php

declare(strict_types=1);

namespace Contenir\Seo\Title;

use function trim;

/**
 * Builds a page's <title> from its own title and the site's name, so sites
 * stop hard-coding the " | Site" suffix.
 *
 * @api
 */
final readonly class TitleTemplate
{
    public const string SEPARATOR = '|';

    /**
     * The separators a settings field offers, with labels.
     */
    public const array SEPARATORS = [
        '|'   => '| (vertical bar)',
        '–' => '– (en dash)',
        '-'   => '- (hyphen)',
        '·'  => '· (middle dot)',
        '•' => '• (bullet)',
        '/'   => '/ (slash)',
        ':'   => ': (colon)',
    ];

    /**
     * @param string $homeTitle The home page's whole title, used as it is; blank builds it like any other page's
     */
    public function __construct(
        public string $siteName = '',
        public string $separator = self::SEPARATOR,
        public TitlePosition $position = TitlePosition::After,
        public string $homeTitle = '',
    ) {}

    /**
     * The title for a page called $title. A page without a title, or one
     * that is already the site's name, gets the site's name alone.
     */
    public function format(?string $title): string
    {
        $title    = trim((string) $title);
        $siteName = trim($this->siteName);
        if ('' === $siteName || $title === $siteName) {
            return $title;
        }

        if ('' === $title) {
            return $siteName;
        }

        $separator = trim($this->separator);
        $separator = '' === $separator ? ' ' : " {$separator} ";

        return match ($this->position) {
            TitlePosition::After  => "{$title}{$separator}{$siteName}",
            TitlePosition::Before => "{$siteName}{$separator}{$title}",
            TitlePosition::None   => $title,
        };
    }

    /**
     * The home page's title: the override when there is one, otherwise
     * built like any other page's.
     */
    public function home(?string $title): string
    {
        $homeTitle = trim($this->homeTitle);

        return '' === $homeTitle ? $this->format($title) : $homeTitle;
    }
}
