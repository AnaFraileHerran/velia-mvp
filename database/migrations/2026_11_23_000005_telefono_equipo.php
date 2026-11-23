<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Contacto de trabajo de las auxiliares (extensión o móvil de empresa; nunca el personal sin consentimiento). Demo: extensiones ficticias. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('contacto');
        });
        foreach (DB::table('users')->where('rol', 'auxiliar')->orderBy('id')->get() as $i => $u) {
            DB::table('users')->where('id', $u->id)->update(['telefono' => 'Ext. '.(120 + $i)]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};
