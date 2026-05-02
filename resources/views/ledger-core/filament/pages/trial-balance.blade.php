<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid gap-3 md:grid-cols-4">
            <x-filament::input.wrapper><x-filament::input type="number" wire:model.live="entity" placeholder="Entity ID" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            <x-filament::input.wrapper><x-filament::input wire:model.live="currency" placeholder="Currency" /></x-filament::input.wrapper>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left dark:bg-gray-900"><th class="p-3">Code</th><th class="p-3">Account</th><th class="p-3">Debit</th><th class="p-3">Credit</th><th class="p-3">Balance</th></tr></thead>
                <tbody>
                @foreach ($this->getRows() as $row)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="p-3">{{ $row['code'] }}</td><td class="p-3">{{ $row['name'] }}</td><td class="p-3">{{ $row['debit_total'] }}</td><td class="p-3">{{ $row['credit_total'] }}</td><td class="p-3">{{ $row['balance'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
