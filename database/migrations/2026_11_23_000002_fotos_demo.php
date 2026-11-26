<?php

use App\Support\FotosDemo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Demo: foto ficticia a quien no tiene (equipo y residentes). No toca las fotos que haya subido la dirección. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('users')->whereNull('foto')->whereIn('rol', ['auxiliar', 'sanitario', 'direccion'])->get() as $u) {
            DB::table('users')->where('id', $u->id)->update(['foto' => FotosDemo::para($u->name, $u->id)]);
        }
        // Residentes: ver 2026_11_23_000003 (fotos de personas mayores).
    }

    public function down(): void
    {
        DB::table('users')->where('foto', 'like', 'https://randomuser.me/%')->update(['foto' => null]);
        DB::table('residentes')->where('foto', 'like', 'https://randomuser.me/%')->update(['foto' => null]);
    }
};
