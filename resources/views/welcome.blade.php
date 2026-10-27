<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Proyectos y fichas en tres niveles para Primaria, con el currículo de Asturias comprobado.">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600,700|geist-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="relative isolate flex min-h-[100dvh] flex-col overflow-x-clip">
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[820px] bg-[radial-gradient(55%_70%_at_80%_10%,rgba(66,138,122,0.16),transparent_70%)]"></div>
            <div aria-hidden="true" class="cuadricula-clara pointer-events-none absolute inset-x-0 top-0 -z-10 h-[720px] [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]"></div>

            <header class="contenedor flex items-center justify-between gap-3 py-5">
                <x-marca :href="url('/')" />
                @if (Route::has('login'))
                    <livewire:welcome.navigation />
                @endif
            </header>

            <main class="flex-1">
                {{-- Héroe --}}
                <section class="contenedor grid items-center gap-14 pb-20 pt-10 sm:pt-16 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:gap-10 lg:pb-28">
                    <div class="animate-aparecer">
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/80 py-1 pe-3 ps-1 text-[13px] font-medium text-stone-700 shadow-sm ring-1 ring-stone-200 backdrop-blur">
                            <span class="rounded-full bg-pino-700 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-white">Piloto</span>
                            Primaria · Principado de Asturias
                        </p>
                        <h1 class="mt-6 max-w-2xl text-balance text-4xl font-semibold leading-[1.04] tracking-tighter text-stone-950 sm:text-5xl lg:text-[3.6rem]">
                            Proyectos y fichas para tu clase de Primaria, con el currículo de Asturias.
                        </h1>
                        <p class="mt-6 max-w-[56ch] text-lg leading-relaxed text-stone-600">
                            Escribe tu idea en una frase y la app prepara el diseño del proyecto con criterios reales del Decreto 57/2022. Las fichas salen en tres niveles (refuerzo, estándar y ampliación) para que toda la clase trabaje lo mismo a la vez.
                        </p>

                        <div class="mt-9 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="boton-primario px-5 py-3 text-[15px]">
                                    Ir a mi panel
                                    <x-icono nombre="flecha-derecha" class="size-4" />
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="boton-primario group px-5 py-3 text-[15px]">
                                    Crear cuenta
                                    <x-icono nombre="flecha-derecha" class="size-4 transition duration-300 ease-suave group-hover:translate-x-0.5" />
                                </a>
                                <a href="{{ route('login') }}" class="boton-secundario px-5 py-3 text-[15px]">Entrar</a>
                            @endauth
                        </div>

                        <ul class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm text-stone-600">
                            <li class="inline-flex items-center gap-2"><x-icono nombre="escudo" class="size-[18px] text-pino-600" />Ningún dato de tus alumnos</li>
                            <li class="inline-flex items-center gap-2"><x-icono nombre="check-circulo" class="size-[18px] text-pino-600" />Criterios comprobados con el decreto</li>
                            <li class="inline-flex items-center gap-2"><x-icono nombre="lapiz" class="size-[18px] text-pino-600" />Todo se puede editar</li>
                        </ul>
                    </div>

                    {{-- Composición: documento del proyecto y la ficha en tres niveles --}}
                    <div aria-hidden="true" class="relative mx-auto h-[440px] w-full max-w-[560px] animate-aparecer [animation-delay:120ms] sm:h-[500px]">
                        <div class="absolute inset-x-6 top-0 rounded-[1.75rem] bg-white p-6 shadow-elevada ring-1 ring-stone-200/80 sm:inset-x-10">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-pino-700">Proyecto · 3.º</span>
                                <span class="insignia-pino"><x-icono nombre="check" class="size-3" />14 criterios</span>
                            </div>
                            <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-950">El agua en Asturias</p>
                            <p class="text-sm text-stone-500">De los ríos al mar · 6 semanas</p>
                            <div class="mt-5 grid grid-cols-[1.2fr_1fr] gap-3">
                                <div class="rounded-xl bg-stone-50 p-3 ring-1 ring-stone-200/70">
                                    <p class="text-[11px] font-semibold text-stone-500">Tareas</p>
                                    <div class="mt-2 space-y-1.5">
                                        @foreach (['w-full', 'w-5/6', 'w-4/6', 'w-5/6'] as $n => $w)
                                            <div class="flex items-center gap-2"><span class="inline-flex size-4 items-center justify-center rounded-full bg-pino-600 text-[9px] font-semibold text-white">{{ $n + 1 }}</span><span class="h-1.5 {{ $w }} rounded-full bg-stone-200"></span></div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="rounded-xl bg-stone-50 p-3 ring-1 ring-stone-200/70">
                                    <p class="text-[11px] font-semibold text-stone-500">Currículo</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach (['LCL 2.1', 'CN 2.1', 'MAT 4.2', 'CS 1.1'] as $c)
                                            <span class="codigo text-[10px]">{{ $c }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        @foreach ([
                            ['Refuerzo', 1, 'left-0 bottom-6 -rotate-[7deg] z-10', 'animate-flotar-lento'],
                            ['Ampliación', 3, 'right-0 bottom-10 rotate-[6deg] z-10', 'animate-flotar-lento'],
                            ['Estándar', 2, 'left-1/2 bottom-0 -translate-x-1/2 z-20', 'animate-flotar'],
                        ] as [$nivel, $puntos, $posicion, $animacion])
                            <div class="absolute {{ $posicion }} w-[46%] max-w-[230px]">
                                <div class="{{ $animacion }} rounded-2xl bg-white p-4 shadow-papel ring-1 ring-stone-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400">{{ $nivel }}</span>
                                        <span class="flex gap-1">
                                            @foreach ([1, 2, 3] as $k)
                                                <i @class(['block size-2 rounded-full ring-[1.5px] ring-pino-600', 'bg-pino-600' => $k <= $puntos])></i>
                                            @endforeach
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-stone-950">¿De dónde viene el agua?</p>
                                    <div class="mt-3 space-y-1.5">
                                        <div class="h-1.5 w-full rounded-full bg-stone-200"></div>
                                        <div class="h-1.5 w-4/5 rounded-full bg-stone-200"></div>
                                    </div>
                                    <div class="mt-3 space-y-2.5">
                                        @for ($l = 0; $l < $puntos + 1; $l++)
                                            <div class="border-b border-dotted border-stone-300"></div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="absolute -right-2 top-[51%] z-30 hidden items-center gap-2 rounded-full bg-stone-950 py-1.5 pe-3.5 ps-1.5 text-[13px] font-medium text-white shadow-elevada sm:flex">
                            <span class="inline-flex size-6 items-center justify-center rounded-full bg-pino-600"><x-icono nombre="check" class="size-3.5" /></span>
                            Comprobado con el Decreto 57/2022
                        </div>
                    </div>
                </section>

                {{-- Cómo funciona --}}
                <section class="border-y border-stone-200/80 bg-white">
                    <div class="contenedor py-20 lg:py-28">
                        <div class="max-w-2xl">
                            <p class="antetitulo">Cómo funciona</p>
                            <h2 class="mt-3 text-balance text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">De una frase a un proyecto listo para el aula.</h2>
                        </div>

                        <div class="mt-16 space-y-20 lg:space-y-28">
                            {{-- 01 --}}
                            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                                <div>
                                    <span class="cifra text-sm font-medium text-pino-700">01</span>
                                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-stone-950">Configura tu clase</h3>
                                    <p class="mt-3 max-w-[50ch] leading-relaxed text-stone-600">Curso, número de alumnos y áreas que impartes. Solo cuántos necesitan refuerzo o ampliación: nunca nombres ni datos de nadie.</p>
                                </div>
                                <div aria-hidden="true" class="rounded-[1.75rem] bg-stone-100/80 p-6 ring-1 ring-inset ring-stone-200/70 sm:p-10">
                                    <div class="rounded-2xl bg-white p-5 shadow-suave ring-1 ring-stone-200/80">
                                        <div class="flex items-center gap-4">
                                            <span class="inline-flex size-12 items-center justify-center rounded-xl bg-pino-700 text-lg font-semibold text-white">3.º</span>
                                            <div>
                                                <p class="font-semibold text-stone-950">3.º B</p>
                                                <p class="text-sm text-stone-500">2.º ciclo · 2026/2027</p>
                                            </div>
                                        </div>
                                        <div class="mt-5 flex h-2.5 overflow-hidden rounded-full bg-stone-100">
                                            <span class="w-[17%] bg-pino-300"></span><span class="w-[66%] bg-stone-300"></span><span class="w-[17%] bg-pino-700"></span>
                                        </div>
                                        <div class="mt-2.5 flex justify-between text-xs text-stone-500">
                                            <span><span class="cifra font-medium text-stone-800">4</span> refuerzo</span>
                                            <span><span class="cifra font-medium text-stone-800">15</span> estándar</span>
                                            <span><span class="cifra font-medium text-stone-800">4</span> ampliación</span>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-1.5">
                                            @foreach (['Lengua', 'Matemáticas', 'C. Naturales', 'Plástica'] as $a)
                                                <span class="insignia-neutra">{{ $a }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 02 --}}
                            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                                <div class="lg:order-2">
                                    <span class="cifra text-sm font-medium text-pino-700">02</span>
                                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-stone-950">Escribe la idea como se la contarías a una compañera</h3>
                                    <p class="mt-3 max-w-[50ch] leading-relaxed text-stone-600">Proyecto de aula o de todo el centro, de 1.º a 6.º. La app elige criterios y saberes de tu ciclo y descarta cualquier referencia que no exista en el decreto.</p>
                                </div>
                                <div aria-hidden="true" class="rounded-[1.75rem] bg-stone-100/80 p-6 ring-1 ring-inset ring-stone-200/70 sm:p-10 lg:order-1"
                                    x-data="{
                                        ideas: [
                                            'Proyecto sobre el agua en Asturias, de los ríos al mar, con un producto final para las familias',
                                            'Un mercado en clase para practicar el euro y las cuentas',
                                            'Los animales de Oceanía y cómo se adaptan a su ecosistema',
                                        ],
                                        texto: 'Proyecto sobre el agua en Asturias, de los ríos al mar, con un producto final para las familias',
                                        async init() {
                                            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                                            const espera = (ms) => new Promise((r) => setTimeout(r, ms));
                                            for (let i = 1; this.$el.isConnected; i = (i + 1) % this.ideas.length) {
                                                await espera(2600);
                                                for (let c = this.texto.length; c >= 0 && this.$el.isConnected; c--) { this.texto = this.texto.slice(0, c); await espera(10); }
                                                const idea = this.ideas[i];
                                                for (let c = 0; c <= idea.length && this.$el.isConnected; c++) { this.texto = idea.slice(0, c); await espera(28); }
                                            }
                                        },
                                    }">
                                    <div class="rounded-2xl bg-white p-5 shadow-suave ring-1 ring-stone-200/80">
                                        <p class="text-sm font-medium text-stone-800">Idea</p>
                                        <div class="mt-2 min-h-[88px] rounded-xl border border-pino-600 px-3.5 py-3 text-[15px] leading-relaxed text-stone-800 ring-4 ring-pino-600/15">
                                            <span x-text="texto">Proyecto sobre el agua en Asturias, de los ríos al mar, con un producto final para las familias</span><span class="ms-px inline-block h-[1.1em] w-[2px] translate-y-[3px] animate-pulse bg-pino-700"></span>
                                        </div>
                                        <div class="mt-4 flex items-center justify-between">
                                            <span class="flex gap-1.5">
                                                <span class="insignia-neutra">6 semanas</span>
                                                <span class="insignia-neutra">Mi clase</span>
                                            </span>
                                            <span class="boton-primario boton-sm"><x-icono nombre="chispa" class="size-4" />Crear proyecto</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 03 --}}
                            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                                <div>
                                    <span class="cifra text-sm font-medium text-pino-700">03</span>
                                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-stone-950">Fichas en tres niveles, sobre el mismo contenido</h3>
                                    <p class="mt-3 max-w-[50ch] leading-relaxed text-stone-600">Desde cada tarea del proyecto sacas su ficha. En la hoja impresa el nivel solo se ve como puntos, así nadie se siente señalado.</p>
                                </div>
                                <div aria-hidden="true" class="rounded-[1.75rem] bg-stone-100/80 p-6 ring-1 ring-inset ring-stone-200/70 sm:p-10">
                                    <div class="grid grid-cols-3 gap-3">
                                        @foreach (['Refuerzo' => 1, 'Estándar' => 2, 'Ampliación' => 3] as $nivel => $puntos)
                                            <div @class(['rounded-xl bg-white p-3.5 shadow-suave ring-1 ring-stone-200/80 transition duration-500 ease-suave hover:-translate-y-1', 'sm:-translate-y-3' => $puntos === 2])>
                                                <span class="flex gap-1">
                                                    @foreach ([1, 2, 3] as $k)
                                                        <i @class(['block size-2 rounded-full ring-[1.5px] ring-pino-600', 'bg-pino-600' => $k <= $puntos])></i>
                                                    @endforeach
                                                </span>
                                                <p class="mt-2.5 text-xs font-semibold text-stone-900">{{ $nivel }}</p>
                                                <div class="mt-3 space-y-1.5">
                                                    @for ($l = 0; $l < $puntos + 2; $l++)
                                                        <div class="h-1 rounded-full bg-stone-200 {{ $l % 2 ? 'w-4/5' : 'w-full' }}"></div>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Garantías --}}
                <section class="contenedor py-20 lg:py-28">
                    <div class="max-w-2xl">
                        <p class="antetitulo">Con garantías</p>
                        <h2 class="mt-3 text-balance text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">Pensado para que puedas fiarte de lo que llevas al aula.</h2>
                    </div>

                    <div class="mt-12 grid gap-4 lg:grid-cols-[1.35fr_1fr]">
                        <article class="relative overflow-hidden rounded-[1.75rem] bg-pino-950 p-8 text-pino-50 sm:p-10 lg:row-span-2">
                            <div aria-hidden="true" class="cuadricula absolute inset-0 [mask-image:radial-gradient(ellipse_at_bottom_right,black,transparent_70%)]"></div>
                            <div class="relative">
                                <span class="inline-flex size-11 items-center justify-center rounded-xl bg-white/10 ring-1 ring-inset ring-white/15"><x-icono nombre="libro" class="size-5 text-pino-300" /></span>
                                <h3 class="mt-6 text-2xl font-semibold tracking-tight text-white">Currículo comprobado</h3>
                                <p class="mt-3 max-w-[46ch] leading-relaxed text-pino-100/75">Cada criterio y cada saber se comprueban con el decreto. Lo que la IA se invente se descarta, y el proyecto te dice cuántas referencias ha quitado.</p>
                                <div aria-hidden="true" class="mt-8 space-y-2.5">
                                    @foreach ([
                                        ['LCL 2.1', 'Comprender el sentido de textos orales y multimodales sencillos…', true],
                                        ['CN 2.1', 'Formular preguntas y realizar predicciones razonadas…', true],
                                        ['MAT 9.9', 'Referencia que no existe en el decreto', false],
                                    ] as [$codigo, $texto, $valido])
                                        <div @class(['flex items-center gap-3 rounded-xl px-4 py-3 ring-1 ring-inset', 'bg-white/[0.06] ring-white/10' => $valido, 'bg-red-400/10 ring-red-300/20' => ! $valido])>
                                            <span @class(['font-mono text-xs font-medium', 'text-pino-300' => $valido, 'text-red-300 line-through' => ! $valido])>{{ $codigo }}</span>
                                            <span @class(['min-w-0 flex-1 truncate text-sm', 'text-pino-50/80' => $valido, 'text-red-200/70 line-through' => ! $valido])>{{ $texto }}</span>
                                            @if ($valido)
                                                <x-icono nombre="check-circulo" class="size-[18px] text-pino-400" />
                                            @else
                                                <span class="text-xs font-medium text-red-300">Descartado</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </article>

                        <article class="tarjeta p-8">
                            <span class="inline-flex size-11 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="escudo" class="size-5" /></span>
                            <h3 class="mt-5 text-xl font-semibold tracking-tight text-stone-950">Ajustado a tu clase, sin datos de alumnos</h3>
                            <p class="mt-2 leading-relaxed text-stone-600">Curso, número de alumnos y áreas que impartes. No guardamos ningún dato de tus alumnos, y cada docente solo ve lo suyo.</p>
                        </article>

                        <article class="tarjeta p-8">
                            <span class="inline-flex size-11 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="lapiz" class="size-5" /></span>
                            <h3 class="mt-5 text-xl font-semibold tracking-tight text-stone-950">Todo se puede editar</h3>
                            <p class="mt-2 leading-relaxed text-stone-600">Cambias cualquier texto con un clic y pides ajustes cuando quieras; cada cambio guarda una versión que puedes recuperar. Las fichas se imprimen tal cual.</p>
                        </article>
                    </div>
                </section>

                {{-- Llamada final --}}
                @guest
                    <section class="contenedor pb-24">
                        <div class="relative overflow-hidden rounded-[2rem] bg-stone-900 px-8 py-14 sm:px-14">
                            <div aria-hidden="true" class="absolute -right-20 -top-28 size-[380px] rounded-full bg-pino-600/30 blur-3xl"></div>
                            <div class="relative grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                                <div>
                                    <h2 class="text-balance text-3xl font-semibold tracking-tight text-white">Prepara tu primer proyecto esta semana.</h2>
                                    <p class="mt-3 max-w-[52ch] text-stone-300">Configura tu clase en un par de minutos y prueba con una idea que ya tengas en la cabeza.</p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('register') }}" class="boton bg-white px-5 py-3 text-[15px] text-stone-950 hover:bg-stone-100">Crear cuenta</a>
                                    <a href="{{ route('login') }}" class="boton px-5 py-3 text-[15px] text-white ring-1 ring-inset ring-white/20 hover:bg-white/10">Entrar</a>
                                </div>
                            </div>
                        </div>
                    </section>
                @endguest
            </main>

            <footer class="border-t border-stone-200/80">
                <div class="contenedor flex flex-col gap-3 py-8 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                    <x-marca :subtitulo="null" />
                    <p>Piloto para docentes de Primaria del Principado de Asturias.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
