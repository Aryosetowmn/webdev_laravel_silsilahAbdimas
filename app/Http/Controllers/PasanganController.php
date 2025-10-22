<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasangan;
use App\Models\User;

class PasanganController extends Controller
{
    /**
     * Tampilkan semua pasangan dari user tertentu.
     */
    public function index($user_id)
    {
        // Cek apakah user ada
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Ambil pasangan di mana dia menjadi primary atau related
        $pasangans = Pasangan::where('primary_child_id', $user_id)
            ->orWhere('related_user_id', $user_id)
            ->with(['primaryUser', 'relatedUser'])
            ->get();

        if ($pasangans->isEmpty()) {
            return response()->json(['message' => 'Belum ada pasangan untuk user ini'], 200);
        }

        return response()->json([
            'message' => 'Daftar pasangan user',
            'data' => $pasangans
        ]);
    }

    /**
     * Tambahkan pasangan antara dua user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'primary_child_id' => 'required|exists:users,user_id',
            'related_user_id' => 'required|exists:users,user_id|different:primary_child_id',
        ]);

        // Cek apakah pasangan sudah ada
        $existing = Pasangan::where(function ($q) use ($validated) {
            $q->where('primary_child_id', $validated['primary_child_id'])
              ->where('related_user_id', $validated['related_user_id']);
        })->orWhere(function ($q) use ($validated) {
            $q->where('primary_child_id', $validated['related_user_id'])
              ->where('related_user_id', $validated['primary_child_id']);
        })->first();

        if ($existing) {
            return response()->json(['message' => 'Pasangan sudah terdaftar'], 409);
        }

        // Buat pasangan baru
        $pasangan = Pasangan::create($validated);

        // Update masing-masing user agar saling terhubung
        // User::where('user_id', $validated['primary_child_id'])->update([
        //     'id_pasangan' => $validated['related_user_id']
        // ]);

        // User::where('user_id', $validated['related_user_id'])->update([
        //     'id_pasangan' => $validated['primary_child_id']
        // ]);

        return response()->json([
            'message' => 'Pasangan berhasil ditambahkan',
            'data' => $pasangan
        ], 201);
    }

    /**
     * Hapus pasangan berdasarkan ID pasangan.
     */
    public function destroy($id)
    {
        $pasangan = Pasangan::find($id);
        if (!$pasangan) {
            return response()->json(['message' => 'Pasangan tidak ditemukan'], 404);
        }

        // Reset id_pasangan kedua user
        // User::where('user_id', $pasangan->primary_child_id)->update(['id_pasangan' => null]);
        // User::where('user_id', $pasangan->related_user_id)->update(['id_pasangan' => null]);

        $pasangan->delete();

        return response()->json(['message' => 'Pasangan berhasil dihapus']);
    }
}
