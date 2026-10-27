<?php

namespace App\View;

use App\Models\User;

/**
 * Estilos visuales de las fichas y los proyectos. Solo cambian el aspecto, con la clase `t-{estilo}`
 * en `x-ficha.hoja` y `x-proyecto.documento` (`resources/css/documentos.css`); el contenido es el mismo.
 */
class EstilosDocumento
{
    public const POR_DEFECTO = 'cuaderno';

    /**
     * Las fuentes van en el formato de Bunny Fonts (`familia:pesos|familia:pesos`).
     *
     * @var array<string, array{nombre: string, descripcion: string, fuentes: string}>
     */
    public const ESTILOS = [
        'cuaderno' => [
            'nombre' => 'Cuaderno',
            'descripcion' => 'Cuadrícula de cuaderno, letra de rotulador en los títulos y el azul y el rojo del bolígrafo.',
            'fuentes' => 'kalam:400,700|atkinson-hyperlegible:400,400i,700',
        ],
        'caligrafia' => [
            'nombre' => 'Caligrafía',
            'descripcion' => 'Pauta de cuadernillo para escribir las respuestas, títulos en letra escolar y actividades numeradas.',
            'fuentes' => 'playwrite-es:400|andika:400,700',
        ],
        'cartulina' => [
            'nombre' => 'Cartulina',
            'descripcion' => 'Piezas recortadas con borde grueso, sombra de color y números grandes. La más alegre.',
            'fuentes' => 'baloo-2:500,700,800|nunito:500,700,800',
        ],
        'esencial' => [
            'nombre' => 'Esencial',
            'descripcion' => 'Solo líneas finas y números huecos. Gasta muy poca tinta y se ve igual en blanco y negro.',
            'fuentes' => 'archivo:400,600,700,800|ibm-plex-mono:400,600',
        ],
    ];

    /**
     * El estilo del documento; si no tiene, el del perfil de la docente; si no, el de por defecto.
     */
    public static function resolver(?string $delDocumento, ?User $docente): string
    {
        foreach ([$delDocumento, data_get($docente?->preferencias, 'estilo')] as $estilo) {
            if (self::existe($estilo)) {
                return $estilo;
            }
        }

        return self::POR_DEFECTO;
    }

    public static function existe(mixed $estilo): bool
    {
        return is_string($estilo) && array_key_exists($estilo, self::ESTILOS);
    }

    public static function nombre(string $estilo): string
    {
        return self::ESTILOS[$estilo]['nombre'] ?? $estilo;
    }

    public static function urlFuentes(string $estilo): string
    {
        return 'https://fonts.bunny.net/css?family='.(self::ESTILOS[$estilo] ?? self::ESTILOS[self::POR_DEFECTO])['fuentes'].'&display=swap';
    }
}
