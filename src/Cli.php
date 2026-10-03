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
        $file       = null;
        $format     = 'json';
        $sign       = null;
        $duplicates = Duplicates::Error;

        foreach ($arguments as $argument) {
            if ('-h' === $argument || '--help' === $argument) {
                $out(self::USAGE);

                return 0;
            }

            if (str_starts_with($argument, '--format=')) {
                $format = substr($argument, 9);
            }
            elseif (str_starts_with($argument, '--sign=')) {
                $sign = substr($argument, 7);
            }
            elseif (str_starts_with($argument, '--duplicates=')) {
                $duplicates = Duplicates::tryFrom(substr($argument, 13));

                if (null === $duplicates) {
                    return $this->usage($err, 'The value of --duplicates must be error, first or last.');
                }
            }
            elseif (str_starts_with($argument, '-')) {
                return $this->usage($err, sprintf('Unknown option "%s".', $argument));
            }
            elseif (null === $file) {
                $file = $argument;
            }
            else {
                return $this->usage($err, 'Only one file can be read.');
            }
        }

        if (null === $file) {
            return $this->usage($err, 'The file to read is missing.');
        }

        if (! in_array($format, ['json', 'html', 'text'], true)) {
            return $this->usage($err, 'The value of --format must be json, html or text.');
        }

        try {
            $voiceOvers = (new Extracts($file, new Options(sign: $sign, duplicates: $duplicates)))->extract();
        }
        catch (InvalidArgumentException|ExtractionException $exception) {
            $err($exception->getMessage() . "\n");

            return 1;
        }

        $out(match ($format) {
            'html'  => $voiceOvers->toHtml(),
            'text'  => $voiceOvers->toText(),
            default => $voiceOvers->toJson() . "\n",
        });

        return 0;
    }

    /**
     * @param callable(string):void $err
     */
    private function usage(callable $err, string $message): int
    {
        $err($message . "\n\n" . self::USAGE);

        return 2;
    }
}
