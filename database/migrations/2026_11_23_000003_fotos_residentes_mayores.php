<?php

use App\Support\FotosDemo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Demo: los residentes pasan a tener fotos de personas mayores (antes, retratos de stock de cualquier edad). No toca las fotos subidas. */
return new class extends Migration
{
    public function up(): void
    {
        $mujeres = 0;
        $hombres = 0;
        foreach (DB::table('residentes')->where(fn ($q) => $q->whereNull('foto')->orWhere('foto', 'like', 'https://randomuser.me/%'))->orderBy('id')->get() as $r) {
            $mujer = FotosDemo::esMujer($r->nombre);
            $url = FotosDemo::residente($r->nombre, $mujer ? $mujeres++ : $hombres++);
            DB::table('residentes')->where('id', $r->id)->update(['foto' => $url]);
        }
    }

    public function down(): void
    {
        DB::table('residentes')->where('foto', 'like', 'https://images.unsplash.com/%')->update(['foto' => null]);
    }
};
