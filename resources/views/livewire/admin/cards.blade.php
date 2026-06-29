<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Cards</flux:heading>
        <flux:button wire:click="openAdd" variant="primary" icon="plus">Add manual card</flux:button>
    </div>

    <flux:radio.group wire:model.live="filter" variant="segmented">
        <flux:radio value="active" label="Active" />
        <flux:radio value="retired" label="Retired" />
        <flux:radio value="all" label="All" />
    </flux:radio.group>

    <div class="space-y-2">
        @forelse ($cards as $card)
            <flux:card wire:key="card-{{ $card->id }}" class="py-3">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium">{{ $card->spanish }}</div>
                        <div class="text-zinc-500 text-sm">{{ $card->english }}</div>
                        <div class="mt-1 text-xs text-zinc-400">
                            @php $mm = $card->must_match ?? []; @endphp
                            <flux:badge size="sm" :color="$card->status->value === 'active' ? 'green' : 'zinc'">{{ $card->status->value }}</flux:badge>
                            <flux:badge size="sm" color="zinc">{{ $card->source->value }}</flux:badge>
                            tense: {{ $mm['tense'] ?? '—' }} · subject: {{ $mm['subject'] ?? '—' }} · gender: {{ $mm['gender'] ?? '—' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <flux:button wire:click="openEdit({{ $card->id }})" size="xs" variant="ghost" icon="pencil" />
                        @if ($card->status->value === 'active')
                            <flux:button wire:click="retire({{ $card->id }})" size="xs" variant="ghost" icon="archive-box">Retire</flux:button>
                        @else
                            <flux:button wire:click="activate({{ $card->id }})" size="xs" variant="ghost" icon="arrow-uturn-left">Activate</flux:button>
                        @endif
                        <flux:button wire:click="delete({{ $card->id }})" wire:confirm="Delete this card permanently?" size="xs" variant="ghost" icon="trash" />
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No cards here.</flux:text>
        @endforelse
    </div>

    {{ $cards->links() }}

    <flux:modal name="card-form" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit card' : 'Add a manual card' }}</flux:heading>
            <flux:textarea wire:model="cSpanish" label="Spanish" rows="2" />
            <flux:textarea wire:model="cEnglish" label="English" rows="2" />
            <flux:heading size="sm" class="text-zinc-500">Must match (leave blank to not enforce)</flux:heading>
            <div class="grid grid-cols-3 gap-3">
                <flux:input wire:model="mmTense" label="Tense" placeholder="present" />
                <flux:input wire:model="mmSubject" label="Subject" placeholder="1st_singular" />
                <flux:input wire:model="mmGender" label="Gender" placeholder="" />
            </div>
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save' : 'Add' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
