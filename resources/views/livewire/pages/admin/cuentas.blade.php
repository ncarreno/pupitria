<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/**
 * Cuentas registradas, para el administrador: datos de la cuenta, recuentos de clases,
 * proyectos y fichas (nunca su contenido), los permisos de administrar y de valorar pruebas
 * y el alta de cuentas nuevas.
 */
new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    public bool $mostrarFormulario = false;

    public string $nombre = '';

    public string $email = '';

    public string $password = '';

    public bool $administrador = false;

    public bool $valorar = false;

    /** @var array{nombre: string, email: string, password: string}|null Datos de la cuenta recién creada, para pasárselos a la docente. */
    public ?array $creada = null;

    /**
     * La ruta ya pasa por el middleware `admin`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->esAdministrador(), 403);
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cuentas()
    {
        $buscar = trim($this->buscar);

        return User::query()
            ->withCount(['clases', 'proyectos', 'fichas'])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.addcslashes($buscar, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($buscar, '%_\\').'%')))
            ->latest()
            ->latest('id')
            ->paginate(25);
    }

    public function nueva(): void
    {
        $this->resetValidation();
        $this->reset('nombre', 'email', 'administrador', 'valorar', 'creada');
        $this->generarPassword();
        $this->mostrarFormulario = true;
    }

    public function generarPassword(): void
    {
        $this->password = Str::password(12, symbols: false);
    }

    /**
     * Alta hecha por el administrador: el email se da por verificado y la contraseña se le pasa
     * a la docente, que puede cambiarla en su perfil.
     */
    public function crear(): void
    {
        $this->email = Str::lower(trim($this->email));

        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $cuenta = User::create(['name' => $datos['nombre'], 'email' => $datos['email'], 'password' => $datos['password']]);
        $cuenta->forceFill([
            'email_verified_at' => now(),
            'rol' => $this->administrador ? 'admin' : 'docente',
            'puede_valorar' => $this->valorar,
        ])->save();

        $this->creada = ['nombre' => $cuenta->name, 'email' => $cuenta->email, 'password' => $datos['password']];
        $this->reset('nombre', 'email', 'password', 'administrador', 'valorar', 'mostrarFormulario', 'buscar');
        $this->resetPage();
    }

    public function cancelar(): void
    {
        $this->resetValidation();
        $this->reset('nombre', 'email', 'password', 'administrador', 'valorar', 'mostrarFormulario');
    }

    public function alternarAdministrador(int $id): void
    {
        $cuenta = User::findOrFail($id);

        if ($cuenta->is(auth()->user())) {
            $this->addError("cuenta.{$id}", 'No puedes quitarte a ti mismo el rol de administrador.');

            return;
        }

        $cuenta->forceFill(['rol' => $cuenta->esAdministrador() ? 'docente' : 'admin'])->save();
    }

    public function alternarValorar(int $id): void
    {
        $cuenta = User::findOrFail($id);
        $cuenta->forceFill(['puede_valorar' => ! $cuenta->puede_valorar])->save();
    }

    /**
     * Para cuando a una docente no le llega el email de confirmación.
     */
    public function marcarVerificada(int $id): void
    {
        $cuenta = User::findOrFail($id);

        if (! $cuenta->hasVerifiedEmail()) {
            $cuenta->markEmailAsVerified();
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Administración" titulo="Cuentas">
            Solo datos de la cuenta y recuentos: el contenido de cada docente es suyo.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-4">
        @if ($mostrarFormulario)
            <form wire:submit="crear" class="tarjeta overflow-hidden animate-aparecer">
                <header class="border-b border-stone-100 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-semibold tracking-tight text-stone-950">Nueva cuenta</h3>
                    <p class="mt-1 text-sm text-stone-600">El email queda verificado. Pásale la contraseña a la docente: podrá cambiarla en su perfil.</p>
                </header>

                <div class="divide-y divide-stone-100">
                    <div class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <p class="text-sm font-medium text-stone-950">La cuenta</p>
                            <p class="ayuda mt-1">Con este email y esta contraseña entrará en la app.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid content-start gap-2">
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input wire:model="nombre" id="nombre" type="text" required autofocus />
                                <x-input-error :messages="$errors->get('nombre')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="email" value="Email" />
                                <x-text-input wire:model="email" id="email" type="email" required />
                                <x-input-error :messages="$errors->get('email')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="password" value="Contraseña" />
                                <div class="flex gap-2">
                                    <x-text-input wire:model="password" id="password" type="text" class="cifra" required autocomplete="off" />
                                    <button type="button" wire:click="generarPassword" class="boton-icono shrink-0" aria-label="Generar otra contraseña" title="Generar otra"><x-icono nombre="refrescar" class="size-4" /></button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" />
                            </div>
                        </div>
                    </div>

                    <fieldset class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <legend class="text-sm font-medium text-stone-950">Permisos</legend>
                            <p class="ayuda mt-1">Sin marcar nada, es una docente.</p>
                        </div>
                        <div class="space-y-3 text-sm text-stone-700">
                            <label class="flex items-center gap-2.5">
                                <input type="checkbox" wire:model="administrador" class="casilla">
                                Administrador: configura la IA y ve todas las cuentas
                            </label>
                            <label class="flex items-center gap-2.5">
                                <input type="checkbox" wire:model="valorar" class="casilla">
                                Valora pruebas: ve y valora la batería de pruebas
                            </label>
                        </div>
                    </fieldset>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-stone-100 bg-stone-50/60 px-6 py-4 sm:px-8">
                    <x-secondary-button type="button" wire:click="cancelar">Cancelar</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="crear"><x-icono nombre="check" class="size-4" />Crear cuenta</x-primary-button>
                </div>
            </form>
        @endif

        @if ($creada)
            <div class="aviso-pino animate-aparecer" x-data="{ copiado: false }">
                <x-icono nombre="check-circulo" class="mt-0.5 size-5 shrink-0" />
                <div class="min-w-0 flex-1">
                    <p class="font-medium">Cuenta creada para {{ $creada['nombre'] }}. Apunta la contraseña ahora: no se vuelve a mostrar.</p>
                    <p x-ref="datos" class="mt-2 whitespace-pre-line font-mono text-[13px]">Email: {{ $creada['email'] }}
Contraseña: {{ $creada['password'] }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-secondary-button type="button" class="boton-sm"
                            x-on:click="navigator.clipboard.writeText($refs.datos.innerText).then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })">
                            <span x-show="! copiado" class="inline-flex items-center gap-2"><x-icono nombre="copiar" class="size-4" />Copiar los datos</span><span x-show="copiado" style="display: none" class="inline-flex items-center gap-2 text-pino-700"><x-icono nombre="check" class="size-4" />Copiado</span>
                        </x-secondary-button>
                        <button type="button" wire:click="$set('creada', null)" class="enlace text-xs">Ya los tengo, cerrar</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="relative block w-full max-w-md">
                <span class="sr-only">Buscar</span>
                <x-icono nombre="buscar" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Nombre o email" class="campo ps-10">
                <span wire:loading.delay wire:target="buscar" class="absolute right-3.5 top-1/2 -translate-y-1/2"><span class="punto-vivo text-pino-600"></span></span>
            </label>
            @unless ($mostrarFormulario)
                <x-primary-button wire:click="nueva"><x-icono nombre="mas" class="size-4" />Nueva cuenta</x-primary-button>
            @endunless
        </div>

        <div class="tarjeta overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th>Registro</th>
                        <th>Email</th>
                        <th class="!text-right">Clases</th>
                        <th class="!text-right">Proyectos</th>
                        <th class="!text-right">Fichas</th>
                        <th>Permisos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->cuentas as $c)
                        @php $iniciales = str($c->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''); @endphp
                        <tr wire:key="cuenta-{{ $c->id }}" class="align-top">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'inline-flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold uppercase ring-1 ring-inset',
                                        'bg-stone-900 text-pino-300 ring-stone-900' => $c->esAdministrador(),
                                        'bg-pino-50 text-pino-800 ring-pino-200' => ! $c->esAdministrador(),
                                    ])>{{ $iniciales }}</span>
                                    <div class="min-w-0">
                                        <span class="font-medium text-stone-950">{{ $c->name }}</span>
                                        @if ($c->is(auth()->user())) <span class="text-xs text-stone-500">(tú)</span> @endif
                                        <br><span class="text-[13px] text-stone-500">{{ $c->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="cifra whitespace-nowrap text-stone-500">{{ $c->created_at?->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap">
                                @if ($c->hasVerifiedEmail())
                                    <span class="insignia-pino"><x-icono nombre="check" class="size-3" />Verificado</span>
                                @else
                                    <span class="insignia-ambar">Sin verificar</span>
                                    <button type="button" wire:click="marcarVerificada({{ $c->id }})" wire:confirm="¿Dar por verificado el email de {{ $c->name }}?" class="enlace mt-1.5 block text-xs">Marcar verificado</button>
                                @endif
                            </td>
                            <td class="cifra text-right">{{ $c->clases_count }}</td>
                            <td class="cifra text-right">{{ $c->proyectos_count }}</td>
                            <td class="cifra text-right">{{ $c->fichas_count }}</td>
                            <td class="space-y-2">
                                <div class="flex flex-wrap gap-1">
                                    @if ($c->esAdministrador())
                                        <span class="insignia bg-stone-900 text-white ring-stone-900"><x-icono nombre="llave" class="size-3" />Administrador</span>
                                    @endif
                                    @if ($c->puede_valorar)
                                        <span class="insignia-ambar"><x-icono nombre="estrella" class="size-3" />Valora pruebas</span>
                                    @endif
                                    @if (! $c->esAdministrador() && ! $c->puede_valorar)
                                        <span class="insignia-neutra">Docente</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                    @unless ($c->is(auth()->user()))
                                        <button type="button" wire:click="alternarAdministrador({{ $c->id }})"
                                            wire:confirm="{{ $c->esAdministrador() ? '¿Quitar el rol de administrador a '.$c->name.'?' : '¿Hacer administrador a '.$c->name.'? Podrá configurar la IA y ver todas las cuentas.' }}"
                                            class="enlace">{{ $c->esAdministrador() ? 'Quitar administrador' : 'Hacer administrador' }}</button>
                                    @endunless
                                    <button type="button" wire:click="alternarValorar({{ $c->id }})" class="enlace">{{ $c->puede_valorar ? 'Quitar permiso de valorar' : 'Dar permiso de valorar' }}</button>
                                </div>
                                <x-input-error :messages="$errors->get('cuenta.'.$c->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-stone-500">No hay cuentas que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $this->cuentas->links() }}
    </div>
</div>
