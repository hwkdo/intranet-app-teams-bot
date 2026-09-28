<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsAdaptiveCardAction;
use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotIncomingMessage;
use Hwkdo\IntranetAppTeamsBot\Enums\TeamsBotConversationStatus;
use Hwkdo\IntranetAppTeamsBot\Events\TeamsBotMessageReceived;
use Hwkdo\IntranetAppTeamsBot\Http\TeamsSdkRestClient;
use Hwkdo\IntranetAppTeamsBot\Models\IntranetAppTeamsBotSettings;
use Hwkdo\IntranetAppTeamsBot\Models\TeamsBotConversation;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsAdaptiveCardInvokeResponse;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsAiCommand;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsMemberId;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamsWebhookHandler
{
    public function __construct(
        private readonly TeamsBotMessagingService $messagingService,
        private readonly TeamsAiChatService $aiChatService,
        private readonly TeamsAdaptiveCardActionDispatcher $adaptiveCardActionDispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{invokeResponse: array{statusCode: int, type: string, value: mixed}}|null
     */
    public function handle(string $event, array $payload): ?array
    {
        $activity = is_array($payload['activity'] ?? null) ? $payload['activity'] : [];
        $conversationRef = is_array($payload['conversationRef'] ?? null) ? $payload['conversationRef'] : [];

        return match ($event) {
            'install.add', 'conversationUpdate.channelMemberAdded' => $this->handleInstallAdd($activity, $conversationRef),
            'install.remove' => $this->handleInstallRemove($conversationRef),
            'message', 'mention' => $this->handleMessage($event, $activity, $conversationRef),
            'adaptive-card.action' => $this->handleAdaptiveCardAction($activity, $conversationRef),
            default => $this->ignoreEvent($event),
        };
    }

    /**
     * @return null
     */
    private function ignoreEvent(string $event): null
    {
        Log::debug('Teams Webhook Event ignoriert', ['event' => $event]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     * @return null
     */
    private function handleInstallAdd(array $activity, array $conversationRef): null
    {
        $azureUserId = $this->resolveAzureUserId($activity, $conversationRef);

        if ($azureUserId === null) {
            return null;
        }

        $conversation = TeamsBotConversation::query()
            ->where('azure_user_id', $azureUserId)
            ->first();

        if ($conversation === null) {
            $conversation = TeamsBotConversation::query()->create([
                'azure_user_id' => $azureUserId,
                'upn' => $this->resolveUpn($activity),
                'display_name' => $this->resolveDisplayName($activity),
                'status' => TeamsBotConversationStatus::Pending,
            ]);
        }

        $this->syncConversationFromRef($conversation, $conversationRef, $activity);

        Log::info('Teams Bot Conversation aktiviert', [
            'azure_user_id' => $azureUserId,
            'conversation_id' => $conversation->fresh()?->conversation_id,
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $conversationRef
     * @return null
     */
    private function handleInstallRemove(array $conversationRef): null
    {
        $azureUserId = $conversationRef['userAadId'] ?? null;

        if (! is_string($azureUserId) || $azureUserId === '') {
            return null;
        }

        TeamsBotConversation::query()
            ->where('azure_user_id', strtolower($azureUserId))
            ->update([
                'status' => TeamsBotConversationStatus::Uninstalled,
                'last_error' => null,
            ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     * @return null
     */
    private function handleMessage(string $event, array $activity, array $conversationRef): null
    {
        $fromId = $activity['from']['id'] ?? null;
        $botAppId = config('intranet-app-teams-bot.bot.app_id');

        if (! is_string($fromId) || $this->isFromBot($fromId, is_string($botAppId) ? $botAppId : null)) {
            Log::debug('Teams Webhook Nachricht ignoriert (vom Bot oder ohne Absender)', [
                'from_id' => $fromId,
            ]);

            return null;
        }

        $azureUserId = $this->resolveAzureUserId($activity, $conversationRef);

        if ($azureUserId !== null) {
            $conversation = TeamsBotConversation::query()
                ->where('azure_user_id', $azureUserId)
                ->first();

            if ($conversation !== null) {
                $this->syncConversationFromRef($conversation, $conversationRef, $activity);
            }
        }

        $message = TeamsBotIncomingMessage::fromWebhook($event, $activity, $conversationRef, $azureUserId);

        // Das separate 'mention'-Event ist ein Duplikat des 'message'-Events (Kanal/Gruppenchat).
        // Verarbeitung erfolgt am message-Event, wenn der Bot erwähnt wurde.
        if ($event === 'mention') {
            return null;
        }

        // In Kanälen und Gruppenchats nur auf @mentions reagieren.
        if (! $message->isDirectMessage() && ! $message->isMention) {
            return null;
        }

        Log::info('Teams Bot Nachricht vom Benutzer empfangen', [
            'from_id' => $fromId,
            'event' => $event,
            'conversation_type' => $message->conversationType,
            'text' => $message->text,
            'quoted_text' => $message->quotedText,
            'quoted_sender_name' => $message->quotedSenderName,
            'quoted_sender_azure_id' => $message->quotedSenderAzureId,
            'quoted_message_id' => $message->quotedMessageId,
        ]);

        if (config('intranet-app-teams-bot.sdk_rest.log_webhook_payload', false)
            || $message->hasQuotedContent()
            || $this->activityLooksLikeQuote($activity)) {
            Log::info('Teams Bot Activity Payload (Debug)', [
                'activity_id' => $message->messageId,
                'text' => $activity['text'] ?? null,
                'reply_to_id' => $activity['replyToId'] ?? null,
                'attachment_types' => collect(is_array($activity['attachments'] ?? null) ? $activity['attachments'] : [])
                    ->map(fn (mixed $attachment): ?string => is_array($attachment) ? ($attachment['contentType'] ?? null) : null)
                    ->filter()
                    ->values()
                    ->all(),
                'entity_types' => collect(is_array($activity['entities'] ?? null) ? $activity['entities'] : [])
                    ->map(fn (mixed $entity): ?string => is_array($entity) ? ($entity['type'] ?? null) : null)
                    ->filter()
                    ->values()
                    ->all(),
                'attachments' => $activity['attachments'] ?? null,
                'entities' => $activity['entities'] ?? null,
            ]);
        }

        if ($this->handleAskAiCommand($message, $activity, $conversationRef)) {
            return null;
        }

        if ($this->dispatchToProcessors($message, $activity, $conversationRef)) {
            return null;
        }

        $reply = $this->resolveFallbackReply($message);

        if (is_string($reply) && $reply !== '') {
            $this->messagingService->replyToWebhookMessage($activity, $conversationRef, $reply);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     * @return array{invokeResponse: array{statusCode: int, type: string, value: mixed}}
     */
    private function handleAdaptiveCardAction(array $activity, array $conversationRef): array
    {
        $azureUserId = $this->resolveAzureUserId($activity, $conversationRef);

        if ($azureUserId === null) {
            return [
                'invokeResponse' => TeamsAdaptiveCardInvokeResponse::message(
                    'Benutzer konnte nicht ermittelt werden.',
                ),
            ];
        }

        $conversation = TeamsBotConversation::query()
            ->where('azure_user_id', $azureUserId)
            ->first();

        if ($conversation !== null) {
            $this->syncConversationFromRef($conversation, $conversationRef, $activity);
        }

        $user = $this->resolveUserByAzureId($azureUserId);

        if ($user === null) {
            return [
                'invokeResponse' => TeamsAdaptiveCardInvokeResponse::message(
                    'Kein Intranet-Benutzer für dieses Teams-Konto gefunden.',
                ),
            ];
        }

        [$verb, $data] = $this->extractAdaptiveCardAction($activity);

        if ($verb === '') {
            return [
                'invokeResponse' => TeamsAdaptiveCardInvokeResponse::message(
                    'Ungültige Card-Aktion.',
                ),
            ];
        }

        $action = new TeamsAdaptiveCardAction(
            azureUserId: $azureUserId,
            verb: $verb,
            data: $data,
            activity: $activity,
            conversationRef: $conversationRef,
        );

        return [
            'invokeResponse' => $this->adaptiveCardActionDispatcher->dispatch($user, $action),
        ];
    }

    /**
     * @param  array<string, mixed>  $activity
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function extractAdaptiveCardAction(array $activity): array
    {
        $value = is_array($activity['value'] ?? null) ? $activity['value'] : [];
        $action = is_array($value['action'] ?? null) ? $value['action'] : [];
        $actionData = is_array($action['data'] ?? null) ? $action['data'] : [];

        $verb = $action['verb'] ?? $value['verb'] ?? $actionData['verb'] ?? '';
        $verb = is_string($verb) ? trim($verb) : '';

        $data = array_merge($value, $actionData);
        unset($data['action'], $data['verb']);

        return [$verb, $data];
    }

    /**
     * Resolve intranet Eloquent user via DB only (not the LDAP auth provider model).
     */
    private function resolveUserByAzureId(string $azureUserId): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $userClass = config('auth.providers.users.database.model')
            ?? config('auth.providers.users.model')
            ?? \App\Models\User::class;

        // LDAP provider sets users.model to the AD class; Eloquent users live under database.model.
        if (! is_string($userClass)
            || ! class_exists($userClass)
            || ! is_subclass_of($userClass, \Illuminate\Database\Eloquent\Model::class)
        ) {
            $userClass = \App\Models\User::class;
        }

        $normalized = strtolower(trim($azureUserId));

        if ($normalized === '') {
            return null;
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $userClass;
        $table = $model->getTable();
        $connection = $model->getConnectionName();

        $userId = \Illuminate\Support\Facades\DB::connection($connection)
            ->table($table)
            ->whereRaw('LOWER(socialite_id) = ?', [$normalized])
            ->value($model->getKeyName());

        if ($userId === null) {
            return null;
        }

        $user = $userClass::query()->find($userId);

        return $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     */
    private function handleAskAiCommand(
        TeamsBotIncomingMessage $message,
        array $activity,
        array $conversationRef,
    ): bool {
        $settings = IntranetAppTeamsBotSettings::resolvedAppSettings();

        if (! $settings->aiChatEnabled) {
            return false;
        }

        $trigger = trim($settings->aiChatTriggerPhrase) !== ''
            ? $settings->aiChatTriggerPhrase
            : TeamsAiCommand::DEFAULT_TRIGGER;

        if (! TeamsAiCommand::matches($message->text, $trigger)) {
            return false;
        }

        $reply = $this->aiChatService->answer($message);

        if ($reply === '') {
            return false;
        }

        $this->messagingService->replyToWebhookMessage($activity, $conversationRef, $reply);

        return true;
    }

    /**
     * Verteilt die Nachricht an registrierte Listener (z. B. Ticket-Erstellung). Gibt einen
     * Bestätigungstext zurück, wenn ein Listener die Nachricht übernommen hat.
     *
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     */
    private function dispatchToProcessors(
        TeamsBotIncomingMessage $message,
        array $activity,
        array $conversationRef,
    ): bool {
        $responses = TeamsBotMessageReceived::dispatch($message);

        $acknowledgement = collect(is_array($responses) ? $responses : [])
            ->first(fn ($response): bool => is_string($response) && trim($response) !== '');

        if (! is_string($acknowledgement)) {
            return false;
        }

        $this->messagingService->replyToWebhookMessage($activity, $conversationRef, $acknowledgement);

        return true;
    }

    private function resolveFallbackReply(TeamsBotIncomingMessage $message): ?string
    {
        if (! $message->isDirectMessage()) {
            $help = config('intranet-app-teams-bot.bot.mention_help_message');

            return is_string($help) ? $help : null;
        }

        $reply = TeamsMemberId::isHiCommand($message->text)
            ? config('intranet-app-teams-bot.bot.hi_reply_message')
            : config('intranet-app-teams-bot.bot.auto_reply_message');

        return is_string($reply) ? $reply : null;
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     */
    private function syncConversationFromRef(
        TeamsBotConversation $conversation,
        array $conversationRef,
        array $activity,
    ): void {
        $conversationId = $conversationRef['conversationId']
            ?? (is_array($activity['conversation'] ?? null) ? ($activity['conversation']['id'] ?? null) : null);
        $serviceUrl = $conversationRef['serviceUrl'] ?? $activity['serviceUrl'] ?? null;
        $tenantId = $conversationRef['tenantId']
            ?? (is_array($activity['conversation'] ?? null) ? ($activity['conversation']['tenantId'] ?? null) : null)
            ?? (is_array($activity['channelData']['tenant'] ?? null) ? ($activity['channelData']['tenant']['id'] ?? null) : null);

        if (! is_string($conversationId) || $conversationId === '' || ! is_string($serviceUrl) || $serviceUrl === '') {
            return;
        }

        $conversation->markActive(
            $conversationId,
            $serviceUrl,
            is_string($tenantId) ? $tenantId : null,
        );

        $this->registerConversationInSdk($conversation, $conversationRef, $activity);
    }

    /**
     * @param  array<string, mixed>  $conversationRef
     * @param  array<string, mixed>  $activity
     */
    private function registerConversationInSdk(
        TeamsBotConversation $conversation,
        array $conversationRef,
        array $activity,
    ): void {
        if (! filled($conversation->conversation_id)) {
            return;
        }

        try {
            app(TeamsSdkRestClient::class)->registerConversation([
                'userAadId' => $conversation->azure_user_id,
                'conversationId' => $conversation->conversation_id,
                'serviceUrl' => $conversation->service_url
                    ?? $conversationRef['serviceUrl']
                    ?? $activity['serviceUrl']
                    ?? null,
                'tenantId' => $conversation->tenant_id
                    ?? $conversationRef['tenantId']
                    ?? null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Teams Webhook konnte Conversation nicht in teams-sdk-rest registrieren', [
                'azure_user_id' => $conversation->azure_user_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     */
    private function resolveAzureUserId(array $activity, array $conversationRef): ?string
    {
        $userAadId = $conversationRef['userAadId'] ?? $activity['from']['aadObjectId'] ?? null;

        if (is_string($userAadId) && $userAadId !== '') {
            return strtolower($userAadId);
        }

        $fromId = $activity['from']['id'] ?? null;

        if (! is_string($fromId) || $fromId === '') {
            return null;
        }

        return TeamsMemberId::resolveAzureUserId($fromId);
    }

    private function isFromBot(string $fromId, ?string $botAppId): bool
    {
        if ($botAppId === null || $botAppId === '') {
            return false;
        }

        if ($fromId === $botAppId) {
            return true;
        }

        return str_contains($fromId, $botAppId);
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private function resolveUpn(array $activity): ?string
    {
        $from = is_array($activity['from'] ?? null) ? $activity['from'] : [];
        $upn = $from['userPrincipalName'] ?? $from['email'] ?? null;

        return is_string($upn) && $upn !== '' ? $upn : null;
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private function resolveDisplayName(array $activity): ?string
    {
        $from = is_array($activity['from'] ?? null) ? $activity['from'] : [];
        $name = $from['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private function activityLooksLikeQuote(array $activity): bool
    {
        $text = $activity['text'] ?? '';

        if (is_string($text) && str_contains($text, '<blockquote')) {
            return true;
        }

        $attachments = $activity['attachments'] ?? [];

        if (! is_array($attachments)) {
            return false;
        }

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $contentType = $attachment['contentType'] ?? null;

            if ($contentType === 'messageReference') {
                return true;
            }

            if ($contentType === 'text/html') {
                $content = $attachment['content'] ?? '';

                if (is_string($content) && str_contains($content, '<blockquote')) {
                    return true;
                }
            }
        }

        $entities = $activity['entities'] ?? [];

        if (! is_array($entities)) {
            return false;
        }

        foreach ($entities as $entity) {
            if (is_array($entity) && ($entity['type'] ?? null) === 'quotedReply') {
                return true;
            }
        }

        return filled($activity['replyToId'] ?? null);
    }
}
