<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Qué proveedor y modelo usa cada tarea. Solo lo edita el administrador.
        Schema::create('ia_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('tarea', 40);                 // proyecto, ajuste, ficha, prueba
            $table->string('proveedor', 40);             // anthropic, openai, gemini…
            $table->string('modelo')->nullable();        // null = modelo por defecto del proveedor
            $table->unsignedTinyInteger('orden')->default(1); // 1 = principal, 2+ = respaldo
            $table->unsignedSmallInteger('timeout')->default(180);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['tarea', 'proveedor']);
            $table->index(['tarea', 'activo', 'orden']);
        });

        // Registro de cada llamada a la IA. No se guarda el texto de la petición.
        Schema::create('generaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tarea', 40);
            $table->string('estado', 20)->default('pendiente'); // pendiente, completada, fallida
            $table->string('proveedor', 40)->nullable();
            $table->string('modelo')->nullable();
            $table->boolean('respaldo')->default(false);        // true si respondió un proveedor que no era el principal
            $table->unsignedInteger('tokens_entrada')->nullable();
            $table->unsignedInteger('tokens_salida')->nullable();
            $table->unsignedInteger('tokens_cache')->nullable();
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->unsignedSmallInteger('ids_descartados')->default(0); // criterios/saberes inventados que se descartaron
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['tarea', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generaciones');
        Schema::dropIfExists('ia_configuraciones');
    }
};
