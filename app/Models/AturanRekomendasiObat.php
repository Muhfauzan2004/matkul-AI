<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AturanRekomendasiObat extends Model
{
    use HasFactory;

    protected $table = 'aturan_rekomendasi_obat';

    protected $fillable = [
        'obat_id',
        'indikasi',
        'gejala',
        'kontraindikasi',
        'peringatan',
        'sumber_referensi',
        'is_verified',
        'verified_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
