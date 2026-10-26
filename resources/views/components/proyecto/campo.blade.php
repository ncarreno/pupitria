@props(['ruta', 'valor' => '', 'tag' => 'span', 'editable' => false])
<{{ $tag }} {{ $attributes }}
    @if ($editable)
        contenteditable="true"
        spellcheck="true"
        data-ruta="{{ $ruta }}"
        x-on:blur="$wire.actualizar(@js($ruta), $el.innerText)"
    @endif
>{{ $valor }}</{{ $tag }}>