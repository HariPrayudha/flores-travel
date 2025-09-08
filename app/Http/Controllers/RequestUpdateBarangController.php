<?php

namespace App\Http\Controllers;

use App\Models\RequestUpdateBarang;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestUpdateBarangRequest;
use App\Http\Requests\UpdateRequestUpdateBarangRequest;
use App\Models\Barang;
use App\Models\User;
use App\Notifications\RequestUpdateBarangCreated;
use App\Notifications\RequestUpdateStatusChanged;
use Illuminate\Support\Facades\Notification;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class RequestUpdateBarangController extends Controller
{
    public function index()
    {
        try {
            $requestUpdate = RequestUpdateBarang::with(['barang', 'user'])->get();

            return response()->json([
                'success' => true,
                'data'    => $requestUpdate
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
                'barang_id'        => 'required|exists:barangs,id',
                'kota_asal'        => 'sometimes|nullable|exists:kotas,id',
                'kota_tujuan'      => 'sometimes|nullable|exists:kotas,id',
                'deskripsi_barang' => 'sometimes|nullable|string',
                'nama_pengirim'    => 'sometimes|nullable|string|max:255',
                'hp_pengirim'      => 'sometimes|nullable|string|max:20',
                'nama_penerima'    => 'sometimes|nullable|string|max:255',
                'hp_penerima'      => 'sometimes|nullable|string|max:20',
                'harga_awal'       => 'sometimes|nullable|numeric|min:0',
                'harga_terbayar'   => 'sometimes|nullable|numeric|min:0',
                'status_bayar'     => 'sometimes|nullable|string|in:Lunas,Belum Bayar,Transfer',
                'alasan'           => 'sometimes|nullable|string',
                'status_update'    => 'sometimes|nullable|string|in:Pending,Disetujui,Ditolak',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $barang = Barang::findOrFail($validated['barang_id']);

            $updatableKeys = [
                'kota_asal',
                'kota_tujuan',
                'deskripsi_barang',
                'nama_pengirim',
                'hp_pengirim',
                'nama_penerima',
                'hp_penerima',
                'harga_awal',
                'harga_terbayar',
                'status_bayar',
                'alasan'
            ];
            $hasAny = collect($updatableKeys)->some(fn($k) => $request->has($k));

            if (!$hasAny) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada perubahan yang diajukan.',
                    'errors'  => ['fields' => ['Minimal satu field harus diisi untuk request update.']]
                ], 422);
            }

            $data = [
                'user_id'       => $user->id,
                'barang_id'     => $barang->id,
                'status_update' => 'Pending',
            ];
            foreach ($updatableKeys as $k) {
                if (array_key_exists($k, $validated)) {
                    $data[$k] = $validated[$k];
                }
            }

            $reqUpdate = RequestUpdateBarang::create($data);

            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new RequestUpdateBarangCreated($reqUpdate));

            return response()->json([
                'success' => true,
                'data'    => $reqUpdate->load(['barang', 'user'])
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request update barang',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $requestUpdate = RequestUpdateBarang::with(['user', 'barang'])->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'data'    => $requestUpdate
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

    public function update(Request $r)
    {
        $r->validate([
            'user_id'          => 'required|exists:users,id',
            'id'               => 'required',
            'status'           => 'required|string|max:20'
        ]);

        try {
            $updateBarang = RequestUpdateBarang::where('id', $r->id)->first();
            if ($r->status == 'Diterima') {
                $barang = Barang::where('id', $updateBarang->barang_id)->first();

                $barang->kota_asal = $updateBarang->kota_asal;
                $barang->kota_tujuan = $updateBarang->kota_tujuan;
                $barang->deskripsi_barang = $updateBarang->deskripsi_barang;
                $barang->nama_pengirim = $updateBarang->nama_pengirim;
                $barang->hp_pengirim = $updateBarang->hp_pengirim;
                $barang->nama_penerima = $updateBarang->nama_penerima;
                $barang->hp_penerima = $updateBarang->hp_penerima;
                $barang->harga_awal = $updateBarang->harga_awal;
                $barang->status_bayar = $updateBarang->status_bayar;

                $updateBarang->status_update = "Diterima";
            } elseif ($r->status == 'Diterima') {
                $updateBarang->status_update = "Ditolak";
            }

            $barang->save();
            $updateBarang->save();

            return response()->json([
                'success' => true,
                'message' => 'Barang updated successfully',
                'data' => $updateBarang->load('barang'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barang not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $requestUpdate = RequestUpdateBarang::findOrFail($id);
            $user = auth()->user();

            if ($requestUpdate->user_id != $user->id && $user->role != 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus data ini.'
                ], 403);
            }

            $requestUpdate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request Update Barang berhasil dihapus.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Request Update Barang tidak ditemukan.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Request Update barang.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $admin = $request->user();

            DB::beginTransaction();

            // Lock request + eager load relasi untuk dipakai setelahnya
            $req = RequestUpdateBarang::lockForUpdate()
                ->with(['barang', 'user'])
                ->findOrFail($id);

            // Hanya boleh diproses kalau masih pending / null
            if ($req->status_update && strtolower($req->status_update) !== 'pending') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request sudah diproses.'
                ], 409);
            }

            // Lock barang agar konsisten
            $barang = Barang::lockForUpdate()->findOrFail($req->barang_id);

            // Terapkan perubahan dari request ke barang
            $barang->update([
                'kota_asal'        => $req->kota_asal,
                'kota_tujuan'      => $req->kota_tujuan,
                'deskripsi_barang' => $req->deskripsi_barang,
                'nama_pengirim'    => $req->nama_pengirim,
                'hp_pengirim'      => $req->hp_pengirim,
                'nama_penerima'    => $req->nama_penerima,
                'hp_penerima'      => $req->hp_penerima,
                'harga_awal'       => $req->harga_awal,
                'status_bayar'     => $req->status_bayar,
                'user_update'      => $admin?->id,
            ]);

            // Update status request
            $req->status_update = 'Disetujui';
            $req->save();

            DB::commit();

            // Kirim notifikasi ke karani (pemilik pengajuan)
            if ($req->user_id) {
                $karani = User::find($req->user_id);
                if ($karani) {
                    $karani->notify(new RequestUpdateStatusChanged(
                        reqUpdate: $req->fresh(['barang', 'user']),
                        newStatus: 'Disetujui',
                        actor: $admin
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Request disetujui & data barang diperbarui.',
                'data'    => $req->load(['barang', 'user']),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $admin = $request->user();

            DB::beginTransaction();

            $req = RequestUpdateBarang::lockForUpdate()
                ->with(['barang', 'user'])
                ->findOrFail($id);

            if ($req->status_update && strtolower($req->status_update) !== 'pending') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Request sudah diproses.'
                ], 409);
            }

            $req->status_update = 'Ditolak';
            $req->save();

            DB::commit();

            if ($req->user_id) {
                $karani = User::find($req->user_id);
                if ($karani) {
                    $karani->notify(new RequestUpdateStatusChanged(
                        reqUpdate: $req->fresh(['barang', 'user']),
                        newStatus: 'Ditolak',
                        actor: $admin
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Request ditolak.',
                'data'    => $req->load(['barang', 'user']),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
