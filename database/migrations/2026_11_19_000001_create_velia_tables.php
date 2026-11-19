<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('lema')->default('Cada día, contado con calma.');
            $table->string('responsable')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('cif')->nullable();
            $table->unsignedSmallInteger('plazas_autorizadas')->nullable();
            $table->date('proxima_auditoria')->nullable();
            $table->date('piloto_hasta')->nullable();
            $table->json('fotos')->nullable(); // hasta 5, pantalla de acceso
            $table->timestamps();
        });

        Schema::create('plantas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('rol')->default('auxiliar'); // direccion | auxiliar
            $table->string('pin')->nullable();          // hash del PIN
            $table->boolean('pin_provisional')->default(false);
            $table->string('codigo_invitacion', 6)->nullable(); // para vincular un móvil personal
            $table->foreignId('planta_id')->nullable()->constrained('plantas')->nullOnDelete();
            $table->string('turno_habitual')->nullable(); // manana | tarde | noche
            $table->boolean('responsable_turno')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('baja_motivo')->nullable();
            $table->timestamp('pin_restablecido_at')->nullable();
            $table->foreignId('pin_restablecido_por')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('residentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('dni')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->foreignId('planta_id')->nullable()->constrained('plantas')->nullOnDelete();
            $table->string('habitacion')->nullable();
            $table->string('tipo_habitacion')->default('individual'); // individual | doble
            $table->string('estado')->default('en_centro'); // en_centro | fuera | baja
            $table->string('baja_motivo')->nullable();
            $table->date('baja_fecha')->nullable();
            // Ficha de cuidados (la auxiliar la ve en solo lectura en «Cómo atender»)
            $table->string('alergias')->nullable();
            $table->string('riesgos')->nullable();
            $table->string('movilidad')->nullable();
            $table->boolean('sujecion')->default(false);
            $table->string('dieta')->nullable();
            $table->text('como_tratarle')->nullable();
            $table->text('notas')->nullable();
            $table->json('informes')->nullable(); // PDFs adjuntos (Informes en la ficha)
            // Plan personal (Orden 2680/2024: primer mes y revisión cada 6 meses)
            $table->date('plan_ultima_revision')->nullable();
            $table->date('plan_proxima_revision')->nullable();
            $table->foreignId('plan_responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('relacion');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->boolean('emergencia')->default(false);
            $table->boolean('recibe_resumen')->default(true);
            $table->string('canal')->default('email'); // email | whatsapp
            $table->timestamps();
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('planta_id')->nullable()->constrained('plantas')->nullOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->string('motivo_cierre')->nullable();
            $table->string('motivo_texto')->nullable();
            $table->text('nota_relevo')->nullable();
            $table->timestamps();
        });

        Schema::create('registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained()->nullOnDelete();
            $table->text('texto_original')->nullable();
            $table->json('categorias')->nullable(); // {"Movilidad": "…", …}
            $table->string('estado')->default('borrador'); // borrador | confirmado | sin_novedad
            $table->boolean('incidencia_destacada')->default(false);
            $table->string('tipo_incidencia')->nullable();
            $table->timestamps();
        });

        Schema::create('correcciones', function (Blueprint $table) {
            $table->id();
            $table->morphs('corregible');
            $table->string('campo');
            $table->text('antes')->nullable();
            $table->text('ahora')->nullable();
            $table->string('motivo');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ausencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->string('tipo'); // familia | hospital | vacaciones | otro
            $table->date('sale');
            $table->date('vuelve')->nullable();
            $table->boolean('ha_vuelto')->default(false);
            $table->foreignId('registrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // revision_vencida | patron | incidencia | turno_con_motivo
            $table->string('titulo');
            $table->foreignId('residente_id')->nullable()->constrained()->nullOnDelete();
            $table->text('detalle')->nullable();
            $table->unsignedTinyInteger('urgencia')->default(2); // 1 alta … 3 baja
            $table->string('estado')->default('pendiente'); // pendiente | asignado | pregunta | revisado | cerrado
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('aviso_id')->nullable()->constrained('avisos')->nullOnDelete();
            $table->text('texto');
            $table->text('respuesta')->nullable();
            $table->foreignId('respondida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('resumenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->date('semana_inicio');
            $table->text('texto');
            $table->boolean('tiene_incidencias')->default(false);
            $table->string('estado')->default('por_revisar'); // por_revisar | aprobado | enviado
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['resumenes', 'preguntas', 'avisos', 'ausencias', 'correcciones', 'registros', 'turnos', 'contactos', 'residentes', 'plantas', 'residencias'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
