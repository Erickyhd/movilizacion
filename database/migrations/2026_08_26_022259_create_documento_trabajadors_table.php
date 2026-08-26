<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->string('tipo_documento', 50);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->string('url_archivo', 255)->nullable();
            $table->boolean('es_vigente')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_trabajador');
    }
};