<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Al hacer obligatoria la verificación del email, las cuentas que ya existían (Nacho y la
 * beta tester) se dan por verificadas para que no se queden fuera. Las nuevas verifican con el enlace.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    /**
     * No se puede saber qué cuentas estaban sin verificar, así que no se deshace.
     */
    public function down(): void
    {
        //
    }
};
