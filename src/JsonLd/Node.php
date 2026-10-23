<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use function array_filter;

/**
 * Leaves out a node's properties that have no value.
 *
 * @internal
 */
final class Node
{
    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    public static function of(array $properties): array
    {
        return array_filter(
            $properties,
            static fn(mixed $value): bool => null !== $value && [] !== $value && '' !== $value,
        );
    }
}
