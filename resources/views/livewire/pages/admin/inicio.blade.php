<?php

use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Generacion;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Portada de la administración: cifras generales de cuentas, contenido e IA. Solo recuentos,
 * nunca el contenido de cada docente.
 */
new #[Layout('layouts.admin')] class extends Component
{
    public const DIAS = 7;

    /**
     * La ruta ya pasa por el middleware `admin`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->esAdministrador(), 403);
    }

    /**
     * @return array<string, list<array{nombre: string, valor: int, detalle?: string}>>
     */
    #[Computed]
    public function cifras(): array
    {
        $desde = now()->subDays(self::DIAS);
        $generaciones = Generacion::where('created_at', '>=', $desde)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when estado = 'fallida' then 1 else 0 end) as fallidas")
            ->selectRaw('sum(case when respaldo then 1 else 0 end) as respaldo')
            ->first();

        return [
            'Cuentas' => [
                ['nombre' => 'Registradas', 'valor' => User::count(), 'detalle' => User::where('created_at', '>=', $desde)->count().' nuevas en '.self::DIAS.' días'],
                ['nombre' => 'Con el email verificado', 'valor' => User::whereNotNull('email_verified_at')->count()],
                ['nombre' => 'Con alguna clase', 'valor' => User::has('clases')->count()],
            ],
            'Contenido' => [
                ['nombre' => 'Clases', 'valor' => Clase::count()],
                ['nombre' => 'Proyectos', 'valor' => Proyecto::count(), 'detalle' => Proyecto::where('created_at', '>=', $desde)->count().' en '.self::DIAS.' días'],
                ['nombre' => 'Fichas', 'valor' => Ficha::count(), 'detalle' => Ficha::where('created_at', '>=', $desde)->count().' en '.self::DIAS.' días'],
            ],
            'IA en los últimos '.self::DIAS.' días' => [
                ['nombre' => 'Generaciones', 'valor' => (int) $generaciones->total],
                ['nombre' => 'Fallidas', 'valor' => (int) $generaciones->fallidas],
                ['nombre' => 'Respondidas por el respaldo', 'valor' => (int) $generaciones->respaldo],
            ],
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Administración" titulo="Resumen">
            Solo recuentos: el contenido de cada docente es suyo.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)_minmax(0,1fr)] escalonado">
            @foreach ($this->cifras as $grupo => $cifras)
                @php $principal = array_shift($cifras); @endphp
                <section @class([
                    'relative overflow-hidden rounded-[1.75rem] p-6 sm:p-7',
                    'bg-pino-950 text-stone-300' => $loop->first,
                    'tarjeta' => ! $loop->first,
                ])>
                    @if ($loop->first)
                        <div aria-hidden="true" class="cuadricula pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)]"></div>
                    @endif
                    <div class="relative">
                        <h3 @class(['text-[11px] font-semibold uppercase tracking-[0.14em]', 'text-pino-400' => $loop->first, 'text-stone-500' => ! $loop->first])>{{ $grupo }}</h3>
                        <p @class(['mt-5 text-sm', 'text-stone-400' => $loop->first, 'text-stone-600' => ! $loop->first])>{{ $principal['nombre'] }}</p>
                        <p @class(['cifra mt-1 text-5xl font-medium', 'text-white' => $loop->first, 'text-stone-950' => ! $loop->first])>{{ number_format($principal['valor'], 0, ',', '.') }}</p>
                        @isset($principal['detalle'])
                            <p @class(['mt-2 inline-flex items-center gap-1.5 text-[13px]', 'text-pino-300' => $loop->first, 'text-pino-700' => ! $loop->first])><x-icono nombre="flecha-arriba" class="size-3.5" />{{ $principal['detalle'] }}</p>
                        @endisset

                        <dl @class(['mt-6 grid grid-cols-2 gap-4 border-t pt-5', 'border-white/10' => $loop->first, 'border-stone-100' => ! $loop->first])>
                            @foreach ($cifras as $cifra)
                                <div>
                                    <dt @class(['text-[13px] leading-snug', 'text-stone-400' => $loop->parent->first, 'text-stone-500' => ! $loop->parent->first])>{{ $cifra['nombre'] }}</dt>
                                    <dd @class(['cifra mt-1 text-2xl font-medium', 'text-white' => $loop->parent->first, 'text-stone-950' => ! $loop->parent->first])>{{ number_format($cifra['valor'], 0, ',', '.') }}</dd>
                                    @isset($cifra['detalle'])
                                        <p class="mt-0.5 text-xs text-stone-500">{{ $cifra['detalle'] }}</p>
                                    @endisset
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </section>
            @endforeach
        </div>

        <a href="{{ route('admin.ia.uso') }}" wire:navigate class="tarjeta group flex items-center gap-4 p-5 transition hover:shadow-elevada sm:p-6">
            <span class="inline-flex size-10 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="grafico" class="size-5" /></span>
            <p class="flex-1 text-sm text-stone-600">
                El detalle por tarea, proveedor y modelo está en <span class="font-medium text-stone-950">Uso de la IA</span>.
            </p>
            <x-icono nombre="flecha-derecha" class="size-4 text-stone-400 transition duration-300 ease-suave group-hover:translate-x-1 group-hover:text-stone-700" />
        </a>
    </div>
</div>
