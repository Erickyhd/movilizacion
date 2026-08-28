<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rol')) {
                $table->string('rol', 30)->default('ADMIN')->after('email');
            }
            if (!Schema::hasColumn('users', 'permisos')) {
                $table->text('permisos')->nullable()->after('rol');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'rol')) {
                $table->dropColumn(['rol', 'permisos']);
            }
        });
    }
};