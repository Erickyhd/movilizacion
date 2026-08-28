<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'estado')) {
                $table->integer('estado')->default(1)->after('es_contratista');
            }
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            if (!Schema::hasColumn('trabajadores', 'estado')) {
                $table->integer('estado')->default(1)->after('estado_acreditacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'estado')) {
                $table->dropColumn('estado');
            }
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            if (Schema::hasColumn('trabajadores', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};