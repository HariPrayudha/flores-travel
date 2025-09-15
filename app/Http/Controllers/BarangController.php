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
use Illuminate\Support\Facades\Auth;
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
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            if (strtolower($user->role) === 'karani') {
                if (!$user->kota_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun tidak memiliki kota. Hubungi admin.'
                    ], 422);
                }
                $request->merge([
                    'user_id'   => $user->id,
                    'kota_asal' => $user->kota_id,
                ]);
            } else {
                if (!$request->filled('user_id')) {
                    $request->merge(['user_id' => $user->id]);
                }
            }

            $validated = $request->validate([
                'user_id'          => 'required|exists:users,id',
                'kota_asal'        => 'required|exists:kotas,id',
                'kota_tujuan'      => 'required|exists:kotas,id',
                'deskripsi_barang' => 'required|string',
                'nama_pengirim'    => 'required|string|max:255',
                'hp_pengirim'      => 'nullable|string|max:20',
                'nama_penerima'    => 'required|string|max:255',
                'hp_penerima'      => 'required|string|max:20',
                'harga_awal'       => 'required|numeric|min:0',
                'status_bayar'     => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'status_barang'    => 'required|string|in:Diterima,Belum Diterima',
                'foto_barang.*'    => 'sometimes|image|mimes:jpg,jpeg,png|max:4096',
                'bukti_transfer' => 'required_if:status_bayar,Transfer|image|mimes:jpg,jpeg,png|max:4096',
                'paket_antar'      => 'sometimes|boolean',
                'alamat'           => 'required_if:paket_antar,1|string',
                'catatan_pengiriman' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $kotaTujuanFirstChar = Kota::where('id', $request->kota_tujuan)->value('nama');
            $kotaTujuanFirstChar = $kotaTujuanFirstChar ? mb_substr($kotaTujuanFirstChar, 0, 1) : 'X';

            $tanggalToday = date('dmy');

            $lastResi = Barang::where('kode_barang', 'like', $kotaTujuanFirstChar . '-' . $tanggalToday . '-%')
                ->orderBy('kode_barang', 'desc')
                ->value('kode_barang');

            $nextNumber = 1;
            if ($lastResi) {
                $parts = explode('-', $lastResi);
                $lastNumber = intval(end($parts));
                $nextNumber = $lastNumber + 1;
            }
            $nextNumberFormatted = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $resi = $kotaTujuanFirstChar . '-' . $tanggalToday . '-' . $nextNumberFormatted;

            $barang = Barang::create([
                'kode_barang'        => $resi,
                'user_id'            => $validated['user_id'],
                'kota_asal'          => $validated['kota_asal'],
                'kota_tujuan'        => $validated['kota_tujuan'],
                'deskripsi_barang'   => $validated['deskripsi_barang'],
                'nama_pengirim'      => $validated['nama_pengirim'],
                'hp_pengirim'        => $validated['hp_pengirim'] ?? null,
                'nama_penerima'      => $validated['nama_penerima'],
                'hp_penerima'        => $validated['hp_penerima'],
                'harga_awal'         => $validated['harga_awal'],
                'status_bayar'       => $validated['status_bayar'],
                'status_barang'      => $validated['status_barang'],
                'catatan_pengiriman' => $validated['catatan_pengiriman'] ?? null,
                'paket_antar'        => $request->boolean('paket_antar'),
                'alamat'             => $request->boolean('paket_antar') ? ($validated['alamat'] ?? null) : null,
            ]);

            if ($request->hasFile('foto_barang')) {
                foreach ($request->file('foto_barang') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    Storage::disk('public')->putFileAs('foto_barang', $file, $filename);

                    FotoBarang::create([
                        'barang_id' => $barang->id,
                        'nama_file' => $filename,
                    ]);
                }
            }

            if ($request->hasFile('bukti_transfer')) {
                $bt = $request->file('bukti_transfer');
                $btName = 'bukti_' . time() . '_' . uniqid() . '.' . $bt->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('bukti_transfer', $bt, $btName);
                $barang->bukti_transfer = $btName;
                $barang->save();
            }

            DB::commit();

            $barang->load('fotoBarang');
            foreach ($barang->fotoBarang as $f) {
                $f->url = Storage::url('foto_barang/' . $f->nama_file);
            }
            $barang->foto_penerima_url   = $barang->foto_penerima ? Storage::url('foto_barang/' . $barang->foto_penerima) : null;
            $barang->ttd_penerima_url    = $barang->ttd_penerima ? Storage::url('foto_barang/' . $barang->ttd_penerima) : null;
            $barang->bukti_transfer_url  = $barang->bukti_transfer ? Storage::url('bukti_transfer/' . $barang->bukti_transfer) : null;

            return response()->json([
                'success' => true,
                'data'    => $barang,
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
            $barang = Barang::with(['user', 'fotoBarang'])->findOrFail($id);

            foreach ($barang->fotoBarang as $f) {
                $f->url = Storage::url('foto_barang/' . $f->nama_file);
            }
            $barang->foto_penerima_url = $barang->foto_penerima
                ? Storage::url('foto_barang/' . $barang->foto_penerima)
                : null;
            $barang->ttd_penerima_url = $barang->ttd_penerima
                ? Storage::url('foto_barang/' . $barang->ttd_penerima)
                : null;

            return response()->json([
                'success' => true,
                'data'    => $barang,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barang not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch barang',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'kota_asal'           => 'nullable|exists:kotas,id',
                'kota_tujuan'         => 'nullable|exists:kotas,id',
                'deskripsi_barang'    => 'nullable|string',
                'nama_pengirim'       => 'nullable|string|max:255',
                'hp_pengirim'         => 'nullable|string|max:20',
                'nama_penerima'       => 'nullable|string|max:255',
                'hp_penerima'         => 'nullable|string|max:20',
                'harga_awal'          => 'nullable|numeric|min:0',
                'status_bayar'        => 'nullable|string|in:Lunas,Belum Bayar,Transfer',
                'status_barang'       => 'nullable|string|in:Diterima,Belum Diterima',
                'foto_barang.*'       => 'sometimes|image|mimes:jpg,jpeg,png|max:4096',
                'foto_penerima'       => 'sometimes|image|mimes:jpg,jpeg,png|max:4096',
                'ttd_penerima'        => 'sometimes|image|mimes:jpg,jpeg,png|max:4096',
                'bukti_transfer'      => 'required_if:status_bayar,Transfer|image|mimes:jpg,jpeg,png|max:4096',
                'delete_foto_ids'     => ['nullable', 'array'],
                'delete_foto_ids.*'   => ['integer', 'exists:foto_barangs,id'],
                'clear_foto_penerima' => 'nullable|boolean',
                'clear_ttd_penerima'  => 'nullable|boolean',
                'clear_bukti_transfer' => 'nullable|boolean',
                'paket_antar'         => 'sometimes|boolean',
                'alamat'              => 'required_if:paket_antar,1|string|nullable',
                'catatan_pengiriman'  => 'nullable|string',
            ]);

            DB::beginTransaction();

            $barang = Barang::with('fotoBarang')->lockForUpdate()->findOrFail($id);

            if ($request->has('kota_tujuan')) {
                $kotaBaruId = $validated['kota_tujuan'] ?? null;
                if (!is_null($kotaBaruId)) {
                    $namaKotaBaru = Kota::where('id', $kotaBaruId)->value('nama');
                    $prefixBaru = $namaKotaBaru ? mb_substr($namaKotaBaru, 0, 1) : 'X';

                    $parts = explode('-', (string)$barang->kode_barang, 3);
                    if (count($parts) === 3) {
                        [$oldPrefix, $oldTanggal, $oldNomor] = $parts;
                        if ($prefixBaru !== $oldPrefix) {
                            $num = (int) $oldNomor;
                            while (true) {
                                $numStr = str_pad($num, 3, '0', STR_PAD_LEFT);
                                $candidate = "{$prefixBaru}-{$oldTanggal}-{$numStr}";
                                $exists = Barang::where('kode_barang', $candidate)
                                    ->where('id', '!=', $barang->id)
                                    ->exists();
                                if (!$exists) {
                                    $barang->kode_barang = $candidate;
                                    break;
                                }
                                $num++;
                            }
                        }
                    }
                }
            }

            $updatable = [
                'kota_asal',
                'kota_tujuan',
                'deskripsi_barang',
                'nama_pengirim',
                'hp_pengirim',
                'nama_penerima',
                'hp_penerima',
                'harga_awal',
                'status_bayar',
                'status_barang',
                'catatan_pengiriman',
            ];
            foreach ($updatable as $field) {
                if ($request->has($field)) {
                    $barang->{$field} = $validated[$field] ?? null;
                }
            }

            if ($request->has('paket_antar')) {
                $barang->paket_antar = $request->boolean('paket_antar');
                $barang->alamat = $barang->paket_antar ? ($validated['alamat'] ?? $barang->alamat) : null;
            } elseif ($request->has('alamat')) {
                $barang->alamat = $validated['alamat'] ?? null;
            }

            $barang->user_update = $request->user()?->id ?? $barang->user_update;
            $barang->save();

            $idsToDelete = $request->input('delete_foto_ids', []);
            if (!empty($idsToDelete)) {
                $fotos = FotoBarang::where('barang_id', $barang->id)
                    ->whereIn('id', $idsToDelete)
                    ->get();
                foreach ($fotos as $f) {
                    Storage::disk('public')->delete('foto_barang/' . $f->nama_file);
                    $f->delete();
                }
            }

            if ($request->hasFile('foto_barang')) {
                foreach ($request->file('foto_barang') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('foto_barang', $file, $filename);
                    FotoBarang::create([
                        'barang_id' => $barang->id,
                        'nama_file' => $filename,
                    ]);
                }
            }

            if ($request->boolean('clear_foto_penerima')) {
                if ($barang->foto_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->foto_penerima);
                }
                $barang->foto_penerima = null;
                $barang->save();
            } elseif ($request->hasFile('foto_penerima')) {
                if ($barang->foto_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->foto_penerima);
                }
                $fp = $request->file('foto_penerima');
                $fpName = 'foto_' . time() . '_' . uniqid() . '.' . $fp->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('foto_barang', $fp, $fpName);
                $barang->foto_penerima = $fpName;
                $barang->save();
            }

            if ($request->boolean('clear_ttd_penerima')) {
                if ($barang->ttd_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->ttd_penerima);
                }
                $barang->ttd_penerima = null;
                $barang->save();
            } elseif ($request->hasFile('ttd_penerima')) {
                if ($barang->ttd_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->ttd_penerima);
                }
                $ttd = $request->file('ttd_penerima');
                $ttdName = 'ttd_' . time() . '_' . uniqid() . '.' . $ttd->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('foto_barang', $ttd, $ttdName);
                $barang->ttd_penerima = $ttdName;
                $barang->save();
            }

            $newStatus = $request->has('status_bayar')
                ? ($validated['status_bayar'] ?? null)
                : $barang->status_bayar;

            if ($newStatus !== 'Transfer') {
                if ($barang->bukti_transfer) {
                    Storage::disk('public')->delete('bukti_transfer/' . $barang->bukti_transfer);
                    $barang->bukti_transfer = null;
                    $barang->save();
                }
            } else {
                if ($request->boolean('clear_bukti_transfer')) {
                    if ($barang->bukti_transfer) {
                        Storage::disk('public')->delete('bukti_transfer/' . $barang->bukti_transfer);
                    }
                    $barang->bukti_transfer = null;
                    $barang->save();
                } elseif ($request->hasFile('bukti_transfer')) {
                    if ($barang->bukti_transfer) {
                        Storage::disk('public')->delete('bukti_transfer/' . $barang->bukti_transfer);
                    }
                    $bt = $request->file('bukti_transfer');
                    $btName = 'bukti_' . time() . '_' . uniqid() . '.' . $bt->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('bukti_transfer', $bt, $btName);
                    $barang->bukti_transfer = $btName;
                    $barang->save();
                }
            }

            DB::commit();

            $barang->load('fotoBarang');
            foreach ($barang->fotoBarang as $f) {
                $f->url = Storage::url('foto_barang/' . $f->nama_file);
            }
            $barang->foto_penerima_url  = $barang->foto_penerima ? Storage::url('foto_barang/' . $barang->foto_penerima) : null;
            $barang->ttd_penerima_url   = $barang->ttd_penerima ? Storage::url('foto_barang/' . $barang->ttd_penerima) : null;
            $barang->bukti_transfer_url = $barang->bukti_transfer ? Storage::url('bukti_transfer/' . $barang->bukti_transfer) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate.',
                'data'    => $barang,
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
            DB::beginTransaction();

            $barang = Barang::lockForUpdate()->findOrFail($id);

            $rules = [
                'harga_terbayar'     => 'nullable|numeric|min:0',
                'status_bayar'       => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'catatan_penerimaan' => 'nullable|string',
            ];

            $rules['ttd_penerima']  = ($barang->ttd_penerima  ? 'sometimes' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048';
            $rules['foto_penerima'] = ($barang->foto_penerima ? 'sometimes' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048';

            $validated = $request->validate($rules);

            if ($request->hasFile('ttd_penerima')) {
                if ($barang->ttd_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->ttd_penerima);
                }
                $ttdFile     = $request->file('ttd_penerima');
                $ttdFilename = 'ttd_' . time() . '_' . uniqid() . '.' . $ttdFile->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('foto_barang', $ttdFile, $ttdFilename);
                $barang->ttd_penerima = $ttdFilename;
            }

            if ($request->hasFile('foto_penerima')) {
                if ($barang->foto_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->foto_penerima);
                }
                $fotoFile     = $request->file('foto_penerima');
                $fotoFilename = 'foto_' . time() . '_' . uniqid() . '.' . $fotoFile->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('foto_barang', $fotoFile, $fotoFilename);
                $barang->foto_penerima = $fotoFilename;
            }

            $barang->status_barang       = 'Diterima';
            if (!$barang->tanggal_terima) {
                $barang->tanggal_terima = now();
            }
            $barang->harga_terbayar      = $validated['harga_terbayar'];
            $barang->status_bayar        = $validated['status_bayar'];
            $barang->catatan_penerimaan  = $validated['catatan_penerimaan'] ?? $barang->catatan_penerimaan;
            $barang->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil ditandai sebagai diterima.',
                'data'    => array_merge($barang->toArray(), [
                    'foto_penerima_url' => $barang->foto_penerima ? Storage::url('foto_barang/' . $barang->foto_penerima) : null,
                    'ttd_penerima_url'  => $barang->ttd_penerima  ? Storage::url('foto_barang/' . $barang->ttd_penerima)  : null,
                ]),
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
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
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus data ini.'], 403);
        }

        try {
            DB::beginTransaction();

            $barang = Barang::with('fotoBarang')->findOrFail($id);

            foreach ($barang->fotoBarang as $foto) {
                Storage::disk('public')->delete('foto_barang/' . $foto->nama_file);
                $foto->delete();
            }

            if ($barang->foto_penerima) {
                Storage::disk('public')->delete('foto_barang/' . $barang->foto_penerima);
            }
            if ($barang->ttd_penerima) {
                Storage::disk('public')->delete('foto_barang/' . $barang->ttd_penerima);
            }
            if ($barang->bukti_transfer) {
                Storage::disk('public')->delete('bukti_transfer/' . $barang->bukti_transfer);
            }

            $barang->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data barang berhasil dihapus.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus data ini.'], 403);
        }

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:barangs,id'],
        ]);

        try {
            DB::beginTransaction();

            $barangs = Barang::with('fotoBarang')
                ->whereIn('id', $validated['ids'])
                ->get();

            foreach ($barangs as $barang) {
                foreach ($barang->fotoBarang as $foto) {
                    Storage::disk('public')->delete('foto_barang/' . $foto->nama_file);
                    $foto->delete();
                }
                if ($barang->foto_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->foto_penerima);
                }
                if ($barang->ttd_penerima) {
                    Storage::disk('public')->delete('foto_barang/' . $barang->ttd_penerima);
                }
                if ($barang->bukti_transfer) {
                    Storage::disk('public')->delete('bukti_transfer/' . $barang->bukti_transfer);
                }

                $barang->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'deleted' => $validated['ids']], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function findByKode(string $kode)
    {
        try {
            $norm = strtoupper(preg_replace('/\s+/', '', $kode));

            $barang = Barang::query()
                ->select('id', 'kode_barang', 'status_barang')
                ->whereRaw("REPLACE(UPPER(kode_barang), ' ', '') = ?", [$norm])
                ->first();

            if (!$barang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barang not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $barang,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch barang by kode',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
