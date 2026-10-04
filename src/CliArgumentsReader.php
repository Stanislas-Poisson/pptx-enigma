<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * Reads the arguments of the command line and checks that they go together.
 */
final readonly class CliArgumentsReader
{
    private const array FORMATS = ['json', 'html', 'text', 'pdf'];

    /**
     * @param list<string> $arguments the arguments, without the name of the script
     *
     * @throws InvalidArgumentException when an argument is not valid
     */
    public function read(array $arguments): CliArguments
    {
        $cliInput   = CliInput::parse($arguments);
        $speaker    = $cliInput->text('speaker');
        $split      = $cliInput->text('split');
        $script     = null !== $speaker || null !== $split;

        $this->checkFiles($cliInput);
        (new ScriptUse())->check($cliInput, $speaker, $split);

        return new CliArguments(
            $cliInput->files[0] ?? '',
            $this->format($cliInput, $script),
            $cliInput->text('sign'),
            $cliInput->enum(Duplicates::class, 'duplicates', Duplicates::Error),
            $cliInput->has('help'),
            $speaker,
            $split,
            new ScriptOptions(
                $cliInput->enum(Layout::class, 'layout', Layout::ReferenceAbove),
                $cliInput->enum(Emphasis::class, 'emphasis', Emphasis::Marks),
                ! $cliInput->has('no-slides'),
                ! $cliInput->has('no-counts'),
            ),
            $cliInput->text('end-sign'),
        );
    }

    private function checkFiles(CliInput $cliInput): void
    {
        if ([] === $cliInput->files && ! $cliInput->has('help')) {
            throw new InvalidArgumentException('The file to read is missing.');
        }

        if (count($cliInput->files) > 1) {
            throw new InvalidArgumentException('Only one file can be read.');
        }
    }

    private function format(CliInput $cliInput, bool $script): string
    {
        $format = $cliInput->text('format') ?? ($script ? 'text' : 'json');

        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException('The value of --format must be json, html, text or pdf.');
        }

        if ($script && 'json' === $format) {
            throw new InvalidArgumentException('A script is written as text, html or pdf.');
        }

        return $format;
    }
}
