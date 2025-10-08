<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangExport;
use App\Models\BarangExportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set('Asia/Jakarta');

class ExportPenerimaanController extends Controller
{
    /**
     * GET /supir-exports/items?today=1
     * Ambil daftar item export (join barangs) — default HARI INI.
     */
    public function index(Request $request)
    {
        try {
            $query = BarangExportItem::query()
                ->select([
                    'barang_export_items.id as export_item_id',
                    'barang_export_items.export_id',
                    'barang_export_items.status_terima',
                    'barang_export_items.diterima_at',
                    'barangs.id as barang_id',
                    'barangs.kode_barang',
                    'barangs.kota_asal',
                    'barangs.kota_tujuan',
                    'barangs.deskripsi_barang',
                    'barangs.nama_penerima',
                    'barangs.harga_awal',
                    'barangs.harga_terbayar',
                    'barangs.status_bayar',
                    'barangs.paket_antar',
                    'barangs.alamat',
                    'barang_exports.kode as export_kode',
                    'barang_exports.created_at as export_created_at',
                ])
                ->join('barang_exports', 'barang_exports.id', '=', 'barang_export_items.export_id')
                ->join('barangs', 'barangs.id', '=', 'barang_export_items.barang_id');

            if ($request->boolean('today', true)) {
                $query->whereDate('barang_exports.created_at', now()->format('Y-m-d'));
            }

            // Opsional: kalau user karani punya kota, batasi berdasar kota_tujuan
            $user = $request->user();
            if ($user && strtolower($user->role) === 'karani' && $user->kota_id) {
                $query->where('barangs.kota_tujuan', $user->kota_id);
            }

            $rows = $query
                ->orderBy('barang_exports.created_at', 'desc')
                ->orderBy('barangs.kota_tujuan')
                ->get();

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /supir-exports/items/mark-received
     * Body: { ids: [export_item_id, ...] }
     * Menandai item export sebagai diterima (tanpa menyentuh tabel barangs).
     */
    public function markReceived(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:barang_export_items,id'],
        ]);

        try {
            DB::beginTransaction();

            $count = BarangExportItem::whereIn('id', $validated['ids'])
                ->update([
                    'status_terima'     => true,
                    'diterima_at'       => now(),
                    'penerima_user_id'  => $request->user()->id ?? null,
                ]);

            DB::commit();

            return response()->json(['success' => true, 'updated' => $count], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
