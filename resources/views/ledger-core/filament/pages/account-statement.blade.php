<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            <x-filament::input.wrapper><x-filament::input type="number" wire:model.live="account" placeholder="Account ID" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left dark:bg-gray-900"><th class="p-3">Date</th><th class="p-3">Journal entry</th><th class="p-3">Direction</th><th class="p-3">Amount</th><th class="p-3">Memo</th></tr></thead>
                <tbody>
                @foreach ($this->getRows() as $line)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="p-3">{{ $line->entry?->posted_at }}</td><td class="p-3">{{ $line->entry?->uuid }}</td><td class="p-3">{{ $line->direction->value }}</td><td class="p-3">{{ $line->amount }}</td><td class="p-3">{{ $line->memo }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
