@props([
    'installations',
])

@if($installations->isNotEmpty())
    <flux:card class="glass-card mt-4">
        <flux:heading size="lg" class="mb-4">Letzte Installationen</flux:heading>

        <div class="divide-y divide-zinc-200 dark:divide-white/10">
            @foreach($installations as $installation)
                <div class="flex items-start justify-between gap-4 py-3">
                    <div>
                        <flux:text class="font-medium">{{ $installation->display_name ?: $installation->target_id }}</flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ $installation->target_type->label() }}</flux:text>
                        @if(filled($installation->last_error))
                            <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $installation->last_error }}</flux:text>
                        @endif
                    </div>
                    <flux:badge>{{ $installation->status->label() }}</flux:badge>
                </div>
            @endforeach
        </div>
    </flux:card>
@endif
