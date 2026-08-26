<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifiestos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_manifiesto', 20)->unique();
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->foreignId('conductor_id')->nullable()->constrained('conductores')->nullOnDelete();
            $table->dateTime('fecha_salida_programada');
            $table->dateTime('fecha_salida_real')->nullable();
            $table->dateTime('fecha_llegada_real')->nullable();
            $table->enum('estado', ['BORRADOR', 'CONFIRMADO', 'EN_GARITA', 'EN_RUTA', 'FINALIZADO', 'CANCELADO'])->default('BORRADOR');
            $table->string('codigo_qr_token', 255)->nullable()->unique();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifiestos');
    }
};