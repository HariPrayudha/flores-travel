<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        try {
            $r->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string'
            ]);

            $user = User::where('username', $r->username)->first();

            if (!$user || !Hash::check($r->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success'      => true,
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    public function logout(Request $r)
    {
        try {
            $r->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name'          => ['required', 'string', 'max:255'],
                'username'      => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
                'gambar'        => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                'remove_gambar' => ['sometimes', 'boolean'],
            ]);

            $user->name = $validated['name'];
            $user->username = $validated['username'];

            if ($request->boolean('remove_gambar')) {
                if ($user->gambar) {
                    Storage::disk('public')->delete('foto_profil/' . $user->gambar);
                }
                $user->gambar = null;
            }

            if ($request->hasFile('gambar')) {
                if ($user->gambar) {
                    Storage::disk('public')->delete('foto_profil/' . $user->gambar);
                }

                $file = $request->file('gambar');
                $filename = 'pp_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('foto_profil', $file, $filename);

                $user->gambar = $filename;
            }

            $user->save();

            $user->gambar_url = $user->gambar ? Storage::url('foto_profil/' . $user->gambar) : null;

            return response()->json([
                'success' => true,
                'user'    => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil',
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
