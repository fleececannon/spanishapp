<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Lessons</flux:heading>
            <flux:text class="mt-1">Conversation scripts to run with the kids. Open one to read or print it.</flux:text>
        </div>
        <flux:button wire:click="openAdd" variant="primary" icon="plus">Add lesson</flux:button>
    </div>

    <div class="space-y-2">
        @forelse ($lessons as $lesson)
            <flux:card wire:key="lesson-{{ $lesson->id }}" class="py-3">
                <div class="flex items-center gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {{ $lesson->position }}
                    </div>
                    <a href="{{ route('admin.lessons.show', $lesson) }}" wire:navigate class="min-w-0 flex-1">
                        <div class="font-medium">Lesson {{ $lesson->position }} · {{ $lesson->title }}</div>
                        <div class="truncate text-sm text-zinc-500">
                            @if ($lesson->minutes) ~{{ $lesson->minutes }} min · @endif{{ $lesson->subtitle }}
                        </div>
                    </a>
                    <flux:button :href="route('admin.lessons.print', $lesson)" target="_blank" size="xs" variant="ghost" icon="printer" />
                    <flux:button wire:click="openEdit({{ $lesson->id }})" size="xs" variant="ghost" icon="pencil" />
                    <flux:button wire:click="delete({{ $lesson->id }})" wire:confirm="Remove this lesson?" size="xs" variant="ghost" icon="trash" />
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No lessons yet. Add one to start the catalog.</flux:text>
        @endforelse
    </div>

    <flux:modal name="lesson-form" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit lesson' : 'Add a lesson' }}</flux:heading>
            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="position" type="number" label="Lesson #" min="1" />
                <flux:input wire:model="minutes" type="number" label="Minutes" placeholder="20" min="1" />
            </div>
            <flux:input wire:model="title" label="Title" placeholder="¿Quieres comer?" />
            <flux:input wire:model="subtitle" label="Subtitle" placeholder="What this lesson covers, in one line" />
            <flux:textarea wire:model="body" label="Lesson sheet (HTML)" rows="12" class="font-mono text-xs"
                placeholder="The lesson content as HTML — headings, dialogue tables, word banks." />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save' : 'Add' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
