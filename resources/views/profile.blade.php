<x-app-layout>
    <x-slot name="header">
        <x-cabecera antetitulo="Tu cuenta" :titulo="__('Profile')">
            Tus datos de acceso. Lo que preparas en la app solo lo ves tú.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5 escalonado">
        <div class="tarjeta p-6 sm:p-8">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="tarjeta p-6 sm:p-8">
            <livewire:profile.update-estilo-form />
        </div>

        <div class="tarjeta p-6 sm:p-8">
            <livewire:profile.update-password-form />
        </div>

        <div class="tarjeta border-red-200/80 p-6 sm:p-8">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
