<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_order',
        'user_id',
        'status',
        'total_harga',
        'alamat_pengiriman',
        'metode_pembayaran',
        'catatan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateNoOrder(): string
    {
        $prefix = 'ORD-' . date('Ymd');
        $last = self::where('no_order', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $number = $last ? (int)substr($last->no_order, -4) + 1 : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
