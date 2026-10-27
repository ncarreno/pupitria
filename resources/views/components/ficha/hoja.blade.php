@props(['ficha', 'nivel' => 'estandar', 'editable' => false])
@php
    $estilo = $ficha->estiloVisual();
    $n = $ficha->contenido[$nivel] ?? ['consigna' => '', 'bloques' => []];
    $puntos = ['refuerzo' => 1, 'estandar' => 2, 'ampliacion' => 3][$nivel] ?? 2;
    $e = $editable;
    $base = "{$nivel}.bloques";
    $nombreArea = \App\Models\Area::where('codigo', $ficha->area)->value('nombre_corto') ?? $ficha->area;
@endphp

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="{{ \App\View\EstilosDocumento::urlFuentes($estilo) }}" rel="stylesheet" />

<article {{ $attributes->merge(['class' => "hoja-ficha {$nivel} t-{$estilo}"]) }}>
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
        <section class="b b-{{ $b['tipo'] }}">
            @if ($b['titulo'] !== '')
                <x-proyecto.campo :editable="$e" :ruta="$p.'.titulo'" tag="h2" :valor="$b['titulo']" />
            @endif
            @if ($b['enunciado'] !== '')
                <x-proyecto.campo :editable="$e" :ruta="$p.'.enunciado'" :tag="$b['titulo'] !== '' ? 'p' : 'h2'" class="enunciado" :valor="$b['enunciado']" />
            @endif

            @switch($b['tipo'])
                @case('lectura')
                    <x-proyecto.campo :editable="$e" :ruta="$p.'.texto'" tag="p" :valor="$b['texto']" />
                    @break
                @case('preguntas')
                    @foreach ($b['preguntas'] as $j => $q)
                        <div class="pregunta">{{ $j + 1 }}. <x-proyecto.campo :editable="$e" :ruta="$p.'.preguntas.'.$j" :valor="$q" />
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
