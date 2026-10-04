<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * The arguments of the command line, read and checked.
 */
final readonly class CliArguments
{
    public function __construct(
        public string $file = '',
        public string $format = 'json',
        public ?string $sign = null,
        public Duplicates $duplicates = Duplicates::Error,
        public bool $help = false,
        public ?string $speaker = null,
        public ?string $split = null,
        public ScriptOptions $scriptOptions = new ScriptOptions(),
        public ?string $endSign = null,
    ) {}

    /**
     * @param list<string> $arguments the arguments, without the name of the script
     *
     * @throws InvalidArgumentException when an argument is not valid
     */
    public static function parse(array $arguments): self
    {
        return (new CliArgumentsReader())->read($arguments);
    }
}
