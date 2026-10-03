<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The lists that are open while a list is written: it gives the tags to write for each new item.
 *
 * @internal
 */
final class OpenLists
{
    /**
     * @var list<string> the tag of each opened list, from the outermost
     */
    private array $open = [];

    public function closeAll(): string
    {
        return $this->closeDeeperThan(-1);
    }

    /**
     * The closing and opening tags that come before an item of a level and a type.
     */
    public function enter(int $level, string $type): string
    {
        $level = min($level, count($this->open));
        $html  = $this->closeDeeperThan($level);

        if (count($this->open) === $level) {
            return $html . $this->open($type);
        }

        $html .= '</li>';

        return $this->open[$level] === $type ? $html : $html . $this->close() . $this->open($type);
    }

    private function close(): string
    {
        return '</' . array_pop($this->open) . '>';
    }

    private function closeDeeperThan(int $level): string
    {
        $html = '';

        while (count($this->open) > $level + 1) {
            $html .= '</li>' . $this->close();
        }

        return $html;
    }

    private function open(string $type): string
    {
        $this->open[] = $type;

        return '<' . $type . '>';
    }
}
