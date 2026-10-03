<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * The arguments of the command line, read and checked.
 */
final readonly class CliArguments
{
    private const array FORMATS = ['json', 'html', 'text'];

    public function __construct(
        public string $file = '',
        public string $format = 'json',
        public ?string $sign = null,
        public Duplicates $duplicates = Duplicates::Error,
        public bool $help = false,
    ) {}

    /**
     * @param list<string> $arguments the arguments, without the name of the script
     *
     * @throws InvalidArgumentException when an argument is not valid
     */
    public static function parse(array $arguments): self
    {
        $parsed = new self();

        foreach ($arguments as $argument) {
            $parsed = $parsed->read($argument);
        }

        return $parsed->checked();
    }

    private function checked(): self
    {
        if ('' === $this->file && ! $this->help) {
            throw new InvalidArgumentException('The file to read is missing.');
        }

        return $this;
    }

    private function read(string $argument): self
    {
        if ('-h' === $argument || '--help' === $argument) {
            return $this->withHelp();
        }

        if (1 === preg_match('/^--([a-z]+)=(.*)$/s', $argument, $option)) {
            return $this->readOption($option[1], $option[2]);
        }

        if (str_starts_with($argument, '-')) {
            throw new InvalidArgumentException(sprintf('Unknown option "%s".', $argument));
        }

        return $this->readFile($argument);
    }

    private function readDuplicates(string $duplicates): self
    {
        $handling = Duplicates::tryFrom($duplicates);

        return $handling instanceof Duplicates
            ? $this->withDuplicates($handling)
            : throw new InvalidArgumentException('The value of --duplicates must be error, first or last.');
    }

    private function readFile(string $file): self
    {
        if ('' !== $this->file) {
            throw new InvalidArgumentException('Only one file can be read.');
        }

        return $this->withFile($file);
    }

    private function readFormat(string $format): self
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException('The value of --format must be json, html or text.');
        }

        return $this->withFormat($format);
    }

    private function readOption(string $name, string $value): self
    {
        return match ($name) {
            'format'     => $this->readFormat($value),
            'sign'       => $this->withSign($value),
            'duplicates' => $this->readDuplicates($value),
            default      => throw new InvalidArgumentException(sprintf('Unknown option "--%s".', $name)),
        };
    }

    private function withDuplicates(Duplicates $duplicates): self
    {
        return new self($this->file, $this->format, $this->sign, $duplicates, $this->help);
    }

    private function withFile(string $file): self
    {
        return new self($file, $this->format, $this->sign, $this->duplicates, $this->help);
    }

    private function withFormat(string $format): self
    {
        return new self($this->file, $format, $this->sign, $this->duplicates, $this->help);
    }

    private function withHelp(): self
    {
        return new self($this->file, $this->format, $this->sign, $this->duplicates, true);
    }

    private function withSign(string $sign): self
    {
        return new self($this->file, $this->format, $sign, $this->duplicates, $this->help);
    }
}
