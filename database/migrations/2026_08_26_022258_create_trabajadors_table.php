<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('dni', 15)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('cargo', 100)->nullable();
            $table->string('grupo_sanguineo', 5)->nullable();
            $table->string('telefono_emergencia', 20)->nullable();
            $table->enum('estado_acreditacion', ['APTO', 'OBSERVADO', 'BLOQUEADO'])->default('APTO');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};