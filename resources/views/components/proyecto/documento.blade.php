@props(['proyecto', 'editable' => false])
@php
    use App\Models\Area;
    use App\Models\Criterio;
    use App\Models\Saber;
    use App\Services\Curriculo\CatalogoCurriculo;

    $c = $proyecto->contenido ?? [];
    $idsCriterio = data_get($c, 'curriculo.criterios', []);
    $derivados = app(CatalogoCurriculo::class)->derivados($idsCriterio);
    $criterios = Criterio::with('area')->findMany($idsCriterio)->sortBy(fn ($cr) => array_search($cr->id, $idsCriterio));
    $saberes = Saber::with('area')->findMany(data_get($c, 'curriculo.saberes', []));
    $nombreArea = Area::pluck('nombre_corto', 'codigo');
    $ods = [1 => 'Fin de la pobreza', 2 => 'Hambre cero', 3 => 'Salud y bienestar', 4 => 'Educación de calidad', 5 => 'Igualdad de género', 6 => 'Agua limpia y saneamiento', 7 => 'Energía asequible y no contaminante', 8 => 'Trabajo decente y crecimiento económico', 9 => 'Industria, innovación e infraestructura', 10 => 'Reducción de las desigualdades', 11 => 'Ciudades y comunidades sostenibles', 12 => 'Producción y consumo responsables', 13 => 'Acción por el clima', 14 => 'Vida submarina', 15 => 'Vida de ecosistemas terrestres', 16 => 'Paz, justicia e instituciones sólidas', 17 => 'Alianzas para lograr los objetivos'];
    $corto = fn (string $t, int $n = 90) => str(preg_split('/ para | a través de /', $t)[0])->rtrim(',;.')->limit($n, '…', preserveWords: true);
    $e = $editable;
@endphp

