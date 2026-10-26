<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Currículo oficial (solo lectura). Los ids de criterios y saberes son los
 * identificadores estables del JSON extraído del decreto (p. ej. MAT.C2.1.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normativas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();          // asturias-primaria-d57-2022
            $table->string('comunidad');                 // Principado de Asturias
            $table->string('etapa');                     // Educación Primaria
            $table->string('referencia');                // Decreto 57/2022, de 5 de agosto
            $table->string('fuente')->nullable();        // BOPA núm. 156, 12-VIII-2022
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('normativa_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 10);                // LCL, MAT, CN…
            $table->string('nombre');
            $table->string('nombre_corto');
            $table->json('cursos');                      // [1..6] o [6] para EVCE
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['normativa_id', 'codigo']);
        });

        Schema::create('competencias_especificas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 20)->unique();      // MAT.CE1
            $table->unsignedTinyInteger('numero');
            $table->text('enunciado');
            $table->timestamps();
        });

        Schema::create('criterios', function (Blueprint $table) {
            $table->string('id', 30)->primary();         // MAT.C2.1.1
            $table->foreignId('competencia_especifica_id')->constrained('competencias_especificas')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('ciclo');
            $table->string('codigo', 10);                // 1.1
            $table->text('texto');
            $table->timestamps();
            $table->index(['area_id', 'ciclo']);
        });

        Schema::create('saberes', function (Blueprint $table) {
            $table->string('id', 40)->primary();         // CN.C2.A.2.4
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('ciclo');
            $table->string('bloque');                    // A. Cultura científica
            $table->string('apartado')->nullable();
            $table->text('texto');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['area_id', 'ciclo']);
        });

        Schema::create('descriptores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('normativa_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 10);                // CCL1
            $table->string('competencia_clave', 10);     // CCL
            $table->string('competencia_clave_nombre');
            $table->text('texto');
            $table->timestamps();
            $table->unique(['normativa_id', 'codigo']);
        });

        Schema::create('competencia_descriptor', function (Blueprint $table) {
            $table->foreignId('competencia_especifica_id')->constrained('competencias_especificas')->cascadeOnDelete();
            $table->foreignId('descriptor_id')->constrained('descriptores')->cascadeOnDelete();
            $table->primary(['competencia_especifica_id', 'descriptor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencia_descriptor');
        Schema::dropIfExists('descriptores');
        Schema::dropIfExists('saberes');
        Schema::dropIfExists('criterios');
        Schema::dropIfExists('competencias_especificas');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('normativas');
    }
};
