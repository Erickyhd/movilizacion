<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conductores', function (Blueprint $table) {
            if (!Schema::hasColumn('conductores', 'dni')) {
                $table->string('dni', 15)->nullable()->after('trabajador_id');
            }
            if (!Schema::hasColumn('conductores', 'nombres')) {
                $table->string('nombres', 100)->nullable()->after('dni');
            }
            if (!Schema::hasColumn('conductores', 'apellido_paterno')) {
                $table->string('apellido_paterno', 100)->nullable()->after('nombres');
            }
            if (!Schema::hasColumn('conductores', 'apellido_materno')) {
                $table->string('apellido_materno', 100)->nullable()->after('apellido_paterno');
            }
            if (!Schema::hasColumn('conductores', 'fecha_nacimiento')) {
                $table->date('fecha_nacimiento')->nullable()->after('apellido_materno');
            }
            if (!Schema::hasColumn('conductores', 'rol_conductor')) {
                $table->string('rol_conductor', 20)->default('CONDUCTOR')->after('categoria_licencia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conductores', function (Blueprint $table) {
            $table->dropColumn(['dni', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'rol_conductor']);
        });
    }
};