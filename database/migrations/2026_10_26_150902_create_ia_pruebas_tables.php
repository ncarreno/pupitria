<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batería de pruebas comparativa: casos reales (sin datos de alumnos), una ejecución por
 * proveedor y caso, y la valoración de cada persona que valora (Nacho y la beta tester).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ia_casos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tarea', 20);                         // proyecto | ficha
            $table->json('entrada');                             // lo que necesita el generador: curso, áreas, idea o tema…
            $table->timestamps();
        });

        Schema::create('ia_ejecuciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ia_caso_id')->constrained('ia_casos')->cascadeOnDelete();
            $table->string('proveedor', 40);
            $table->string('modelo')->nullable();                // null = modelo por defecto del proveedor
            $table->string('estado', 20)->default('pendiente');  // pendiente | completada | fallida
            $table->json('resultado')->nullable();               // contenido ya limpio, como lo guardaría la app
            $table->text('error')->nullable();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete(); // duración, tokens e ids descartados
            $table->timestamps();
            $table->index(['ia_caso_id', 'proveedor', 'modelo']);
        });

        Schema::create('ia_valoraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ia_ejecucion_id')->constrained('ia_ejecuciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('puntuacion');           // 1..5
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->unique(['ia_ejecucion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ia_valoraciones');
        Schema::dropIfExists('ia_ejecuciones');
        Schema::dropIfExists('ia_casos');
    }
};
