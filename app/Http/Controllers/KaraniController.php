<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class KaraniController extends Controller
{
    public function index()
    {
        try {
            $karanis = User::where('role', 'karani')->get();

            return response()->json([
                'success' => true,
                'data' => $karanis
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch karani list'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'password' => 'nullable|string|min:6',
            ]);

            $passwordPlain = $validated['password'] ?? $validated['username'];

            $karani = User::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($passwordPlain),
                'role'     => 'karani',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $karani
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
                'message' => 'Failed to create karani',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $karani = User::where('role', 'karani')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $karani
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Karani not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch karani'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'username' => [
                    'required',
                    'string',
                    Rule::unique('users', 'username')->ignore($id),
                ],
                'password' => 'nullable|string|min:6',
            ]);

            $karani = User::where('role', 'karani')->findOrFail($id);

            $karani->name = $validated['name'];
            $karani->username = $validated['username'];

            if (!empty($validated['password'])) {
                $karani->password = Hash::make($validated['password']);
            }

            $karani->save();

            return response()->json([
                'success' => true,
                'data'    => $karani
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Karani not found',
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
                'message' => 'Failed to update karani',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $karani = User::where('role', 'karani')->findOrFail($id);
            $karani->delete();

            return response()->json([
                'success' => true,
                'message' => 'Karani deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Karani not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete karani'
            ], 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            $karani = User::where('role', 'karani')->findOrFail($id);

            $karani->password = Hash::make($karani->username);
            $karani->save();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset to username.',
                'data'    => $karani,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Karani not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
            ], 500);
        }
    }
}
