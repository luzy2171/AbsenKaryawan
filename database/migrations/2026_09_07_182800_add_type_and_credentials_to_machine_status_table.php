<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('machine_status', function (Blueprint $table) {
            $table->string('machine_type')->default('hikvision')->after('machine_name');
            $table->integer('port')->default(80)->after('machine_type');
            $table->string('username')->nullable()->after('port');
            $table->string('password')->nullable()->after('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_status', function (Blueprint $table) {
            $table->dropColumn(['machine_type', 'port', 'username', 'password']);
        });
    }
};
