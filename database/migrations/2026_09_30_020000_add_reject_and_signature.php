<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tanda tangan approver. Disimpan sekali per user lalu dipakai otomatis
        // di setiap PDF persetujuan.
        Schema::create('user_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('image_path');            // path di disk public
            $table->string('nama');                 // nama yang dicetak
            $table->string('jabatan')->nullable();
            $table->timestamps();
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->text('alasan_tolak')->nullable();
            $table->timestamp('ditolak_at')->nullable();
            $table->foreignId('ditolak_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['alasan_tolak', 'ditolak_at', 'ditolak_by']);
        });

        Schema::dropIfExists('user_signatures');
    }
};
