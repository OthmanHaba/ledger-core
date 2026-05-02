<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filters</x-slot>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.75rem;">
            <x-filament::input.wrapper>
                <x-filament::input type="number" wire:model.live="entity" placeholder="Entity ID" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input type="number" wire:model.live="account" placeholder="Account ID" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input wire:model.live="referenceType" placeholder="Reference type" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input wire:model.live="referenceId" placeholder="Reference ID" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="from" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="to" />
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">General ledger</x-slot>
        <div class="fi-ta-ctn">
            <table class="fi-ta-table" style="width:100%;">
                <thead class="fi-ta-header">
                    <tr class="fi-ta-row">
                        <th class="fi-ta-header-cell">Date</th>
                        <th class="fi-ta-header-cell">Journal entry</th>
                        <th class="fi-ta-header-cell">Account</th>
                        <th class="fi-ta-header-cell">Debit</th>
                        <th class="fi-ta-header-cell">Credit</th>
                        <th class="fi-ta-header-cell">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getRows() as $line)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell">{{ $line->entry?->posted_at }}</td>
                            <td class="fi-ta-cell">{{ $line->entry?->uuid }}</td>
                            <td class="fi-ta-cell">{{ $line->account?->name }}</td>
                            <td class="fi-ta-cell">{{ $line->direction->value === 'debit' ? $line->amount : '' }}</td>
                            <td class="fi-ta-cell">{{ $line->direction->value === 'credit' ? $line->amount : '' }}</td>
                            <td class="fi-ta-cell">{{ $line->entry?->reference_type }} {{ $line->entry?->reference_id }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="6">Provide an Entity ID to view the general ledger.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
