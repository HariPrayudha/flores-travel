<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\FotoBarang;
use App\Models\Kota;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

date_default_timezone_set('Asia/Jakarta');

class BarangController extends Controller
{
    public function index()
    {
        try {
            $barang = Barang::with(['fotoBarang', 'user'])->get();

            return response()->json([
                'success' => true,
                'data'    => $barang
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id'          => 'required|exists:users,id',
                'kota_asal'        => 'required|exists:kotas,id',
                'kota_tujuan'      => 'required|exists:kotas,id',
                'deskripsi_barang' => 'required|string',
                'nama_pengirim'    => 'required|string|max:255',
                'hp_pengirim'      => 'required|string|max:20',
                'nama_penerima'    => 'required|string|max:255',
                'hp_penerima'      => 'required|string|max:20',
                'harga_awal'       => 'required|numeric|min:0',
                'status_bayar' => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'status_barang' => 'required|string|in:Diterima,Belum Diterima',
                'foto_barang.*'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'catatan_pengiriman'       => 'nullable|string',
            ]);

            DB::beginTransaction();

            $kotaTujuan = Kota::where('id', $request->kota_tujuan)->pluck('nama')->first()[0];
            $tanggalToday = date('dmy');

            $lastResi = Barang::where('kode_barang', 'like', $kotaTujuan . '-' . $tanggalToday . '-%')
                ->orderBy('kode_barang', 'desc')
                ->pluck('kode_barang')
                ->first();

            $nextNumber = 1;

            if ($lastResi) {
                $parts = explode('-', $lastResi);
                $lastNumber = intval($parts[count($parts) - 1]);
                $nextNumber = $lastNumber + 1;
            }

            $nextNumberFormatted = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $resi = $kotaTujuan . '-' . $tanggalToday . '-' . $nextNumberFormatted;

            $barang = Barang::create([
                'kode_barang'      => $resi,
                'user_id'          => $validated['user_id'],
                'kota_asal'        => $validated['kota_asal'],
                'kota_tujuan'      => $validated['kota_tujuan'],
                'deskripsi_barang' => $validated['deskripsi_barang'],
                'nama_pengirim'    => $validated['nama_pengirim'],
                'hp_pengirim'      => $validated['hp_pengirim'],
                'nama_penerima'    => $validated['nama_penerima'],
                'hp_penerima'      => $validated['hp_penerima'],
                'harga_awal'       => $validated['harga_awal'],
                'status_bayar'     => $validated['status_bayar'],
                'status_barang'    => $validated['status_barang'],
                'catatan_pengiriman' => $validated['catatan_pengiriman'],
            ]);

            if ($request->hasFile('foto_barang')) {
                foreach ($request->file('foto_barang') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->storeAs('public/foto_barang', $filename);

                    FotoBarang::create([
                        'barang_id' => $barang->id,
                        'nama_file' => $filename
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $barang->load('fotoBarang')
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create barang',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $barang = Barang::with(['user', 'fotoBarang'])->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'data'    => $barang
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barang not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch barang'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'kota_asal'        => 'required|exists:kotas,id',
                'kota_tujuan'      => 'required|exists:kotas,id',
                'deskripsi_barang' => 'required|string',
                'nama_pengirim'    => 'required|string|max:255',
                'hp_pengirim'      => 'required|string|max:20',
                'nama_penerima'    => 'required|string|max:255',
                'hp_penerima'      => 'required|string|max:20',
                'harga_awal'       => 'required|numeric|min:0',
                'status_bayar'     => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'status_barang'    => 'required|string|in:Diterima,Belum Diterima',
                'foto_barang.*'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            DB::beginTransaction();

            $barang = Barang::with('fotoBarang')->findOrFail($id);

            $barang->update([
                'kota_asal'        => $validated['kota_asal'],
                'kota_tujuan'      => $validated['kota_tujuan'],
                'deskripsi_barang' => $validated['deskripsi_barang'],
                'nama_pengirim'    => $validated['nama_pengirim'],
                'hp_pengirim'      => $validated['hp_pengirim'],
                'nama_penerima'    => $validated['nama_penerima'],
                'hp_penerima'      => $validated['hp_penerima'],
                'harga_awal'       => $validated['harga_awal'],
                'status_bayar'     => $validated['status_bayar'],
                'status_barang'    => $validated['status_barang'],
            ]);

            if ($request->hasFile('foto_barang')) {
                foreach ($barang->fotoBarang as $foto) {
                    Storage::delete('public/foto_barang/' . $foto->nama_file);
                    $foto->delete();
                }

                foreach ($request->file('foto_barang') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->storeAs('public/foto_barang', $filename);

                    FotoBarang::create([
                        'barang_id' => $barang->id,
                        'nama_file' => $filename
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate.',
                'data'    => $barang->load('fotoBarang')
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function terimaBarang(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'ttd_penerima'  => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'foto_penerima' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'harga_terbayar' => 'required|numeric|min:0',
                'status_bayar' => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'catatan_penerimaan'       => 'nullable|string',
            ]);

            DB::beginTransaction();

            $barang = Barang::findOrFail($id);

            $ttdFile = $request->file('ttd_penerima');
            $ttdFilename = 'ttd_' . time() . '_' . uniqid() . '.' . $ttdFile->getClientOriginalExtension();
            $ttdFile->storeAs('public/foto_barang', $ttdFilename);

            $fotoFile = $request->file('foto_penerima');
            $fotoFilename = 'foto_' . time() . '_' . uniqid() . '.' . $fotoFile->getClientOriginalExtension();
            $fotoFile->storeAs('public/foto_barang', $fotoFilename);

            $barang->update([
                'status_barang'   => 'Diterima',
                'tanggal_terima'  => now(),
                'ttd_penerima'    => $ttdFilename,
                'foto_penerima'   => $fotoFilename,
                'harga_terbayar'  => $request->harga_terbayar,
                'status_bayar'    => $request->status_bayar,
                'catatan_penerimaan' => $request->catatan_penerimaan
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil ditandai sebagai diterima.',
                'data'    => $barang
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status barang.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus data ini.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $barang = Barang::with('fotoBarang')->findOrFail($id);

            foreach ($barang->fotoBarang as $foto) {
                Storage::delete('public/foto_barang/' . $foto->nama_file);
                $foto->delete();
            }

            $barang->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data barang berhasil dihapus.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
