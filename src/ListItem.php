<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The place of a paragraph in a list.
 */
final readonly class ListItem
{
    public function __construct(
        public int $level,
        public ListType $type,
    ) {}
}