@once
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=kalam:400,700|atkinson-hyperlegible:400,400i,700" rel="stylesheet" />
    <style>
        .doc-proyecto{--azul:#2a6fdb;--rojo:#d62839;--tinta:#1f2a44;position:relative;background-color:#fdfefe;background-image:linear-gradient(#dbe6f5 1px,transparent 1px),linear-gradient(90deg,#dbe6f5 1px,transparent 1px);background-size:18px 18px;color:var(--tinta);font-family:"Atkinson Hyperlegible",ui-sans-serif,system-ui,sans-serif;font-size:13.5px;line-height:1.4;padding:26px 28px 30px 74px}
        .doc-proyecto::before{content:"";position:absolute;top:0;bottom:0;left:44px;width:2px;background:#e8a3aa}
        .doc-proyecto [contenteditable]{border-radius:3px;outline:none}
        .doc-proyecto [contenteditable]:hover{box-shadow:0 0 0 1px rgba(31,42,68,.25)}
        .doc-proyecto [contenteditable]:focus{box-shadow:0 0 0 2px var(--azul);background:#fff}
        .doc-proyecto .titulo{font-family:"Kalam",cursive;font-weight:700;font-size:44px;line-height:1.05;margin:0}
        .doc-proyecto .titulo span{background:linear-gradient(transparent 58%,#ffe066 58%)}
        .doc-proyecto .lema{font-family:"Kalam",cursive;font-size:18px;color:var(--azul);margin:6px 0 10px}
        .doc-proyecto .meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
        .doc-proyecto .meta div{border:1.5px solid var(--tinta);background:#fff;padding:5px 10px;display:flex;gap:8px;align-items:baseline}
        .doc-proyecto .k{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--rojo)}
        .doc-proyecto .rejilla{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px}
        .doc-proyecto .s{background:rgba(255,255,255,.9);border:1.5px solid var(--tinta);box-shadow:3px 3px 0 var(--tinta);padding:12px 14px;min-width:0}
        .doc-proyecto .s h3{font-family:"Kalam",cursive;font-size:20px;font-weight:700;color:var(--rojo);margin:0 0 8px;line-height:1}
        .doc-proyecto .s:nth-child(3n+2) h3{color:var(--azul)}
        .doc-proyecto .s p{margin:0 0 6px}
        .doc-proyecto .s ul{margin:0;padding-left:18px;list-style:disc;display:grid;gap:3px}
        .doc-proyecto .b{font-weight:700}
        .doc-proyecto .cod{font-weight:700;font-size:10.5px;white-space:nowrap;color:var(--rojo)}
        .doc-proyecto .crit{display:grid;grid-template-columns:auto minmax(0,1fr);gap:2px 8px;font-size:11.5px;margin:4px 0 8px}
        .doc-proyecto .tareas{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .doc-proyecto .tarea{display:grid;grid-template-columns:22px minmax(0,1fr);gap:8px}
        .doc-proyecto .tarea .n{background:var(--azul);color:#fff;border-radius:50%;width:22px;height:22px;line-height:22px;text-align:center;font-weight:700;font-size:12px}
        .doc-proyecto .tarea .det{font-size:10.5px;opacity:.8}
        .doc-proyecto .ods div{display:flex;gap:8px;align-items:center;margin-bottom:4px}
        .doc-proyecto .ods span.num{background:var(--azul);color:#fff;font-weight:700;min-width:30px;text-align:center}
        .doc-proyecto table{width:100%;border-collapse:collapse;font-size:11.5px}
        .doc-proyecto th,.doc-proyecto td{border:1px solid #9fb6d9;padding:5px 6px;text-align:left;vertical-align:top}
        .doc-proyecto .verif{font-size:10.5px;margin-top:6px;color:#1d6b35}
        .doc-proyecto .c3{grid-column:span 3}.doc-proyecto .c4{grid-column:span 4}.doc-proyecto .c5{grid-column:span 5}.doc-proyecto .c8{grid-column:span 8}.doc-proyecto .c12{grid-column:span 12}
        @media (max-width:860px){.doc-proyecto{padding:18px 16px 22px 54px}.doc-proyecto::before{left:30px}.doc-proyecto .rejilla{grid-template-columns:minmax(0,1fr)}.doc-proyecto .rejilla>*{grid-column:1/-1}.doc-proyecto .tareas{grid-template-columns:1fr}.doc-proyecto .titulo{font-size:34px}}
    </style>
@endonce

<article {{ $attributes->merge(['class' => 'doc-proyecto']) }}>
    <h1 class="titulo"><x-proyecto.campo :editable="$e" ruta="titulo" :valor="$c['titulo'] ?? ''" /></h1>
    <x-proyecto.campo :editable="$e" ruta="lema" tag="p" class="lema" :valor="$c['lema'] ?? ''" />
    <div class="meta">
        <div><span class="k">Áreas</span><span>{{ collect($proyecto->areas)->map(fn ($a) => $nombreArea[$a] ?? $a)->implode(', ') }}</span></div>
        <div><span class="k">Curso</span><span>{{ $proyecto->tipo === 'centro' ? 'De 1.º a 6.º · ficha de '.$proyecto->curso.'.º' : $proyecto->curso.'.º de Primaria' }}</span></div>
        <div><span class="k">Temporalización</span><x-proyecto.campo :editable="$e" ruta="temporalizacion" :valor="$c['temporalizacion'] ?? ''" /></div>
    </div>

    <div class="rejilla">
        <section class="s c4">
            <h3>Narración y escenario</h3>
            <p><span class="b">Secuencia: </span><x-proyecto.campo :editable="$e" ruta="narracion.secuencia" :valor="data_get($c, 'narracion.secuencia')" /></p>
            <p><span class="b">Vinculación con el alumnado: </span><x-proyecto.campo :editable="$e" ruta="narracion.vinculacion" :valor="data_get($c, 'narracion.vinculacion')" /></p>
        </section>

        <section class="s c5">
            <h3>Relación con el currículo</h3>
            <p><span class="b">Competencias clave: </span>{{ $derivados['competencias_clave']->map(fn ($n) => str($n)->replaceMatches('/^Competencia (en )?/i', '')->ucfirst())->implode('; ') ?: '—' }}</p>
            <p class="b" style="margin-bottom:2px">Competencias específicas</p>
            <ul>
                @foreach ($derivados['competencias'] as $comp)
                    <li><span class="cod">{{ $comp->area->codigo }} CE{{ $comp->numero }}</span> {{ $corto($comp->enunciado) }}</li>
                @endforeach
            </ul>
            <p class="b" style="margin:8px 0 0">Criterios de evaluación</p>
            <div class="crit">
                @foreach ($criterios as $cr)
                    <span class="cod">{{ $cr->area->codigo }} {{ $cr->codigo }}</span><span title="{{ $cr->texto }}">{{ $corto($cr->texto, 85) }}</span>
                @endforeach
            </div>
            <p class="b" style="margin-bottom:2px">Saberes básicos</p>
            <ul>
                @foreach ($saberes as $sb)
                    <li>{{ $corto($sb->texto, 110) }} <span class="cod">({{ $sb->area->codigo }})</span></li>
                @endforeach
            </ul>
            <p style="margin-top:8px"><span class="b">Descriptores: </span><span class="cod" style="white-space:normal">{{ $derivados['descriptores']->pluck('codigo')->implode(', ') }}</span></p>
            <p class="verif">✓ {{ $criterios->count() }} criterios comprobados con el Decreto 57/2022 @if ($proyecto->ids_descartados) · {{ $proyecto->ids_descartados }} referencias inventadas descartadas @endif</p>
        </section>

        <section class="s c3 ods">
            <h3>ODS y desafíos</h3>
            <p><span class="b">Desafío: </span><x-proyecto.campo :editable="$e" ruta="ods.desafio" :valor="data_get($c, 'ods.desafio')" /></p>
            @foreach (data_get($c, 'ods.lista', []) as $n)
                <div><span class="num">{{ $n }}</span><span>{{ $ods[$n] ?? '' }}</span></div>
            @endforeach
        </section>

        <section class="s c4">
            <h3>Producto final y reto</h3>
            <x-proyecto.campo :editable="$e" ruta="producto_final" tag="p" :valor="$c['producto_final'] ?? ''" />
        </section>

        <section class="s c8">
            <h3>Tareas y actividades</h3>
            <div class="tareas">
                @foreach ($c['tareas'] ?? [] as $i => $t)
                    <div class="tarea">
                        <span class="n">{{ $i + 1 }}</span>
                        <div>
                            <x-proyecto.campo :editable="$e" :ruta="'tareas.'.$i.'.titulo'" tag="div" class="b" :valor="$t['titulo']" />
                            <x-proyecto.campo :editable="$e" :ruta="'tareas.'.$i.'.descripcion'" tag="div" :valor="$t['descripcion']" />
                            <div class="det">
                                {{ collect($t['areas'])->map(fn ($a) => $nombreArea[$a] ?? $a)->implode(' · ') }}
                                @if ($t['criterios']) — {{ collect($t['criterios'])->map(fn ($id) => str($id)->replaceMatches('/^(\w+)\.C\d\./', '$1 '))->implode(', ') }} @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="s c4">
            <h3>Agrupamientos y espacios</h3>
            <p><span class="b">Agrupamientos: </span><x-proyecto.campo :editable="$e" ruta="agrupamientos" :valor="$c['agrupamientos'] ?? ''" /></p>
            <p><span class="b">Espacios: </span><x-proyecto.campo :editable="$e" ruta="espacios" :valor="$c['espacios'] ?? ''" /></p>
        </section>
        <section class="s c4">
            <h3>Metodologías</h3>
            <ul>
                @foreach ($c['metodologias'] ?? [] as $i => $m)
                    <li><x-proyecto.campo :editable="$e" :ruta="'metodologias.'.$i.'.nombre'" class="b" :valor="$m['nombre']" />: <x-proyecto.campo :editable="$e" :ruta="'metodologias.'.$i.'.descripcion'" :valor="$m['descripcion']" /></li>
                @endforeach
            </ul>
        </section>
        <section class="s c4">
            <h3>Recursos</h3>
            <x-proyecto.campo :editable="$e" ruta="recursos" tag="p" :valor="$c['recursos'] ?? ''" />
        </section>

        <section class="s c4">
            <h3>TIC</h3>
            <x-proyecto.campo :editable="$e" ruta="tic" tag="p" :valor="$c['tic'] ?? ''" />
        </section>
        <section class="s c4">
            <h3>Evaluación</h3>
            <ul>
                @foreach ($c['evaluacion'] ?? [] as $i => $ev)
                    <li><x-proyecto.campo :editable="$e" :ruta="'evaluacion.'.$i.'.instrumento'" class="b" :valor="$ev['instrumento']" />: <x-proyecto.campo :editable="$e" :ruta="'evaluacion.'.$i.'.que'" :valor="$ev['que']" /></li>
                @endforeach
            </ul>
        </section>
        <section class="s c4">
            <h3>Difusión</h3>
            <ul>
                @foreach ($c['difusion'] ?? [] as $i => $d)
                    <li><x-proyecto.campo :editable="$e" :ruta="'difusion.'.$i" :valor="$d" /></li>
                @endforeach
            </ul>
        </section>

        @if ($proyecto->tipo === 'centro' && ! empty($c['itinerario']))
            <section class="s c12">
                <h3>Itinerario del centro</h3>
                <div style="overflow-x:auto">
                    <table>
                        <thead><tr><th>Curso</th><th>Qué prepara</th><th>Elige el profesorado</th><th>Aporta el alumnado</th><th>Nuevo reto</th><th>Áreas</th><th>Duración</th></tr></thead>
                        <tbody>
                            @foreach ($c['itinerario'] as $i => $r)
                                <tr>
                                    <td class="b">{{ $r['curso'] }}.º</td>
                                    <td><x-proyecto.campo :editable="$e" :ruta="'itinerario.'.$i.'.formato'" :valor="$r['formato']" /></td>
                                    <td><x-proyecto.campo :editable="$e" :ruta="'itinerario.'.$i.'.elige_profesorado'" :valor="$r['elige_profesorado']" /></td>
                                    <td><x-proyecto.campo :editable="$e" :ruta="'itinerario.'.$i.'.aporta_alumnado'" :valor="$r['aporta_alumnado']" /></td>
                                    <td><x-proyecto.campo :editable="$e" :ruta="'itinerario.'.$i.'.reto'" :valor="$r['reto']" /></td>
                                    <td>{{ collect($r['areas'])->map(fn ($a) => $nombreArea[$a] ?? $a)->implode(', ') }}</td>
                                    <td><x-proyecto.campo :editable="$e" :ruta="'itinerario.'.$i.'.duracion'" :valor="$r['duracion']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="s c12">
            <h3>Anexo: criterios de evaluación (texto del decreto)</h3>
            <div class="crit">
                @foreach ($criterios as $cr)
                    <span class="cod">{{ $cr->area->codigo }} {{ $cr->codigo }}</span><span>{{ $cr->texto }}</span>
                @endforeach
            </div>
        </section>
    </div>
</article>
