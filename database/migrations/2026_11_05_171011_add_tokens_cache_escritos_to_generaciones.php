<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tokens que se escribieron en la caché de prompts: Anthropic los cobra más caros que la entrada normal,
     * así que hacen falta para calcular el coste real. `tokens_cache` son los leídos.
     */
    public function up(): void
    {
        Schema::table('generaciones', function (Blueprint $table) {
            $table->unsignedInteger('tokens_cache_escritos')->nullable()->after('tokens_cache');
        });
    }

    public function down(): void
    {
        Schema::table('generaciones', function (Blueprint $table) {
            $table->dropColumn('tokens_cache_escritos');
        });
    }
};
