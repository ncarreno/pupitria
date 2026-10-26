<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/**
 * Cuentas registradas, para el administrador: datos de la cuenta, recuentos de clases,
 * proyectos y fichas (nunca su contenido) y los permisos de administrar y de valorar pruebas.
 */
new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cuentas</h2>
                <p class="text-sm text-gray-500 mt-1">Solo datos de la cuenta y recuentos: el contenido de cada docente es suyo.</p>
            </div>
            <a href="{{ route('admin.ia') }}" wire:navigate class="text-sm text-indigo-700 hover:underline">Configuración de la IA</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <label class="grid gap-1 text-sm font-medium text-gray-700 max-w-md">Buscar
                    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Nombre o email" class="rounded-md border-gray-300">
                </label>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Registro</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3 text-right">Clases</th>
                            <th class="px-4 py-3 text-right">Proyectos</th>
                            <th class="px-4 py-3 text-right">Fichas</th>
                            <th class="px-4 py-3">Permisos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($this->cuentas as $c)
                            <tr wire:key="cuenta-{{ $c->id }}" class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $c->name }}</span>
                                    @if ($c->is(auth()->user())) <span class="text-xs text-gray-500">(tú)</span> @endif
                                    <br><span class="text-gray-600">{{ $c->email }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $c->created_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($c->hasVerifiedEmail())
                                        <span class="text-green-700">Verificado</span>
                                    @else
                                        <span class="text-amber-800">Sin verificar</span>
                                        <button type="button" wire:click="marcarVerificada({{ $c->id }})" wire:confirm="¿Dar por verificado el email de {{ $c->name }}?" class="block text-xs text-indigo-700 hover:underline">Marcar verificado</button>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">{{ $c->clases_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $c->proyectos_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $c->fichas_count }}</td>
                                <td class="px-4 py-3 space-y-1.5">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($c->esAdministrador())
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800">Administrador</span>
                                        @endif
                                        @if ($c->puede_valorar)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Valora pruebas</span>
                                        @endif
                                        @if (! $c->esAdministrador() && ! $c->puede_valorar)
                                            <span class="text-gray-500">Docente</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                        @unless ($c->is(auth()->user()))
                                            <button type="button" wire:click="alternarAdministrador({{ $c->id }})"
                                                wire:confirm="{{ $c->esAdministrador() ? '¿Quitar el rol de administrador a '.$c->name.'?' : '¿Hacer administrador a '.$c->name.'? Podrá configurar la IA y ver todas las cuentas.' }}"
                                                class="text-indigo-700 hover:underline">{{ $c->esAdministrador() ? 'Quitar administrador' : 'Hacer administrador' }}</button>
                                        @endunless
                                        <button type="button" wire:click="alternarValorar({{ $c->id }})" class="text-indigo-700 hover:underline">{{ $c->puede_valorar ? 'Quitar permiso de valorar' : 'Dar permiso de valorar' }}</button>
                                    </div>
                                    <x-input-error :messages="$errors->get('cuenta.'.$c->id)" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-4 text-gray-500">No hay cuentas que coincidan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $this->cuentas->links() }}
        </div>
    </div>
</div>
