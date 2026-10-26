<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proyectos (situaciones de aprendizaje) generados con IA. El documento completo se
 * guarda como JSON en `contenido` con la estructura de la plantilla "Diseño de un proyecto".
 * Los criterios y saberes también van en tablas intermedias para poder filtrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clase_id')->nullable()->constrained('clases')->nullOnDelete();
            $table->string('tipo', 10)->default('aula');        // aula | centro
            $table->unsignedTinyInteger('curso');               // copia del curso de la clase al crear
            $table->json('areas');                              // códigos de área del proyecto
            $table->string('titulo')->default('');
            $table->text('idea');
            $table->string('duracion', 40);
            $table->json('contenido')->nullable();
            $table->string('estado', 20)->default('generando'); // generando | listo | error
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('ids_descartados')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('proyecto_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->json('contenido');
            $table->text('instruccion')->nullable();            // null = generación inicial
            $table->string('proveedor', 40)->nullable();
            $table->string('modelo')->nullable();
            $table->timestamps();
            $table->unique(['proyecto_id', 'numero']);
        });

        Schema::create('criterio_proyecto', function (Blueprint $table) {
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->string('criterio_id', 30);
            $table->foreign('criterio_id')->references('id')->on('criterios')->cascadeOnDelete();
            $table->primary(['proyecto_id', 'criterio_id']);
        });

        Schema::create('proyecto_saber', function (Blueprint $table) {
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->string('saber_id', 40);
            $table->foreign('saber_id')->references('id')->on('saberes')->cascadeOnDelete();
            $table->primary(['proyecto_id', 'saber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_saber');
        Schema::dropIfExists('criterio_proyecto');
        Schema::dropIfExists('proyecto_versiones');
        Schema::dropIfExists('proyectos');
    }
};
