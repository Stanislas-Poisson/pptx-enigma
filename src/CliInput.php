<?php

declare(strict_types=1);

namespace PPTXenigma;

use BackedEnum;
use InvalidArgumentException;

/**
 * The words of the command line, sorted into the options that have a value, the flags and the files.
 */
final readonly class CliInput
{
    private const array FLAGS = [
        '-h'          => 'help',
        '--help'      => 'help',
        '--no-slides' => 'no-slides',
        '--no-counts' => 'no-counts',
    ];

    private const array OPTIONS = ['format', 'sign', 'duplicates', 'speaker', 'split', 'layout', 'emphasis'];

    /**
     * @param array<string, string|true> $values the value of each option, and true for each flag
     * @param list<string>               $files
     */
    public function __construct(
        public array $values = [],
        public array $files = [],
    ) {}

    /**
     * @param list<string> $arguments the arguments, without the name of the script
     *
     * @throws InvalidArgumentException when an option is not known
     */
    public static function parse(array $arguments): self
    {
        $input = new self();

        foreach ($arguments as $argument) {
            $input = $input->with($argument);
        }

        return $input;
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enum
     * @param T               $backedEnum
     *
     * @return T
     *
     * @throws InvalidArgumentException when the value is not one of the cases
     */
    public function enum(string $enum, string $name, BackedEnum $backedEnum): BackedEnum
    {
        $value = $this->text($name);

        if (null === $value) {
            return $backedEnum;
        }

        $cases = array_map(static fn (BackedEnum $backedEnum): string => (string) $backedEnum->value, $enum::cases());

        return $enum::tryFrom($value) ?? throw new InvalidArgumentException(sprintf(
            'The value of --%s must be one of: %s.',
            $name,
            implode(', ', $cases),
        ));
    }

    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function text(string $name): ?string
    {
        $value = $this->values[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    private function with(string $argument): self
    {
        if (1 === preg_match('/^--([a-z-]+)=(.*)$/s', $argument, $option)) {
            return $this->withOption($option[1], $option[2]);
        }

        if (isset(self::FLAGS[$argument])) {
            return new self([...$this->values, self::FLAGS[$argument] => true], $this->files);
        }

        if (str_starts_with($argument, '-')) {
            throw new InvalidArgumentException(sprintf('Unknown option "%s".', $argument));
        }

        return new self($this->values, [...$this->files, $argument]);
    }

    private function withOption(string $name, string $value): self
    {
        if (! in_array($name, self::OPTIONS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown option "--%s".', $name));
        }

        return new self([...$this->values, $name => $value], $this->files);
    }
}
