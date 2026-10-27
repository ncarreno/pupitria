@props(['on'])

<div x-data="{ shown: false, timeout: null }"
     x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
     x-show.transition.out.opacity.duration.1500ms="shown"
     x-transition:leave.opacity.duration.1500ms
     style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm font-medium text-pino-700']) }}>
    <span class="inline-flex items-center gap-1.5"><x-icono nombre="check-circulo" class="size-4" />{{ $slot->isEmpty() ? __('Saved.') : $slot }}</span>
</div>
