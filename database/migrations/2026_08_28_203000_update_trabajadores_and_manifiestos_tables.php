<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            if (!Schema::hasColumn('trabajadores', 'apellido_paterno')) {
                $table->string('apellido_paterno', 100)->nullable()->after('nombres');
            }
            if (!Schema::hasColumn('trabajadores', 'apellido_materno')) {
                $table->string('apellido_materno', 100)->nullable()->after('apellido_paterno');
            }
            if (!Schema::hasColumn('trabajadores', 'area')) {
                $table->string('area', 100)->nullable()->after('cargo');
            }
        });

        // Migrate existing single 'apellidos' into 'apellido_paterno'
        DB::statement("UPDATE trabajadores SET apellido_paterno = apellidos WHERE apellido_paterno IS NULL AND apellidos IS NOT NULL");

        Schema::table('manifiestos', function (Blueprint $table) {
            if (!Schema::hasColumn('manifiestos', 'copiloto_id')) {
                $table->foreignId('copiloto_id')->nullable()->after('conductor_id')->constrained('conductores')->nullOnDelete();
            }
            if (!Schema::hasColumn('manifiestos', 'tipo_movilizacion')) {
                $table->string('tipo_movilizacion', 30)->default('INGRESO')->after('estado');
            }
        });

        Schema::table('manifiesto_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('manifiesto_detalles', 'area')) {
                $table->string('area', 100)->nullable();
            }
            if (!Schema::hasColumn('manifiesto_detalles', 'embarque')) {
                $table->string('embarque', 100)->nullable();
            }
            if (!Schema::hasColumn('manifiesto_detalles', 'campamento')) {
                $table->string('campamento', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno', 'area']);
        });

        Schema::table('manifiestos', function (Blueprint $table) {
            $table->dropForeign(['copiloto_id']);
            $table->dropColumn(['copiloto_id', 'tipo_movilizacion']);
        });

        Schema::table('manifiesto_detalles', function (Blueprint $table) {
            $table->dropColumn(['area', 'embarque', 'campamento']);
        });
    }
};