{{-- Lo que le queda a la docente este mes. No sale nada si su cuenta no tiene límite. --}}
@php
    $usuario = auth()->user();
    $restantes = $usuario?->generacionesRestantes();
@endphp

@if ($restantes === 0)
    <div {{ $attributes->merge(['class' => 'aviso-ambar items-center']) }}>
        <x-icono nombre="aviso" class="size-5 text-amber-700" />
        <span>{{ $usuario->mensajeLimite() }}</span>
    </div>
@elseif ($restantes !== null)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-stone-500']) }}>
        <x-icono nombre="grafico" class="size-4" />
        Te {{ $restantes === 1 ? 'queda 1 generación' : "quedan {$restantes} generaciones" }} de {{ $usuario->limiteMensual() }} este mes.
    </span>
@endif
