<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fichas de trabajo en tres niveles (refuerzo, estándar y ampliación) sobre el mismo contenido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clase_id')->nullable()->constrained('clases')->nullOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('tarea_indice')->nullable();
            $table->string('area', 10);                          // código de área
            $table->unsignedTinyInteger('curso');
            $table->text('tema');                                // lo que pidió la docente
            $table->string('titulo')->default('');
            $table->json('contenido')->nullable();               // {consigna, bloques} por nivel
            $table->boolean('con_imagen')->default(false);
            $table->string('imagen')->nullable();                // ruta en el disco public
            $table->string('estado', 20)->default('generando');  // generando | listo | error
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('ids_descartados')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('criterio_ficha', function (Blueprint $table) {
            $table->foreignId('ficha_id')->constrained('fichas')->cascadeOnDelete();
            $table->string('criterio_id', 30);
            $table->foreign('criterio_id')->references('id')->on('criterios')->cascadeOnDelete();
            $table->primary(['ficha_id', 'criterio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterio_ficha');
        Schema::dropIfExists('fichas');
    }
};
