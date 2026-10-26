@props(['ficha', 'nivel' => 'estandar', 'editable' => false])
@php
    $n = $ficha->contenido[$nivel] ?? ['consigna' => '', 'bloques' => []];
    $puntos = ['refuerzo' => 1, 'estandar' => 2, 'ampliacion' => 3][$nivel] ?? 2;
    $e = $editable;
    $base = "{$nivel}.bloques";
    $nombreArea = \App\Models\Area::where('codigo', $ficha->area)->value('nombre_corto') ?? $ficha->area;
@endphp

@once
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=kalam:400,700|atkinson-hyperlegible:400,400i,700" rel="stylesheet" />
    <style>
        .hoja-ficha{--azul:#2a6fdb;--rojo:#d62839;--tinta:#1f2a44;max-width:820px;margin:0 auto;background:#fff;color:var(--tinta);font-family:"Atkinson Hyperlegible",ui-sans-serif,system-ui,sans-serif;font-size:15px;line-height:1.5;padding:28px 32px}
        .hoja-ficha.refuerzo{font-size:17px}
        .hoja-ficha [contenteditable]{border-radius:3px;outline:none}
        .hoja-ficha [contenteditable]:hover{box-shadow:0 0 0 1px rgba(31,42,68,.25)}
        .hoja-ficha [contenteditable]:focus{box-shadow:0 0 0 2px var(--azul)}
        .hoja-ficha .cab{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px 16px;align-items:end;margin-bottom:12px}
        .hoja-ficha h1{font-family:"Kalam",cursive;font-weight:700;font-size:36px;line-height:1.05;margin:0}
        .hoja-ficha .sub{font-size:12px;opacity:.75;margin-top:4px}
        .hoja-ficha .puntos{display:flex;gap:4px;color:var(--azul)}
        .hoja-ficha .puntos i{width:10px;height:10px;border-radius:50%;border:1.5px solid currentColor;display:block}
        .hoja-ficha .puntos i.on{background:currentColor}
        .hoja-ficha .quien{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin:4px 0 14px;font-size:13px}
        .hoja-ficha .quien div{border-bottom:1.5px solid currentColor;padding-bottom:2px}
        .hoja-ficha .ilustracion{width:100%;max-height:260px;object-fit:contain;margin:0 0 14px}
        .hoja-ficha .consigna{font-weight:700;margin:0 0 12px}
        .hoja-ficha .b{border:1.5px solid var(--tinta);padding:12px 14px;margin:0 0 14px;break-inside:avoid}
        .hoja-ficha .b h2{font-family:"Kalam",cursive;font-size:20px;font-weight:700;color:var(--rojo);margin:0 0 8px;line-height:1.1}
        .hoja-ficha .b p{margin:0}
        .hoja-ficha .lineas{display:grid;gap:22px;margin-top:10px}
        .hoja-ficha .lineas span{border-bottom:1.5px dotted currentColor;display:block;height:1px}
        .hoja-ficha .opc{display:flex;flex-wrap:wrap;gap:6px 18px;margin:4px 0 10px}
        .hoja-ficha .opc span{display:inline-flex;gap:6px;align-items:center}
        .hoja-ficha .opc span::before{content:"";width:14px;height:14px;border:1.5px solid currentColor;border-radius:50%}
        .hoja-ficha .banco{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
        .hoja-ficha .banco span{border:1.5px dashed currentColor;padding:2px 10px}
        .hoja-ficha table{width:100%;border-collapse:collapse;margin-top:6px}
        .hoja-ficha th,.hoja-ficha td{border:1.5px solid currentColor;padding:8px;text-align:left;vertical-align:top}
        .hoja-ficha td.vacia{height:44px}
        .hoja-ficha .vf{display:grid;grid-template-columns:minmax(0,1fr) 34px;gap:6px 10px;align-items:center}
        .hoja-ficha .vf b{border:1.5px solid currentColor;height:26px;display:block}
        .hoja-ficha .rel{display:grid;grid-template-columns:minmax(0,1fr) 60px minmax(0,1fr);gap:10px;align-items:center}
        .hoja-ficha .rel .c{border:1.5px solid currentColor;padding:6px 8px}
        .hoja-ficha .rel .p{display:flex;justify-content:space-between}
        .hoja-ficha .rel .p i{width:9px;height:9px;border-radius:50%;background:currentColor;display:block}
        .hoja-ficha .dibujo{height:190px;border:2px dashed currentColor;margin-top:8px}
        .hoja-ficha .pie{font-size:11px;opacity:.7;margin-top:6px}
        @media (max-width:640px){.hoja-ficha{padding:18px}.hoja-ficha h1{font-size:28px}}
        @media print{
            body *{visibility:hidden}
            .hoja-ficha,.hoja-ficha *{visibility:visible}
            .hoja-ficha{position:absolute;left:0;top:0;width:100%;max-width:none;padding:0}
            .hoja-ficha [contenteditable]:hover,.hoja-ficha [contenteditable]:focus{box-shadow:none}
        }
    </style>
@endonce

<article {{ $attributes->merge(['class' => 'hoja-ficha '.$nivel]) }}>
    <div class="cab">
        <div>
            <x-proyecto.campo :editable="$e" ruta="titulo" tag="h1" :valor="$ficha->titulo" />
            <div class="sub">{{ $nombreArea }} · {{ $ficha->curso }}.º de Primaria</div>
        </div>
        <div class="puntos" aria-label="Nivel {{ \App\Models\Ficha::NIVELES[$nivel] ?? '' }}">
            @foreach ([1, 2, 3] as $k)<i class="{{ $k <= $puntos ? 'on' : '' }}"></i>@endforeach
        </div>
    </div>
    <div class="quien"><div>Nombre:</div><div>Fecha:</div></div>

    @if ($ficha->urlImagen())
        <img src="{{ $ficha->urlImagen() }}" alt="" class="ilustracion">
    @endif

    <x-proyecto.campo :editable="$e" :ruta="$nivel.'.consigna'" tag="p" class="consigna" :valor="$n['consigna']" />

    @foreach ($n['bloques'] as $i => $b)
        @php $p = "{$base}.{$i}"; @endphp
        <section class="b">
            @if ($b['titulo'] !== '' || $b['enunciado'] !== '')
                <x-proyecto.campo :editable="$e" :ruta="$p.($b['titulo'] !== '' ? '.titulo' : '.enunciado')" tag="h2" :valor="$b['titulo'] !== '' ? $b['titulo'] : $b['enunciado']" />
            @endif

            @switch($b['tipo'])
                @case('lectura')
                    <x-proyecto.campo :editable="$e" :ruta="$p.'.texto'" tag="p" :valor="$b['texto']" />
                    @break
                @case('preguntas')
                    @foreach ($b['preguntas'] as $j => $q)
                        <div style="margin-bottom:10px">{{ $j + 1 }}. <x-proyecto.campo :editable="$e" :ruta="$p.'.preguntas.'.$j" :valor="$q" />
                            <div class="lineas">@for ($l = 0; $l < $b['lineas']; $l++)<span></span>@endfor</div>
                        </div>
                    @endforeach
                    @break
                @case('opciones')
                    @foreach ($b['items'] as $j => $it)
                        <div>{{ $j + 1 }}. <x-proyecto.campo :editable="$e" :ruta="$p.'.items.'.$j.'.pregunta'" :valor="$it['pregunta']" />
                            <div class="opc">@foreach ($it['opciones'] as $k => $o)<span><x-proyecto.campo :editable="$e" :ruta="$p.'.items.'.$j.'.opciones.'.$k" :valor="$o" /></span>@endforeach</div>
                        </div>
                    @endforeach
                    @break
                @case('completar')
                    <x-proyecto.campo :editable="$e" :ruta="$p.'.texto'" tag="p" :valor="preg_replace('/_{2,}/', '__________', $b['texto'])" />
                    @if ($b['banco'])
                        <div class="banco">@foreach ($b['banco'] as $k => $w)<span><x-proyecto.campo :editable="$e" :ruta="$p.'.banco.'.$k" :valor="$w" /></span>@endforeach</div>
                    @endif
                    @break
                @case('tabla')
                    <table>
                        <thead><tr>@foreach ($b['columnas'] as $k => $col)<th><x-proyecto.campo :editable="$e" :ruta="$p.'.columnas.'.$k" :valor="$col" /></th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($b['filas'] as $j => $fila)
                                <tr>@foreach ($fila as $k => $celda)
                                    @if ($celda !== '')<td><x-proyecto.campo :editable="$e" :ruta="$p.'.filas.'.$j.'.'.$k" :valor="$celda" /></td>@else<td class="vacia"></td>@endif
                                @endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                    @break
                @case('verdadero_falso')
                    <div class="vf">@foreach ($b['frases'] as $j => $f)<span><x-proyecto.campo :editable="$e" :ruta="$p.'.frases.'.$j" :valor="$f" /></span><b></b>@endforeach</div>
                    @break
                @case('relacionar')
                    <div class="rel">
                        @foreach ($b['izquierda'] as $j => $izq)
                            <div class="c"><x-proyecto.campo :editable="$e" :ruta="$p.'.izquierda.'.$j" :valor="$izq" /></div>
                            <div class="p"><i></i><i></i></div>
                            <div class="c"><x-proyecto.campo :editable="$e" :ruta="$p.'.derecha.'.$j" :valor="$b['derecha'][$j] ?? ''" /></div>
                        @endforeach
                    </div>
                    @break
                @case('dibujo')
                    <div class="dibujo"></div>
                    @break
            @endswitch
        </section>
    @endforeach

    @if ($ficha->criterios->isNotEmpty())
        <p class="pie">Criterios: {{ $ficha->criterios->map(fn ($c) => $ficha->area.' '.$c->codigo)->implode(', ') }}</p>
    @endif
</article>
