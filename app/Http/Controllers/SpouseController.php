<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spouse;
use App\Models\User;

class SpouseController extends Controller
{
    public function index($user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $spouses = Spouse::where('primary_child_id', $user_id)
            ->orWhere('related_user_id', $user_id)
            ->with(['primaryUser', 'relatedUser'])
            ->get();

        if ($spouses->isEmpty()) {
            return response()->json(['message' => 'Belum ada pasangan untuk user ini'], 200);
        }

        return response()->json([
            'message' => 'Daftar pasangan user',
            'data' => $spouses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'primary_child_id' => 'required|exists:users,user_id',
            'related_user_id' => 'required|exists:users,user_id|different:primary_child_id',
        ]);

        $existing = Spouse::where(function ($q) use ($validated) {
            $q->where('primary_child_id', $validated['primary_child_id'])
              ->where('related_user_id', $validated['related_user_id']);
        })->orWhere(function ($q) use ($validated) {
            $q->where('primary_child_id', $validated['related_user_id'])
              ->where('related_user_id', $validated['primary_child_id']);
        })->first();

        if ($existing) {
            return response()->json(['message' => 'Pasangan sudah terdaftar'], 409);
        }

        $spouse = Spouse::create($validated);

        return response()->json([
            'message' => 'Pasangan berhasil ditambahkan',
            'data' => $spouse
        ], 201);
    }

    public function destroy($id)
    {
        $spouse = Spouse::find($id);
        if (!$spouse) {
            return response()->json(['message' => 'Pasangan tidak ditemukan'], 404);
        }

        $spouse->delete();

        return response()->json(['message' => 'Pasangan berhasil dihapus']);
    }
}
