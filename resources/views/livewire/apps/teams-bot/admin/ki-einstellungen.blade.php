<?php

use Flux\Flux;
use Hwkdo\IntranetAppBase\Contracts\AiConfigResolverInterface;
use Hwkdo\IntranetAppBase\Contracts\IntranetBaseAiConfigSourceInterface;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppBase\Enums\AiProvider;
use Hwkdo\IntranetAppTeamsBot\Data\AppSettings;
use Hwkdo\IntranetAppTeamsBot\Models\IntranetAppTeamsBotSettings;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $aiTextProviderOverride = '';

    public string $aiTextModelOverride = '';

    public string $aiImageProviderOverride = '';

    public string $aiImageModelOverride = '';

    public bool $aiChatEnabled = true;

    public string $aiChatTriggerPhrase = 'frag die ki';

    public string $aiChatSystemPrompt = '';

    public string $aiChatMissingPromptMessage = '';

    public string $aiChatErrorMessage = '';

    public function mount(): void
    {
        $settings = IntranetAppTeamsBotSettings::resolvedAppSettings();

        $this->aiTextProviderOverride = $settings->aiTextProviderOverride?->value ?? '';
        $this->aiTextModelOverride = $settings->textModelOverride() ?? '';
        $this->aiImageProviderOverride = $settings->aiImageProviderOverride?->value ?? '';
        $this->aiImageModelOverride = $settings->imageModelOverride() ?? '';
        $this->aiChatEnabled = $settings->aiChatEnabled;
        $this->aiChatTriggerPhrase = $settings->aiChatTriggerPhrase;
        $this->aiChatSystemPrompt = $settings->aiChatSystemPrompt;
        $this->aiChatMissingPromptMessage = $settings->aiChatMissingPromptMessage;
        $this->aiChatErrorMessage = $settings->aiChatErrorMessage;
    }

    public function save(): void
    {
        $this->validate([
            'aiTextProviderOverride' => ['nullable', 'string', Rule::enum(AiProvider::class)],
            'aiTextModelOverride' => 'nullable|string|max:100',
            'aiImageProviderOverride' => ['nullable', 'string', Rule::enum(AiProvider::class)],
            'aiImageModelOverride' => 'nullable|string|max:100',
            'aiChatEnabled' => 'boolean',
            'aiChatTriggerPhrase' => 'required|string|max:100',
            'aiChatSystemPrompt' => 'nullable|string|max:5000',
            'aiChatMissingPromptMessage' => 'required|string|max:500',
            'aiChatErrorMessage' => 'required|string|max:500',
        ]);

        $current = IntranetAppTeamsBotSettings::resolvedAppSettings();

        $settings = AppSettings::from(array_merge($current->toArray(), [
            'aiTextProviderOverride' => $this->parseProviderOverride($this->aiTextProviderOverride),
            'aiTextModelOverride' => $this->blankToNull($this->aiTextModelOverride),
            'aiImageProviderOverride' => $this->parseProviderOverride($this->aiImageProviderOverride),
            'aiImageModelOverride' => $this->blankToNull($this->aiImageModelOverride),
            'aiChatEnabled' => $this->aiChatEnabled,
            'aiChatTriggerPhrase' => trim($this->aiChatTriggerPhrase),
            'aiChatSystemPrompt' => trim($this->aiChatSystemPrompt),
            'aiChatMissingPromptMessage' => trim($this->aiChatMissingPromptMessage),
            'aiChatErrorMessage' => trim($this->aiChatErrorMessage),
        ]));

        IntranetAppTeamsBotSettings::persistAppSettings($settings);

        Flux::toast(
            heading: 'Gespeichert',
            text: 'KI-Einstellungen wurden gespeichert.',
            variant: 'success',
        );
    }

    #[Computed]
    public function baseAiTextSummary(): string
    {
        $base = app(IntranetBaseAiConfigSourceInterface::class);
        $model = $base->textModel() ?? 'Provider-Standard';

        return $base->textProvider()->label().' / '.$model;
    }

    #[Computed]
    public function effectiveAiTextSummary(): string
    {
        $resolved = app(AiConfigResolverInterface::class)->resolve('teams-bot', AiCapability::Text);

        return $resolved->provider->label().' / '.($resolved->model ?? 'Provider-Standard');
    }

    private function parseProviderOverride(string $value): ?AiProvider
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return AiProvider::from($trimmed);
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
};
?>

<flux:card class="glass-card">
    <flux:heading size="lg" class="mb-2">KI-Einstellungen</flux:heading>
    <flux:text class="mb-6 text-sm text-zinc-500">
        Gateway-Overrides und „frag die ki" im Teams Bot. Leere Override-Felder nutzen die globalen Einstellungen unter Manager → Base Settings.
    </flux:text>

    <div class="space-y-8">
        <div>
            <flux:heading size="sm" class="mb-3">Gateway (Text)</flux:heading>

            <flux:callout class="mb-4" icon="information-circle">
                <flux:callout.heading>Globale KI-Standards</flux:callout.heading>
                <flux:callout.text>
                    Text: <strong>{{ $this->baseAiTextSummary }}</strong>
                    — änderbar unter Manager → Base Settings.
                </flux:callout.text>
            </flux:callout>

            <x-intranet-app-base::admin-ai-settings
                ai-text-provider-override="aiTextProviderOverride"
                ai-text-model-override="aiTextModelOverride"
                ai-image-provider-override="aiImageProviderOverride"
                ai-image-model-override="aiImageModelOverride"
            />

            <flux:text class="mt-4 text-sm text-zinc-500">
                Aktuell wirksam für „frag die ki":
                <strong>{{ $this->effectiveAiTextSummary }}</strong>.
            </flux:text>
        </div>

        <flux:separator />

        <div>
            <flux:heading size="sm" class="mb-3">„frag die ki"</flux:heading>
            <flux:text class="mb-4 text-sm text-zinc-500">
                Nutzer können im Chat „frag die ki" schreiben (ggf. mit @-Erwähnung in Kanälen), optional gefolgt von der Frage oder mit zitierter Nachricht.
            </flux:text>

            <div class="grid gap-4">
                <flux:field>
                    <flux:label>KI-Antworten aktivieren</flux:label>
                    <flux:switch wire:model="aiChatEnabled" />
                </flux:field>

                <flux:input
                    wire:model="aiChatTriggerPhrase"
                    label="Auslöser-Phrase"
                    description="Groß-/Kleinschreibung spielt keine Rolle."
                />

                <flux:field>
                    <flux:label>System-Prompt</flux:label>
                    <flux:textarea wire:model="aiChatSystemPrompt" rows="5" />
                    <flux:description>Steuert Ton und Kontext der KI-Antworten im Teams Bot.</flux:description>
                </flux:field>

                <flux:input
                    wire:model="aiChatMissingPromptMessage"
                    label="Hinweis ohne erkannte Frage"
                />

                <flux:input
                    wire:model="aiChatErrorMessage"
                    label="Fehlermeldung bei KI-Ausfall"
                />
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <flux:button wire:click="save" variant="primary">
            KI-Einstellungen speichern
        </flux:button>
    </div>
</flux:card>
