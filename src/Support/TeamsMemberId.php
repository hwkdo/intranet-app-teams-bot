<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Support;

class TeamsMemberId
{
    public static function resolveAzureUserId(string $memberId): string
    {
        if (preg_match('/29:1([0-9a-f-]{36})/i', $memberId, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^[0-9a-f-]{36}$/i', $memberId) === 1) {
            return strtolower($memberId);
        }

        return $memberId;
    }

    /**
     * Teams-User-MRI für Adaptive-Card-Refresh (userIds).
     * In personalen Chats ist from.id oft `29:1{aadObjectId}`.
     */
    public static function personalMriFromAzureUserId(string $azureUserIdOrMri): ?string
    {
        $value = trim($azureUserIdOrMri);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '29:')) {
            return $value;
        }

        if (preg_match('/^[0-9a-f-]{36}$/i', $value) === 1) {
            return '29:1'.strtolower($value);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    public static function normalizeMessageText(array $activity): string
    {
        return TeamsActivityContentParser::parse($activity)['text'];
    }

    public static function isHiCommand(string $text): bool
    {
        $normalized = strtolower(trim($text));

        return in_array($normalized, ['hi', 'hello', 'hallo'], true);
    }
}
