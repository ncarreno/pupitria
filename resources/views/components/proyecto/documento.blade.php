@props(['proyecto', 'editable' => false])
@php
    use App\Models\Area;
    use App\Models\Criterio;
    use App\Models\Saber;
    use App\Services\Curriculo\CatalogoCurriculo;

    $estilo = $proyecto->estiloVisual();
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

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="{{ \App\View\EstilosDocumento::urlFuentes($estilo) }}" rel="stylesheet" />

<article {{ $attributes->merge(['class' => "doc-proyecto t-{$estilo}"]) }}>
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
