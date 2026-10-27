<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estilo visual de cada proyecto y cada ficha (ver `App\View\EstilosDocumento`).
 * Null = el que la docente tenga en su perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['proyectos', 'fichas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('estilo', 20)->nullable()->after('contenido');
            });
        }
    }

    public function down(): void
    {
        foreach (['proyectos', 'fichas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('estilo');
            });
        }
    }
};
