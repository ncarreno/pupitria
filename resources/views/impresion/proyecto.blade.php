<x-impresion :titulo="$proyecto->titulo" :volver="route('proyectos.ver', $proyecto)">
    <x-proyecto.documento :proyecto="$proyecto" />
</x-impresion>
