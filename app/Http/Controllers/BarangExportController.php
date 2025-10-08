<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangExport;
use App\Models\BarangExportItem;
use App\Models\Kota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set('Asia/Jakarta');

class BarangExportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:barangs,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $barangs = Barang::whereIn('id', $validated['ids'])->get();
        if ($barangs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data barang tidak ditemukan.'], 404);
        }
        $kotaIds = $barangs->pluck('kota_tujuan')->unique()->filter()->values();
        $kotaMap = Kota::whereIn('id', $kotaIds)->pluck('nama', 'id');
        $total = 0;
        $belum = 0;
        $tf = 0;
        $lunas = 0;
        foreach ($barangs as $b) {
            $val = (float) ($b->harga_terbayar ?? $b->harga_awal ?? 0);
            $total += $val;
            $sb = strtolower($b->status_bayar ?? '');
            if ($sb === 'belum bayar') $belum += $val;
            elseif ($sb === 'transfer') $tf += $val;
            elseif ($sb === 'lunas') $lunas += $val;
        }

        $prefix = 'EXP-' . date('ymd') . '-';
        $last = BarangExport::where('kode', 'like', $prefix . '%')->orderBy('kode', 'desc')->value('kode');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next  = intval(end($parts)) + 1;
        }
        $kode = $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $export = BarangExport::create([
                'kode' => $kode,
                'user_id' => $user->id,
                'exported_at' => now(),
                'jumlah_item' => $barangs->count(),
                'total_ongkir' => $total,
                'total_lunas' => $lunas,
                'total_belum_bayar' => $belum,
                'total_transfer' => $tf,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            $items = [];
            foreach ($barangs as $b) {
                $tujuanText = $b->paket_antar ? ($b->alamat ?? null) : ($kotaMap[$b->kota_tujuan] ?? null);
                $items[] = [
                    'export_id' => $export->id,
                    'barang_id' => $b->id,
                    'kode_barang' => $b->kode_barang,
                    'nama_penerima' => $b->nama_penerima,
                    'paket_antar' => (bool) $b->paket_antar,
                    'tujuan_text' => $tujuanText,
                    'deskripsi_barang' => $b->deskripsi_barang,
                    'ongkos_kirim' => (float) ($b->harga_terbayar ?? $b->harga_awal ?? 0),
                    'status_bayar' => $b->status_bayar ?? 'Belum Bayar',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            BarangExportItem::insert($items);

            Barang::whereIn('id', $validated['ids'])->update([
                'exported_supir' => true,
                'exported_at_supir' => now(),
                'last_export_id' => $export->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch ekspor tersimpan.',
                'data' => [
                    'export_id' => $export->id,
                    'kode' => $export->kode,
                    'jumlah_item' => $export->jumlah_item,
                    'total' => $total,
                    'ringkasan' => [
                        'lunas' => $lunas,
                        'belum_bayar' => $belum,
                        'transfer' => $tf
                    ],
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan batch ekspor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $export = BarangExport::with('items')->find($id);
        if (!$export) return response()->json(['success' => false, 'message' => 'Export tidak ditemukan.'], 404);

        return response()->json(['success' => true, 'data' => $export], 200);
    }
}
