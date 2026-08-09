<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Words library</flux:heading>
            <flux:text class="mt-1">
                {{ $unlockedCount }} of {{ $totalCount }} words unlocked ·
                <span class="font-medium">Ingredient nouns: {{ $ingredientNouns }}</span> (keep this lean).
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.print-cards', ['type' => 'words']) }}" target="_blank" icon="printer">Print vocab cards</flux:button>
            <flux:button wire:click="openAdd" variant="primary" icon="plus">Add word</flux:button>
        </div>
    </div>

    @foreach ($wordsByCategory as $category => $words)
        <flux:card>
            <flux:heading size="lg" class="mb-3 capitalize">{{ $category }}</flux:heading>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($words as $word)
                    <div class="flex items-center gap-3 py-2" wire:key="word-{{ $word->id }}">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-zinc-500 whitespace-nowrap">
                            <input type="checkbox" @checked($word->unlocked)
                                wire:click="toggleUnlocked({{ $word->id }})"
                                class="size-4 rounded border-zinc-300" />
                            unlocked
                        </label>
                        <div class="flex-1 min-w-0">
                            <span class="font-medium">{{ $word->spanish }}</span>
                            <span class="text-zinc-500">— {{ $word->english }}</span>
                            @if ($word->gender)
                                <flux:badge size="sm" color="zinc">{{ $word->gender }}</flux:badge>
                            @endif
                        </div>
                        <button wire:click="toggleRole({{ $word->id }})" type="button">
                            <flux:badge size="sm" :color="$word->role->value === 'target' ? 'blue' : 'amber'">
                                {{ $word->role->value }}
                            </flux:badge>
                        </button>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-zinc-500 whitespace-nowrap">
                            <input type="checkbox" @checked($word->vocab_card)
                                wire:click="toggleVocab({{ $word->id }})"
                                class="size-4 rounded border-zinc-300" />
                            vocab card
                        </label>
                        <flux:button wire:click="openEdit({{ $word->id }})" size="xs" variant="ghost" icon="pencil" />
                        <flux:button wire:click="delete({{ $word->id }})" wire:confirm="Remove this word?" size="xs" variant="ghost" icon="trash" />
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endforeach

    <flux:modal name="word-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit word' : 'Add a word' }}</flux:heading>
            <flux:input wire:model="wSpanish" label="Spanish" placeholder="porque" />
            <flux:input wire:model="wEnglish" label="English" placeholder="because" />
            <flux:select wire:model="wCategory" label="Category">
                <flux:select.option value="pronoun">Pronoun</flux:select.option>
                <flux:select.option value="connector">Connector</flux:select.option>
                <flux:select.option value="adverb">Adverb</flux:select.option>
                <flux:select.option value="noun">Noun</flux:select.option>
                <flux:select.option value="adjective">Adjective</flux:select.option>
                <flux:select.option value="question">Question word</flux:select.option>
            </flux:select>
            <flux:select wire:model="wRole" label="Role">
                <flux:select.option value="target">Target (taught &amp; tested)</flux:select.option>
                <flux:select.option value="ingredient">Ingredient (flavor only)</flux:select.option>
            </flux:select>
            <flux:input wire:model="wGender" label="Gender (m / f, optional)" maxlength="1" placeholder="" />
            <flux:checkbox wire:model="wVocab" label="Vocab card" description="Adds a simple word → meaning card to the deck right away." />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save' : 'Add' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
