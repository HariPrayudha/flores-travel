<?php

namespace App\Http\Controllers;

use App\Models\RequestUpdateBarang;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestUpdateBarangRequest;
use App\Http\Requests\UpdateRequestUpdateBarangRequest;
use App\Models\Barang;
use App\Models\Kota;
use App\Models\User;
use App\Notifications\RequestUpdateBarangCreated;
use App\Notifications\RequestUpdateStatusChanged;
use Illuminate\Support\Facades\Notification;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $isKarani = strtolower($user->role) === 'karani';
            $useUserKotaAsal = $request->boolean('use_user_kota_asal');
            if ($isKarani && $user->kota_id && $useUserKotaAsal) {
                $request->merge(['kota_asal' => $user->kota_id]);
            }

            $validated = $request->validate([
                'barang_id'        => 'required|exists:barangs,id',
                'use_user_kota_asal' => 'sometimes|boolean',
                'kota_asal'          => 'exclude_unless:use_user_kota_asal,1|nullable|exists:kotas,id',

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
                'alasan',
            ];

            $changes = [];
            foreach ($updatableKeys as $k) {
                if (array_key_exists($k, $validated)) {
                    $new = $validated[$k];
                    $old = $barang->{$k};

                    $isSame = (is_null($new) && is_null($old)) || ((string)$new === (string)$old);
                    if (!$isSame) {
                        $changes[$k] = $new;
                    }
                }
            }

            if (empty($changes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada perubahan yang diajukan.',
                    'errors'  => ['fields' => ['Minimal satu field harus diisi/berubah untuk request update.']]
                ], 422);
            }

            $reqUpdate = RequestUpdateBarang::create(array_merge([
                'user_id'       => $user->id,
                'barang_id'     => $barang->id,
                'status_update' => 'Pending',
            ], $changes));

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
            $user = Auth::user();

            if ($requestUpdate->user_id != $user->id && ($user->role ?? '') !== 'admin') {
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Request Update barang.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Daftar id wajib diisi.',
            'ids.array'    => 'Format ids tidak valid.',
            'ids.min'      => 'Pilih minimal 1 data untuk dihapus.',
        ]);

        $user = Auth::user();
        $ids  = array_values(array_unique(array_map('intval', $request->input('ids', []))));

        try {
            DB::beginTransaction();

            $existingIds = RequestUpdateBarang::whereIn('id', $ids)->pluck('id')->all();
            $notFoundIds = array_values(array_diff($ids, $existingIds));

            $allowedQuery = RequestUpdateBarang::whereIn('id', $existingIds);
            if (($user->role ?? '') !== 'admin') {
                $allowedQuery->where('user_id', $user->id);
            }
            $allowedIds = $allowedQuery->pluck('id')->all();

            $notAllowedIds = array_values(array_diff($existingIds, $allowedIds));

            $deletedCount = 0;
            if (!empty($allowedIds)) {
                $deletedCount = RequestUpdateBarang::whereIn('id', $allowedIds)->delete();
            }

            DB::commit();

            return response()->json([
                'success'         => true,
                'message'         => $deletedCount > 0 ? 'Sebagian/seluruh data berhasil dihapus.' : 'Tidak ada data yang dihapus.',
                'deleted_count'   => (int) $deletedCount,
                'deleted_ids'     => array_values($allowedIds),
                'not_found_ids'   => $notFoundIds,
                'not_allowed_ids' => $notAllowedIds,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, $id)
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

            $barang = Barang::lockForUpdate()->findOrFail($req->barang_id);

            $fields = [
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
            ];

            $updates = [];
            foreach ($fields as $f) {
                $val = $req->$f;

                if (is_null($val) || (is_string($val) && trim($val) === '')) continue;

                $curr = $barang->$f;
                $changed = false;
                switch ($f) {
                    case 'kota_asal':
                    case 'kota_tujuan':
                        $changed = ((int)$curr !== (int)$val);
                        break;
                    case 'harga_awal':
                    case 'harga_terbayar':
                        $changed = ((float)$curr !== (float)$val);
                        break;
                    case 'status_bayar':
                        $changed = (mb_strtolower(trim((string)$curr)) !== mb_strtolower(trim((string)$val)));
                        break;
                    default:
                        $changed = (trim((string)$curr) !== trim((string)$val));
                        break;
                }

                if ($changed) $updates[$f] = $val;
            }

            if (!empty($updates) && empty($req->before_values)) {
                $before = [];
                foreach (array_keys($updates) as $key) {
                    $before[$key] = $barang->$key;
                }
                $req->before_values = $before;
                $req->save();
            }

            if (array_key_exists('kota_tujuan', $updates)) {
                $kotaBaruId = $updates['kota_tujuan'];
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

            if (!empty($updates)) {
                $updates['user_update'] = $admin?->id;
                $barang->fill($updates);
                $barang->save();
            }

            $req->status_update = 'Disetujui';
            $req->save();

            DB::commit();

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
