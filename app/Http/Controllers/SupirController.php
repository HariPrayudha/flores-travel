<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SupirController extends Controller
{
    public function index()
    {
        try {
            $supirs = User::with('kota:id,nama')
                ->where('role', 'supir')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $supirs
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supir list'
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

            $supir = User::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($passwordPlain),
                'role'     => 'supir',
                'kota_id'  => $validated['kota_id'] ?? null
            ]);

            $supir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'data'    => $supir
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
                'message' => 'Failed to create supir',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $supir = User::with('kota:id,nama')
                ->where('role', 'supir')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $supir
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supir not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supir'
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

            $supir = User::where('role', 'supir')->findOrFail($id);

            $supir->name = $validated['name'];
            $supir->username = $validated['username'];
            $supir->kota_id = $validated['kota_id'] ?? null;

            if (!empty($validated['password'])) {
                $supir->password = Hash::make($validated['password']);
            }

            $supir->save();

            $supir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'data'    => $supir
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supir not found',
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
                'message' => 'Failed to update supir',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $supir = User::where('role', 'supir')->findOrFail($id);
            $supir->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supir deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supir not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supir'
            ], 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            $supir = User::where('role', 'supir')->findOrFail($id);

            $supir->password = Hash::make($supir->username);
            $supir->save();

            $supir->load('kota:id,nama');

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset to username.',
                'data'    => $supir,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supir not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
            ], 500);
        }
    }
}
