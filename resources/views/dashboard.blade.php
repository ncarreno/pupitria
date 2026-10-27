<x-app-layout>
    @php
        $clases = auth()->user()->clases()->with('areas')->get();
        $nombre = str(auth()->user()->name)->before(' ');
    @endphp

    <x-slot name="header">
        <x-cabecera antetitulo="Inicio" :titulo="'Hola, '.$nombre">
            {{ $clases->isEmpty() ? 'Tres pasos y tendrás tu primer proyecto listo para el aula.' : 'Qué quieres preparar hoy.' }}
        </x-cabecera>
    </x-slot>

    <div class="contenedor">
        @if ($clases->isEmpty())
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <section class="tarjeta relative overflow-hidden p-8 sm:p-10 animate-aparecer">
                    <div aria-hidden="true" class="cuadricula-clara pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_60%)]"></div>
                    <div class="relative max-w-lg">
                        <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-pino-700 text-white shadow-suave"><x-icono nombre="grupo" class="size-6" /></span>
                        <h2 class="mt-6 text-2xl font-semibold tracking-tight text-stone-950">Empieza por configurar tu clase</h2>
                        <p class="mt-3 leading-relaxed text-stone-600">Con el curso, el número de alumnos y las áreas que impartes, los proyectos y las fichas saldrán ajustados a tu grupo y al currículo de tu ciclo.</p>
                        <a href="{{ route('clases') }}" wire:navigate class="boton-primario group mt-7">
                            Configurar mi clase
                            <x-icono nombre="flecha-derecha" class="size-4 transition duration-300 ease-suave group-hover:translate-x-0.5" />
                        </a>
                        <p class="mt-6 flex items-center gap-2 text-[13px] text-stone-500"><x-icono nombre="escudo" class="size-4 text-pino-600" />Solo cifras: ningún nombre ni dato de tus alumnos.</p>
                    </div>
                </section>

                <ol class="tarjeta divide-y divide-stone-100 escalonado">
                    @foreach ([
                        ['Configura tu clase', 'Curso, alumnos y áreas.', true],
                        ['Crea un proyecto', 'Escribe tu idea en una frase.', false],
                        ['Saca las fichas', 'En tres niveles, desde cada tarea.', false],
                    ] as $n => [$paso, $detalle, $actual])
                        <li class="flex items-center gap-4 p-5 sm:p-6">
                            <span @class([
                                'cifra inline-flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-medium',
                                'bg-pino-700 text-white ring-4 ring-pino-100' => $actual,
                                'bg-stone-100 text-stone-500 ring-1 ring-inset ring-stone-200' => ! $actual,
                            ])>{{ $n + 1 }}</span>
                            <div>
                                <p @class(['font-medium', 'text-stone-950' => $actual, 'text-stone-500' => ! $actual])>{{ $paso }}</p>
                                <p class="text-sm text-stone-500">{{ $detalle }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @else
            @php
                $recientes = auth()->user()->proyectos()->limit(5)->get()->map(fn ($p) => ['tipo' => 'proyecto', 'modelo' => $p])
                    ->concat(auth()->user()->fichas()->limit(5)->get()->map(fn ($f) => ['tipo' => 'ficha', 'modelo' => $f]))
                    ->sortByDesc(fn ($r) => $r['modelo']->updated_at)
                    ->take(5);
            @endphp

            <div class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
                    <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="group relative overflow-hidden rounded-[1.75rem] bg-pino-950 p-8 text-pino-50 transition duration-500 ease-suave hover:-translate-y-0.5 hover:shadow-elevada sm:p-10 animate-aparecer">
                        <div aria-hidden="true" class="cuadricula absolute inset-0 [mask-image:radial-gradient(ellipse_at_right,black,transparent_70%)]"></div>
                        <div aria-hidden="true" class="absolute -right-16 -top-20 size-72 rounded-full bg-pino-500/25 blur-3xl transition duration-700 group-hover:bg-pino-400/30"></div>
                        <div class="relative flex h-full flex-col justify-between gap-10">
                            <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-inset ring-white/15"><x-icono nombre="tablero" class="size-6 text-pino-300" /></span>
                            <div>
                                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Crear un proyecto</h2>
                                <p class="mt-2 max-w-[44ch] text-pino-100/75">De tu idea a un diseño completo con criterios, saberes, tareas y evaluación del decreto.</p>
                                <span class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-white">
                                    Empezar
                                    <x-icono nombre="flecha-derecha" class="size-4 transition duration-300 ease-suave group-hover:translate-x-1" />
                                </span>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('fichas.nueva') }}" wire:navigate class="tarjeta group flex flex-col justify-between gap-10 p-8 transition duration-500 ease-suave hover:-translate-y-0.5 hover:shadow-elevada animate-aparecer [animation-delay:80ms]">
                        <div class="flex items-start justify-between">
                            <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="capas" class="size-6" /></span>
                            <span aria-hidden="true" class="flex gap-3">
                                @foreach ([1, 2, 3] as $puntos)
                                    <span class="flex gap-0.5">
                                        @for ($k = 0; $k < $puntos; $k++)<i class="block size-1.5 rounded-full bg-pino-600"></i>@endfor
                                    </span>
                                @endforeach
                            </span>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-stone-950">Nueva ficha</h2>
                            <p class="mt-1.5 text-stone-600">En tres niveles sobre el mismo contenido: refuerzo, estándar y ampliación.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-pino-700">
                                Preparar ficha
                                <x-icono nombre="flecha-derecha" class="size-4 transition duration-300 ease-suave group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
                    <section class="tarjeta animate-aparecer [animation-delay:140ms]">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Mis clases</h3>
                            <a href="{{ route('clases') }}" wire:navigate class="enlace text-sm">Gestionar</a>
                        </div>
                        <ul class="divide-y divide-stone-100">
                            @foreach ($clases as $clase)
                                <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-stone-100 tabular-nums text-sm font-medium text-stone-700 ring-1 ring-inset ring-stone-200">{{ $clase->curso }}.º</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-stone-950">{{ $clase->nombre }}</p>
                                        <p class="truncate text-sm text-stone-500">{{ $clase->curso }}.º · {{ $clase->alumnos }} alumnos · {{ $clase->areas->pluck('nombre_corto')->implode(', ') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <section class="tarjeta animate-aparecer [animation-delay:200ms]">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Lo último que has preparado</h3>
                            <div class="flex gap-4 text-sm">
                                <a href="{{ route('proyectos') }}" wire:navigate class="enlace">Proyectos</a>
                                <a href="{{ route('fichas') }}" wire:navigate class="enlace">Fichas</a>
                            </div>
                        </div>
                        @if ($recientes->isEmpty())
                            <div class="px-6 py-10 text-center">
                                <p class="text-sm text-stone-500">Aquí aparecerán tus proyectos y fichas.</p>
                                <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario boton-sm mt-4"><x-icono nombre="mas" class="size-4" />Crear un proyecto</a>
                            </div>
                        @else
                            <ul class="divide-y divide-stone-100">
                                @foreach ($recientes as $r)
                                    @php
                                        $m = $r['modelo'];
                                        $esProyecto = $r['tipo'] === 'proyecto';
                                    @endphp
                                    <li>
                                        <a href="{{ $esProyecto ? route('proyectos.ver', $m) : route('fichas.ver', $m) }}" wire:navigate class="group flex items-center gap-4 px-5 py-3.5 transition hover:bg-stone-50 sm:px-6">
                                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-stone-500 ring-1 ring-stone-200 transition group-hover:text-pino-700">
                                                <x-icono :nombre="$esProyecto ? 'tablero' : 'ficha'" class="size-[18px]" />
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate font-medium text-stone-900">{{ $m->titulo ?: ($m->estaGenerando() ? 'Generando…' : str($esProyecto ? $m->idea : $m->tema)->limit(60)) }}</p>
                                                <p class="text-[13px] text-stone-500">{{ $esProyecto ? 'Proyecto' : 'Ficha' }} · {{ $m->updated_at->format('d/m/Y') }}</p>
                                            </div>
                                            @if ($m->estado === 'generando')
                                                <span class="insignia-pino"><span class="punto-vivo size-1.5"></span>generando</span>
                                            @elseif ($m->estado === 'error')
                                                <span class="insignia-rojo">error</span>
                                            @endif
                                            <x-icono nombre="chevron-derecha" class="size-4 text-stone-300 transition duration-300 ease-suave group-hover:translate-x-0.5 group-hover:text-stone-500" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
