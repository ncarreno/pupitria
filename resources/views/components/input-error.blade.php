@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-[13px] font-medium text-red-700']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><x-icono nombre="aviso" class="mt-px size-4" />{{ $message }}</li>
        @endforeach
    </ul>
@endif
