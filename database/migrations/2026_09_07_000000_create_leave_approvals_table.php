<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
        
        // Let's also insert a default setting for required_approvals
        DB::table('settings')->insertOrIgnore([
            'key' => 'required_approvals',
            'value' => '1',
            'description' => 'Jumlah minimal persetujuan untuk pengajuan izin/sakit',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
