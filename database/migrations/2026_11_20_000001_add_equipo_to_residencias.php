<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Equipo sanitario y de apoyo (enfermería, medicina…): lo ve la auxiliar en «Mi turno». */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residencias', function (Blueprint $table) {
            $table->json('equipo')->nullable()->after('fotos');
        });
    }

    public function down(): void
    {
        Schema::table('residencias', function (Blueprint $table) {
            $table->dropColumn('equipo');
        });
    }
};
