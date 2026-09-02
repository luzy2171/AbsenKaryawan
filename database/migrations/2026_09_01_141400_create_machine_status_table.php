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
        Schema::create('machine_status', function (Blueprint $table) {
            $table->id();
            $table->string('machine_ip');
            $table->string('machine_name')->default('Solution C100X');
            $table->string('status')->default('offline'); // online, offline
            $table->timestamp('last_ping')->nullable();
            $table->integer('response_time')->nullable(); // dalam milliseconds
            $table->integer('total_users')->default(0);
            $table->integer('total_logs')->default(0);
            $table->timestamps();

            $table->index('machine_ip');
        });

        // Insert default machine
        DB::table('machine_status')->insert([
            'machine_ip' => '10.10.10.237',
            'machine_name' => 'Solution C100X',
            'status' => 'offline',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_status');
    }
};
