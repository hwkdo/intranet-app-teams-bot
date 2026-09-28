<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Support;

class TeamsAdaptiveCardInvokeResponse
{
    /**
     * @param  array<string, mixed>  $card
     * @return array{statusCode: int, type: string, value: array<string, mixed>}
     */
    public static function card(array $card): array
    {
        return [
            'statusCode' => 200,
            'type' => 'application/vnd.microsoft.card.adaptive',
            'value' => $card,
        ];
    }

    /**
     * @return array{statusCode: int, type: string, value: string}
     */
    public static function message(string $message): array
    {
        return [
            'statusCode' => 200,
            'type' => 'application/vnd.microsoft.activity.message',
            'value' => $message,
        ];
    }

    /**
     * @return array{statusCode: int, type: string, value: array{code: string, message: string}}
     */
    public static function error(string $message, int $statusCode = 400): array
    {
        return [
            'statusCode' => $statusCode,
            'type' => 'application/vnd.microsoft.error',
            'value' => [
                'code' => 'BadRequest',
                'message' => $message,
            ],
        ];
    }
}
