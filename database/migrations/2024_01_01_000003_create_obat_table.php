<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 8)->unique();
            $table->string('nama_obat', 100);
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('cascade');
            $table->smallInteger('stok')->default(0);
            $table->string('satuan', 20)->nullable();
            $table->integer('harga_umum')->default(0);
            $table->decimal('harga_barang', 15, 2)->default(0);
            $table->integer('harga_jual_bpjs')->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obat');
    }
};
