<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cómo se consigue la ilustración de una ficha: la crea la app con OpenAI o la docente
 * copia un prompt en ChatGPT o Gemini y sube la imagen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichas', function (Blueprint $table) {
            $table->string('imagen_por', 10)->default('openai')->after('con_imagen'); // openai | prompt
            $table->text('prompt_imagen')->nullable()->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('fichas', function (Blueprint $table) {
            $table->dropColumn(['imagen_por', 'prompt_imagen']);
        });
    }
};
