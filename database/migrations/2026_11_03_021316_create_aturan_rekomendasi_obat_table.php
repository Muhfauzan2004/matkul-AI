<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_rekomendasi_obat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obat_id')
                ->constrained('obat')
                ->cascadeOnDelete();

            $table->string('indikasi', 150);
            $table->text('gejala')->nullable();
            $table->text('kontraindikasi')->nullable();
            $table->text('peringatan')->nullable();

            $table->string('sumber_referensi')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_rekomendasi_obat');
    }
};
