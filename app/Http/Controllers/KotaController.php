<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kota;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KotaController extends Controller
{
    public function index()
    {
        try {
            $kotas = Kota::all();

            return response()->json([
                'success' => true,
                'data'    => $kotas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kota list'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255|unique:kotas,nama'
            ]);

            $kota = Kota::create([
                'nama' => $request->nama
            ]);

            return response()->json([
                'success' => true,
                'data'    => $kota
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kota'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $kota = Kota::findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $kota
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kota not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kota'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:kotas,nama,' . $id,
        ]);

        try {
            $kota = Kota::findOrFail($id);
            $kota->nama = $request->nama;
            $kota->save();

            return response()->json([
                'success' => true,
                'message' => 'Kota updated successfully',
                'data' => $kota,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kota not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update kota',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kota = Kota::findOrFail($id);
            $kota->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kota deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kota not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kota',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
