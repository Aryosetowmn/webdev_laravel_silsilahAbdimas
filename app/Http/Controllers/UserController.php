<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Validasi data (opsional tapi bagus)
        $validated = $request->validate([
            'id_silsilah' => 'required|unique:users',
            'name' => 'required|string',
            'tempat_tinggal' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'id_parent' => 'nullable|string',
            'id_pasangan' => 'nullable|integer',
            'avatar' => 'nullable|string',
        ]);

        // Simpan data ke database
        $user = User::create($validated);

        // Kembalikan response JSON
        return response()->json([
            'message' => 'User berhasil dibuat!',
            'data' => $user
        ]);
    }

    public function addChild(Request $request)
{
    $request->validate([
        'id_parent' => 'required|exists:users,user_id',
        'name' => 'required|string|max:255',
        'tempat_tinggal' => 'nullable|string|max:255',
        'tanggal_lahir' => 'nullable|date',
    ]);

    // Ambil data parent berdasarkan id_parent
    $parent = User::find($request->id_parent);

    if (!$parent) {
        return response()->json(['message' => 'Parent not found'], 404);
    }

    // Ambil id_silsilah parent
    $parentSilsilah = $parent->id_silsilah;

    // Ambil semua anak dari parent ini
    $children = User::where('id_silsilah', 'like', $parentSilsilah . '.%')->get();

    // Cari urutan terakhir anak
    $lastChildNumber = 0;
    foreach ($children as $child) {
        $parts = explode('.', $child->id_silsilah);
        $lastPart = end($parts);
        if (is_numeric($lastPart) && $lastPart > $lastChildNumber) {
            $lastChildNumber = $lastPart;
        }
    }

    // Buat ID silsilah baru untuk anak
    $newIdSilsilah = $parentSilsilah . '.' . ($lastChildNumber + 1);

    // Simpan data anak baru
    $child = User::create([
        'id_parent' => $parent->user_id,
        'id_silsilah' => $newIdSilsilah,
        'name' => $request->name,
        'tempat_tinggal' => $request->tempat_tinggal,
        'tanggal_lahir' => $request->tanggal_lahir,
    ]);

    return response()->json([
        'message' => 'Anak baru berhasil ditambahkan',
        'data' => $child
    ]);
}
public function getBySilsilah($id_silsilah)
{
    // Cari user berdasarkan id_silsilah
    $user = User::where('id_silsilah', $id_silsilah)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // Ambil semua keturunan secara rekursif
    $userWithChildren = $this->loadChildrenRecursive($user);

    return response()->json($userWithChildren);
}

private function loadChildrenRecursive($user)
{
    // Ambil semua anak
    $children = User::where('id_parent', $user->user_id)->get();

    // Rekursif untuk setiap anak
    $childrenWithDescendants = [];
    foreach ($children as $child) {
        $childrenWithDescendants[] = $this->loadChildrenRecursive($child);
    }

    // Return struktur lengkap dengan anak-anak di dalamnya
    return [
        'user_id' => $user->user_id,
        'id_silsilah' => $user->id_silsilah,
        'name' => $user->name,
        'tempat_tinggal' => $user->tempat_tinggal,
        'tanggal_lahir' => $user->tanggal_lahir,
        'children' => $childrenWithDescendants,
    ];
}

}

