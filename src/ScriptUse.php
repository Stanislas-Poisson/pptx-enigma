<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * Checks that the options of a script, --speaker, --split and the formats go together.
 */
final readonly class ScriptUse
{
    private const array SCRIPT_OPTIONS = ['layout', 'emphasis', 'no-slides', 'no-counts'];

    /**
     * @throws InvalidArgumentException when the options do not go together
     */
    public function check(CliInput $cliInput, ?string $speaker, ?string $split): void
    {
        $this->checkMode($speaker, $split);
        $this->checkOptions($cliInput, null !== $speaker || null !== $split);
        $this->checkPdf($cliInput, $split);
    }

    private function checkMode(?string $speaker, ?string $split): void
    {
        if (null !== $speaker && null !== $split) {
            throw new InvalidArgumentException('Use --speaker or --split, not both.');
        }

        if ('' === $split) {
            throw new InvalidArgumentException('The value of --split is missing: it is a directory.');
        }
    }

    private function checkOptions(CliInput $cliInput, bool $script): void
    {
        if ($script) {
            return;
        }

        foreach (self::SCRIPT_OPTIONS as $option) {
            if ($cliInput->has($option)) {
                throw new InvalidArgumentException(sprintf('--%s needs --speaker or --split.', $option));
            }
        }
    }

    private function checkPdf(CliInput $cliInput, ?string $split): void
    {
        if (null === $split && 'pdf' === $cliInput->text('format')) {
            throw new InvalidArgumentException('A PDF is written in a directory: use --split=DIR.');
        }
    }
}
