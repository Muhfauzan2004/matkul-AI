<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'obat';

    protected $fillable = [
        'kode',
        'nama_obat',
        'kategori_id',
        'stok',
        'satuan',
        'harga_umum',
        'harga_barang',
        'harga_jual_bpjs',
        'deskripsi',
        'gambar',
        'is_active',
        'is_approved',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateKodeObat(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        $number = $last ? (int)substr($last->kode, 3) + 1 : 1;
        return 'OBT' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

        public function aturanRekomendasi()
    {
        return $this->hasMany(AturanRekomendasiObat::class);
    }
    
}
