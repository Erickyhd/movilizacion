<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            if (!Schema::hasColumn('rutas', 'departamento')) {
                $table->string('departamento', 100)->nullable()->after('origen');
            }
            if (!Schema::hasColumn('rutas', 'distancia_km')) {
                $table->integer('distancia_km')->nullable()->after('duracion_estimada_minutos');
            }
            if (!Schema::hasColumn('rutas', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('distancia_km');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn(['departamento', 'distancia_km', 'observaciones']);
        });
    }
};