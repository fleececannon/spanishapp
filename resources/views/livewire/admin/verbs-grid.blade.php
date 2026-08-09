<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Verbs grid</flux:heading>
            <flux:text class="mt-1">
                {{ $unlockedCount }} of {{ $totalCount }} verbs unlocked. Tick a tense to let the AI use that verb in that form.
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.print-cards', ['type' => 'verbs']) }}" target="_blank" icon="printer">Print vocab cards</flux:button>
            <flux:modal.trigger name="add-verb">
                <flux:button variant="primary" icon="plus">Add verb</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @foreach ($verbsByTag as $tag => $verbs)
        <flux:card class="overflow-x-auto">
            <flux:heading size="lg" class="mb-3">{{ $tag }}</flux:heading>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                        <th class="py-2 pr-4 font-medium">Verb</th>
                        <th class="py-2 px-2 font-medium text-center">Unlocked</th>
                        <th class="py-2 px-2 font-medium text-center">Drill all</th>
                        <th class="py-2 px-2 font-medium text-center whitespace-nowrap">Vocab card</th>
                        @foreach ($tenses as $tense)
                            <th class="py-2 px-2 font-medium text-center whitespace-nowrap">{{ ucfirst($tense->value) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($verbs as $verb)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="verb-{{ $verb->id }}">
                            <td class="py-2 pr-4 whitespace-nowrap">
                                <span class="font-medium">{{ $verb->spanish }}</span>
                                <span class="text-zinc-500">— {{ $verb->english }}</span>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <input type="checkbox" @checked($verb->unlocked)
                                    wire:click="toggleUnlocked({{ $verb->id }})"
                                    class="size-4 rounded border-zinc-300 cursor-pointer" />
                            </td>
                            <td class="py-2 px-2 text-center">
                                <input type="checkbox" @checked($verb->drill_all_forms)
                                    wire:click="toggleDrill({{ $verb->id }})"
                                    class="size-4 rounded border-zinc-300 cursor-pointer" />
                            </td>
                            <td class="py-2 px-2 text-center">
                                <input type="checkbox" @checked($verb->vocab_card)
                                    wire:click="toggleVocab({{ $verb->id }})"
                                    class="size-4 rounded border-zinc-300 cursor-pointer" />
                            </td>
                            @foreach ($tenses as $tense)
                                <td class="py-2 px-2 text-center">
                                    <input type="checkbox"
                                        @checked(in_array($tense->value, $verb->enabled_tenses ?? [], true))
                                        wire:click="toggleTense({{ $verb->id }}, '{{ $tense->value }}')"
                                        class="size-4 rounded border-zinc-300 cursor-pointer" />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </flux:card>
    @endforeach

    <flux:modal name="add-verb" class="md:w-96">
        <form wire:submit="addVerb" class="space-y-4">
            <flux:heading size="lg">Add a verb</flux:heading>
            <flux:input wire:model="newSpanish" label="Spanish (infinitive)" placeholder="Hablar" />
            <flux:input wire:model="newEnglish" label="English" placeholder="to speak" />
            <flux:select wire:model.live="newGroup" label="Group" placeholder="Pick a group">
                @foreach ($groups as $group)
                    <flux:select.option value="{{ $group }}">{{ $group }}</flux:select.option>
                @endforeach
                <flux:select.option value="__new__">＋ New group…</flux:select.option>
            </flux:select>
            @if ($newGroup === '__new__')
                <flux:input wire:model="newGroupName" label="New group name" placeholder="Verb Set 5" />
            @endif
            <flux:error name="newGroup" />
            <flux:error name="newGroupName" />
            <flux:checkbox wire:model="newVocab" label="Vocab card" description="Adds a simple word → meaning card to the deck right away." />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Add</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
