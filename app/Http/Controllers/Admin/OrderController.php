<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user', 'items.obat');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $op = $this->likeOperator();
            $query->where(function ($q) use ($search, $op) {
                $q->where('no_order', $op, '%' . $search . '%')
                  ->orWhereHas('user', function ($q2) use ($search, $op) {
                      $q2->where('name', $op, '%' . $search . '%');
                  });
            });
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('admin.order.index', compact('orders'));
    }

    public function create()
    {
        $obats = \App\Models\Obat::where('is_active', true)->where('is_approved', true)->orderBy('nama_obat')->get();
        return view('admin.order.create', compact('obats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|array',
            'obat_id.*' => 'exists:obat,id',
            'jumlah' => 'required|array',
            'jumlah.*' => 'integer|min:1',
            'metode_pembayaran' => 'required|string',
        ]);

        $total = 0;
        $items = [];
        foreach ($request->obat_id as $index => $obat_id) {
            $obat = \App\Models\Obat::find($obat_id);
            $jumlah = $request->jumlah[$index];
            if ($obat->stok < $jumlah) {
                return back()->with('error', "Stok {$obat->nama_obat} tidak mencukupi. Sisa: {$obat->stok}");
            }
            $subtotal = $obat->harga_umum * $jumlah;
            $total += $subtotal;
            $items[] = [
                'obat_id' => $obat_id,
                'jumlah' => $jumlah,
                'harga_satuan' => $obat->harga_umum,
                'subtotal' => $subtotal,
            ];
            $obat->decrement('stok', $jumlah);
        }

        $order = Order::create([
            'no_order' => Order::generateNoOrder(),
            'user_id' => auth()->id(),
            'status' => 'selesai',
            'total_harga' => $total,
            'metode_pembayaran' => $request->metode_pembayaran,
            'catatan' => 'Penjualan Manual',
        ]);

        $order->items()->createMany($items);

        return redirect()->route('admin.orders.index')->with('success', 'Catatan penjualan berhasil ditambahkan.');
    }

    public function show(Request $request, Order $order)
    {
        $order->load('user', 'items.obat.kategori');

        if ($request->boolean('modal') || $request->ajax()) {
            return view('admin.order._detail_content', [
                'order' => $order,
                'isModal' => true,
            ]);
        }

        return view('admin.order.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,dikonfirmasi,diproses,dikirim,selesai,dibatalkan',
        ]);

        $order->update(['status' => $request->status]);

        // If cancelled, restore stock
        if ($request->status === 'dibatalkan') {
            foreach ($order->items as $item) {
                $item->obat->increment('stok', $item->jumlah);
            }
        }

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
