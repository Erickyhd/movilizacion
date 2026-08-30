<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('ruc', 20)->nullable()->change();
            if (!Schema::hasColumn('empresas', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('razon_social');
            }
        });

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('ruc', 11)->unique()->change();
            $table->dropColumn(['observaciones']);
        });

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->change();
        });
    }
};