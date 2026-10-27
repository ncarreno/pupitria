@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'aviso-pino']) }}>
        <x-icono nombre="check-circulo" class="size-5 text-pino-700" />
        <span class="font-medium">{{ $status }}</span>
    </div>
@endif
