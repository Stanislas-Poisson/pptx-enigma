<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * The command line: reads a presentation and writes its voice-overs.
 */
final class Cli
{
    private const string USAGE = <<<'TXT'
        Usage: pptx-enigma <file.pptx> [options]

        Options:
          --format=json|html|text     how to write the voice-overs (default: json)
          --sign=SIGN                 the sign of the voice-overs (default: the text of the first notes)
          --duplicates=error|first|last
                                      what to do when a speaker uses a reference twice (default: error)
          -h, --help                  show this help

        TXT;

    /**
     * @param list<string>          $arguments the arguments, without the name of the script
     * @param callable(string):void $out       writes on the standard output
     * @param callable(string):void $err       writes on the standard error
     *
     * @return int 0 on success, 1 when the extraction fails, 2 when the command is not valid
     */
    public function run(array $arguments, callable $out, callable $err): int
    {
        try {
            $parsed = CliArguments::parse($arguments);
        }
        catch (InvalidArgumentException $invalidArgumentException) {
            $err($invalidArgumentException->getMessage() . "\n\n" . self::USAGE);

            return 2;
        }

        if ($parsed->help) {
            $out(self::USAGE);

            return 0;
        }

        return $this->extract($parsed, $out, $err);
    }

    /**
     * @param callable(string):void $out
     * @param callable(string):void $err
     */
    private function extract(CliArguments $cliArguments, callable $out, callable $err): int
    {
        $options = new Options(sign: $cliArguments->sign, duplicates: $cliArguments->duplicates);

        try {
            $voiceOvers = (new Extracts($cliArguments->file, $options))->extract();
        }
        catch (InvalidArgumentException|ExtractionException $exception) {
            $err($exception->getMessage() . "\n");

            return 1;
        }

        $out(match ($cliArguments->format) {
            'html'  => $voiceOvers->toHtml(),
            'text'  => $voiceOvers->toText(),
            default => $voiceOvers->toJson() . "\n",
        });

        return 0;
    }
}
