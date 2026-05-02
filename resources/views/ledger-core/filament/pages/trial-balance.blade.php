<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filters</x-slot>
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0.75rem;">
            <x-filament::input.wrapper>
                <x-filament::input type="number" wire:model.live="entity" placeholder="Entity ID" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="from" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="to" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input wire:model.live="currency" placeholder="Currency" />
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Trial balance</x-slot>
        <div class="fi-ta-ctn">
            <table class="fi-ta-table" style="width:100%;">
                <thead class="fi-ta-header">
                    <tr class="fi-ta-row">
                        <th class="fi-ta-header-cell">Code</th>
                        <th class="fi-ta-header-cell">Account</th>
                        <th class="fi-ta-header-cell">Debit</th>
                        <th class="fi-ta-header-cell">Credit</th>
                        <th class="fi-ta-header-cell">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getRows() as $row)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell">{{ $row['code'] }}</td>
                            <td class="fi-ta-cell">{{ $row['name'] }}</td>
                            <td class="fi-ta-cell">{{ $row['debit_total'] }}</td>
                            <td class="fi-ta-cell">{{ $row['credit_total'] }}</td>
                            <td class="fi-ta-cell">{{ $row['balance'] }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="5">Provide an Entity ID to view the trial balance.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
