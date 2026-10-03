<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Checks the URL of a link.
 */
final class Links
{
    /**
     * The URL, if its scheme is one of the allowed ones, or null.
     */
    public static function safeUrl(?string $url, Options $options): ?string
    {
        if (null === $url) {
            return null;
        }

        $compact = preg_replace('/[\x00-\x20]+/', '', $url) ?? '';
        $scheme  = 1 === preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $found) ? strtolower($found[1]) : '';

        return in_array($scheme, $options->linkSchemes, true) ? $url : null;
    }
}
