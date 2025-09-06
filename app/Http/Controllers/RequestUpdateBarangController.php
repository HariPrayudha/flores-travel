<?php

namespace App\Http\Controllers;

use App\Models\RequestUpdateBarang;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestUpdateBarangRequest;
use App\Http\Requests\UpdateRequestUpdateBarangRequest;
use App\Models\Barang;
use App\Models\User;
use App\Notifications\RequestUpdateBarangCreated;
use Illuminate\Support\Facades\Notification; 
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
                'kota_asal'        => 'required|exists:kotas,id',
                'kota_tujuan'      => 'required|exists:kotas,id',
                'deskripsi_barang' => 'required|string',
                'nama_pengirim'    => 'required|string|max:255',
                'hp_pengirim'      => 'required|string|max:20',
                'nama_penerima'    => 'required|string|max:255',
                'hp_penerima'      => 'required|string|max:20',
                'harga_awal'       => 'required|numeric|min:0',
                'status_bayar'     => 'required|string|in:Lunas,Belum Bayar,Transfer',
                'alasan'           => 'nullable|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $barang = Barang::findOrFail($validated['barang_id']);

            // if ($barang->status_barang === "Diterima") {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Barang tidak bisa diupdate lagi.'
            //     ], 403);
            // }

            $reqUpdate = RequestUpdateBarang::create([
                'user_id'          => $user->id,            // dari token
                'barang_id'        => $barang->id,
                'kota_asal'        => $validated['kota_asal'],
                'kota_tujuan'      => $validated['kota_tujuan'],
                'deskripsi_barang' => $validated['deskripsi_barang'],
                'nama_pengirim'    => $validated['nama_pengirim'],
                'hp_pengirim'      => $validated['hp_pengirim'],
                'nama_penerima'    => $validated['nama_penerima'],
                'hp_penerima'      => $validated['hp_penerima'],
                'harga_awal'       => $validated['harga_awal'],
                'status_bayar'     => $validated['status_bayar'],
                'alasan'           => $validated['alasan'] ?? null,
            ]);

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
        } catch (\Exception $e) {
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
}
