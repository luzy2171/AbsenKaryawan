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
            $table->boolean('is_default')->default(false);
        });

        // Set mesin pertama sebagai default
        DB::table('machine_status')->where('id', 1)->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_status', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
