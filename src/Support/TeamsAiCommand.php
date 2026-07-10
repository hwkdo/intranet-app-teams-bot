<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Support;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotIncomingMessage;

class TeamsAiCommand
{
    public const DEFAULT_TRIGGER = 'frag die ki';

    public static function matches(string $text, string $trigger = self::DEFAULT_TRIGGER): bool
    {
        $normalizedText = self::normalize($text);
        $normalizedTrigger = self::normalize($trigger);

        if ($normalizedTrigger === '') {
            return false;
        }

        return str_contains($normalizedText, $normalizedTrigger);
    }

    public static function extractPrompt(TeamsBotIncomingMessage $message, string $trigger = self::DEFAULT_TRIGGER): ?string
    {
        if (! self::matches($message->text, $trigger)) {
            return null;
        }

        $remainder = self::stripTrigger($message->text, $trigger);

        if ($remainder !== '') {
            return $remainder;
        }

        if ($message->hasQuotedContent()) {
            return $message->quotedText;
        }

        return null;
    }

    private static function stripTrigger(string $text, string $trigger): string
    {
        if (! self::matches($text, $trigger)) {
            return '';
        }

        $pattern = '/'.preg_quote($trigger, '/').'\s*/iu';

        return trim((string) preg_replace($pattern, '', $text, 1));
    }

    private static function normalize(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
