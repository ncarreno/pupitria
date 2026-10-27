<x-impresion :titulo="$ficha->titulo" :volver="route('fichas.ver', $ficha)">
    @foreach ($niveles as $nivel)
        <x-ficha.hoja :ficha="$ficha" :nivel="$nivel" />
    @endforeach
</x-impresion>
