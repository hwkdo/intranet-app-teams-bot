<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBase\Contracts\IntranetAiGatewayInterface;
use Hwkdo\IntranetAppBase\Data\AiChatResult;
use Hwkdo\IntranetAppBase\Data\AiRequestContext;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotIncomingMessage;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsAiChatService;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsAiCommand;

describe('TeamsAiCommand', function (): void {
    it('erkennt die Standard-Auslöser-Phrase unabhängig von der Großschreibung', function (): void {
        expect(TeamsAiCommand::matches('frag die ki'))->toBeTrue()
            ->and(TeamsAiCommand::matches('Frag die KI, was ist Laravel?'))->toBeTrue()
            ->and(TeamsAiCommand::matches('hallo welt'))->toBeFalse();
    });

    it('extrahiert die Frage nach dem Auslöser', function (): void {
        $message = makeTeamsMessage('frag die ki was ist Laravel?');

        expect(TeamsAiCommand::extractPrompt($message))->toBe('was ist Laravel?');
    });

    it('nutzt zitierten Text wenn nur der Auslöser geschrieben wurde', function (): void {
        $message = makeTeamsMessage('frag die ki', quotedText: 'Wie viele Urlaubstage habe ich?');

        expect(TeamsAiCommand::extractPrompt($message))->toBe('Wie viele Urlaubstage habe ich?');
    });

    it('gibt null zurück wenn weder Frage noch Zitat vorhanden sind', function (): void {
        $message = makeTeamsMessage('frag die ki');

        expect(TeamsAiCommand::extractPrompt($message))->toBeNull();
    });
});

describe('TeamsAiChatService', function (): void {
    it('ruft das KI-Gateway mit teams-bot Kontext auf', function (): void {
        $gateway = Mockery::mock(IntranetAiGatewayInterface::class);
        $gateway->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $messages, AiRequestContext $context): bool {
                expect($messages)->toHaveCount(2)
                    ->and($messages[0]['role'])->toBe('system')
                    ->and($messages[1]['role'])->toBe('user')
                    ->and($messages[1]['content'])->toBe('was ist Laravel?')
                    ->and($context->appIdentifier)->toBe('teams-bot')
                    ->and($context->capability)->toBe(AiCapability::Text);

                return true;
            })
            ->andReturn(new AiChatResult(content: 'Laravel ist ein PHP-Framework.'));

        $service = new TeamsAiChatService($gateway);
        $message = makeTeamsMessage('frag die ki was ist Laravel?');

        expect($service->answer($message))->toBe('Laravel ist ein PHP-Framework.');
    });

    it('gibt die konfigurierte Hinweisnachricht zurück wenn keine Frage erkannt wurde', function (): void {
        $gateway = Mockery::mock(IntranetAiGatewayInterface::class);
        $gateway->shouldNotReceive('chat');

        $service = new TeamsAiChatService($gateway);
        $message = makeTeamsMessage('frag die ki');

        expect($service->answer($message))->toContain('Bitte stelle nach');
    });
});

/**
 * @param  array<string, mixed>  $activity
 * @param  array<string, mixed>  $conversationRef
 */
function makeTeamsMessage(
    string $text,
    ?string $quotedText = null,
    array $activity = [],
    array $conversationRef = [],
): TeamsBotIncomingMessage {
    return new TeamsBotIncomingMessage(
        event: 'message',
        text: $text,
        rawText: $text,
        azureUserId: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        upn: null,
        displayName: 'Test User',
        conversationType: 'personal',
        conversationId: 'a:conversation',
        teamId: null,
        channelId: null,
        messageId: 'msg-1',
        isMention: false,
        quotedText: $quotedText,
        quotedSenderName: null,
        quotedSenderAzureId: null,
        quotedMessageId: null,
        activity: $activity,
        conversationRef: $conversationRef,
    );
}
