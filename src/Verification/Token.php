<?php

declare(strict_types=1);

namespace Contenir\Seo\Verification;

use function html_entity_decode;
use function preg_match;
use function sprintf;
use function strlen;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * A verification token as an editor enters it. Services hand out the whole
 * meta tag, so a pasted tag is reduced to its content value.
 *
 * @api
 */
final class Token
{
    /**
     * The longest token accepted; the services' are well under 100 characters.
     */
    public const int MAX_LENGTH = 200;

    /**
     * $value with any meta tag around it removed.
     */
    public static function normalise(string $value): string
    {
        $value = trim($value);
        $match = [];
        if (1 === preg_match('/<meta\b[^>]*\bcontent\s*=\s*(["\'])(.*?)\1/is', $value, $match)) {
            return trim(html_entity_decode($match[2] ?? '', ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8'));
        }

        return $value;
    }

    /**
     * What is wrong with $value as $verifier's token, or null when it is
     * blank or valid.
     */
    public static function problem(Verifier $verifier, string $value): ?string
    {
        $token = self::normalise($value);
        if ('' === $token) {
            return null;
        }

        if (strlen($token) > self::MAX_LENGTH || 1 !== preg_match('/^[A-Za-z0-9_\-.=+\/:]+$/', $token)) {
            return sprintf(
                'This doesn\'t look like a %s verification token. Paste the content value of the meta tag'
                    . ' %s gives you, or the whole tag.',
                $verifier->label(),
                $verifier->label(),
            );
        }

        return null;
    }
}
