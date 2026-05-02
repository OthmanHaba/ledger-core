<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filters</x-slot>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.75rem;">
            <x-filament::input.wrapper>
                <x-filament::input type="number" wire:model.live="account" placeholder="Account ID" />
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
        <x-slot name="heading">Account statement</x-slot>
        <div class="fi-ta-ctn">
            <table class="fi-ta-table" style="width:100%;">
                <thead class="fi-ta-header">
                    <tr class="fi-ta-row">
                        <th class="fi-ta-header-cell">Date</th>
                        <th class="fi-ta-header-cell">Journal entry</th>
                        <th class="fi-ta-header-cell">Direction</th>
                        <th class="fi-ta-header-cell">Amount</th>
                        <th class="fi-ta-header-cell">Memo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getRows() as $line)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell">{{ $line->entry?->posted_at }}</td>
                            <td class="fi-ta-cell">{{ $line->entry?->uuid }}</td>
                            <td class="fi-ta-cell">{{ $line->direction->value }}</td>
                            <td class="fi-ta-cell">{{ $line->amount }}</td>
                            <td class="fi-ta-cell">{{ $line->memo }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="5">Provide an Account ID to view the statement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
