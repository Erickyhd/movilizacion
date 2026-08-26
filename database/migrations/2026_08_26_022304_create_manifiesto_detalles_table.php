<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifiesto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifiesto_id')->constrained('manifiestos')->cascadeOnDelete();
            $table->foreignId('trabajador_id')->nullable()->constrained('trabajadores')->nullOnDelete();
            $table->integer('numero_asiento')->nullable();
            $table->enum('estado_embarque', ['PENDIENTE', 'ABORDADO', 'NO_PRESENTO'])->default('PENDIENTE');
            $table->string('observacion', 255)->nullable();
            $table->dateTime('timestamp_checkin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifiesto_detalles');
    }
};