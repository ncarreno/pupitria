<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Páginas con solo el documento en A4, que abren el diálogo de imprimir del navegador
 * (desde ahí la docente también lo guarda como PDF). La autorización va en la ruta (`can:view`).
 */
class ImpresionController extends Controller
{
    public function proyecto(Proyecto $proyecto): View|RedirectResponse
    {
        if (! $proyecto->contenido || $proyecto->estaGenerando()) {
            return redirect()->route('proyectos.ver', $proyecto);
        }

        return view('impresion.proyecto', ['proyecto' => $proyecto]);
    }

    /**
     * `?nivel=` refuerzo, estandar o ampliacion; `todos` imprime los tres, cada uno en su hoja.
     */
    public function ficha(Request $request, Ficha $ficha): View|RedirectResponse
    {
        if (! $ficha->contenido || $ficha->estaGenerando()) {
            return redirect()->route('fichas.ver', $ficha);
        }

        $nivel = $request->query('nivel');
        $niveles = match (true) {
            $nivel === 'todos' => array_keys(Ficha::NIVELES),
            is_string($nivel) && array_key_exists($nivel, Ficha::NIVELES) => [$nivel],
            default => ['estandar'],
        };

        return view('impresion.ficha', ['ficha' => $ficha->load('criterios'), 'niveles' => $niveles]);
    }
}
