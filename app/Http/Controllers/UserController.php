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

    // Ambil semua keturunan secara rekursif (dengan pasangan)
    $userWithRelations = $this->loadChildrenAndSpouseRecursive($user);

    return response()->json($userWithRelations);
}

private function loadChildrenAndSpouseRecursive($user)
{
    // 🔹 Ambil semua anak
    $children = User::where('id_parent', $user->user_id)->get();

    // 🔹 Rekursif untuk setiap anak
    $childrenWithDescendants = [];
    foreach ($children as $child) {
        $childrenWithDescendants[] = $this->loadChildrenAndSpouseRecursive($child);
    }

    // 🔹 Ambil pasangan (bisa dua arah)
    $spouses = \App\Models\Pasangan::where('primary_child_id', $user->user_id)
        ->orWhere('related_user_id', $user->user_id)
        ->get();

    $spouseList = [];

    foreach ($spouses as $pasangan) {
        // Tentukan siapa pasangan-nya (bukan dirinya sendiri)
        $spouseUserId = $pasangan->primary_child_id == $user->user_id
            ? $pasangan->related_user_id
            : $pasangan->primary_child_id;

        $spouseUser = \App\Models\User::find($spouseUserId);

        if ($spouseUser) {
            $spouseList[] = [
                'user_id' => $spouseUser->user_id,
                'name' => $spouseUser->name,
                'tempat_tinggal' => $spouseUser->tempat_tinggal,
                'tanggal_lahir' => $spouseUser->tanggal_lahir,
            ];
        }
    }

    // 🔹 Return struktur lengkap dengan anak dan pasangan
    return [
        'user_id' => $user->user_id,
        'id_silsilah' => $user->id_silsilah,
        'name' => $user->name,
        'tempat_tinggal' => $user->tempat_tinggal,
        'tanggal_lahir' => $user->tanggal_lahir,
        'spouse' => $spouseList, // ← pasangan ditambahkan di sini
        'children' => $childrenWithDescendants,
    ];
}


public function login(Request $request)
{
    $validated = $request->validate([
        'id_silsilah' => 'required|string',
        'tanggal_lahir' => 'nullable|date'
    ]);

    $user = User::where('id_silsilah', $validated['id_silsilah'])
        ->when($validated['tanggal_lahir'] ?? false, fn($q) => $q->where('tanggal_lahir', $validated['tanggal_lahir']))
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Silsilah NIK atau tanggal lahir tidak ditemukan'], 404);
    }

    return response()->json([
        'message' => 'Login berhasil',
        'data' => $user
    ]);
}

public function storeWithoutSilsilah(Request $request)
{
    // Validasi data tanpa id_silsilah wajib
    $validated = $request->validate([
        'id_silsilah' => 'nullable',
        'name' => 'required|string',
        'tempat_tinggal' => 'nullable|string',
        'tanggal_lahir' => 'nullable|date',
        'id_parent' => 'nullable|string',
        'avatar' => 'nullable|string',
    ]);

    // Simpan data user ke database
    $user = User::create($validated);

    // Kembalikan response JSON
    return response()->json([
        'message' => 'User berhasil dibuat tanpa id_silsilah wajib!',
        'data' => $user
    ]);
}


}

