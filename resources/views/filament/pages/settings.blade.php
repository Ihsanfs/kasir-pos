<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
            <x-filament::button type="submit" size="lg">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
