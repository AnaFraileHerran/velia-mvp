<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Avisos asignados antes de existir el plazo: toman 2 días desde la asignación, para que vuelvan a «Hoy» si vencen. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('avisos')->where('estado', 'asignado')->whereNull('vence_el')->get() as $a) {
            DB::table('avisos')->where('id', $a->id)->update(['vence_el' => \Illuminate\Support\Carbon::parse($a->updated_at)->addDays(2)->toDateString()]);
        }
    }

    public function down(): void {}
};
