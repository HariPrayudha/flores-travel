<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class KurirController extends Controller
{
    public function index()
    {
        try {
            $kurirs = User::with('kota:id,nama')
                ->where('role', 'kurir')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $kurirs
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kurir list'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'      => 'required|string|max:255',
                'username'  => 'required|string|unique:users,username',
                'password'  => 'nullable|string|min:6',
                'kota_id'   => 'nullable|exists:kotas,id',
            ]);

            $passwordPlain = $validated['password'] ?? $validated['username'];

            $kurir = User::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($passwordPlain),
                'role'     => 'kurir',
                'kota_id'  => $validated['kota_id'] ?? null
            ]);

            $kurir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'data'    => $kurir
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kurir',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $kurir = User::with('kota:id,nama')
                ->where('role', 'kurir')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $kurir
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kurir'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name'      => 'required|string|max:255',
                'username'  => [
                    'required',
                    'string',
                    Rule::unique('users', 'username')->ignore($id),
                ],
                'password'  => 'nullable|string|min:6',
                'kota_id'   => 'nullable|exists:kotas,id',
            ]);

            $kurir = User::where('role', 'kurir')->findOrFail($id);

            $kurir->name = $validated['name'];
            $kurir->username = $validated['username'];
            $kurir->kota_id = $validated['kota_id'] ?? null;

            if (!empty($validated['password'])) {
                $kurir->password = Hash::make($validated['password']);
            }

            $kurir->save();

            $kurir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'data'    => $kurir
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update kurir',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kurir = User::where('role', 'kurir')->findOrFail($id);
            $kurir->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kurir deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete kurir'
            ], 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            $kurir = User::where('role', 'kurir')->findOrFail($id);

            $kurir->password = Hash::make($kurir->username);
            $kurir->save();

            $kurir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset to username.',
                'data'    => $kurir,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
            ], 500);
        }
    }
}
