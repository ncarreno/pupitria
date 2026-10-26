<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clases de cada docente. Sin datos personales de alumnos: solo cuántos son y
 * cuántos necesitan refuerzo o ampliación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 60);                       // 3.º A
            $table->unsignedTinyInteger('curso');               // 1..6
            $table->unsignedSmallInteger('alumnos');
            $table->unsignedSmallInteger('alumnos_refuerzo')->default(0);
            $table->unsignedSmallInteger('alumnos_ampliacion')->default(0);
            $table->string('curso_escolar', 9);                 // 2026/2027
            $table->timestamps();
            $table->index(['user_id', 'curso_escolar']);
        });

        Schema::create('area_clase', function (Blueprint $table) {
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->primary(['clase_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_clase');
        Schema::dropIfExists('clases');
    }
};
