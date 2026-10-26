<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inicio</h2>
    </x-slot>

    @php($clases = auth()->user()->clases()->with('areas')->get())

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($clases->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900">Empieza por configurar tu clase</h3>
                    <p class="text-sm text-gray-600">Con el curso, el número de alumnos y las áreas que impartes, los proyectos y las fichas saldrán ajustados a tu grupo y al currículo de tu ciclo.</p>
                    <a href="{{ route('clases') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Configurar mi clase</a>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Mis clases</h3>
                        <a href="{{ route('clases') }}" wire:navigate class="text-sm text-indigo-700 hover:underline">Gestionar</a>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($clases as $clase)
                            <li class="py-3 flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-medium text-gray-900">{{ $clase->nombre }}</span>
                                <span class="text-sm text-gray-600">{{ $clase->curso }}.º · {{ $clase->alumnos }} alumnos · {{ $clase->areas->pluck('nombre_corto')->implode(', ') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Crear un proyecto</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
