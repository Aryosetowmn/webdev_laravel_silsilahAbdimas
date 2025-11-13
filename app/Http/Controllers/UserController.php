<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Spouse;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;


class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'family_tree_id' => 'required|unique:users',
            'full_name' => 'required|string',
            'address' => 'nullable|string',
            'birth_year' => 'nullable|string',
            'parent_id' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil dibuat!',
            'data' => $user
        ]);
    }

    public function addChild(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:users,user_id',
            'full_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'birth_year' => 'nullable|string',
        ]);

        $parent = User::find($request->parent_id);

        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }

        // Ambil family_tree_id parent
        $parentTree = $parent->family_tree_id;

        // Ambil semua anak dari parent ini
        $children = User::where('family_tree_id', 'like', $parentTree . '.%')->get();

        // Cari urutan terakhir anak
        $lastChildNumber = 0;
        foreach ($children as $child) {
            $parts = explode('.', $child->family_tree_id);
            $lastPart = end($parts);
            if (is_numeric($lastPart) && $lastPart > $lastChildNumber) {
                $lastChildNumber = $lastPart;
            }
        }

        // Buat family_tree_id baru untuk anak
        $newTreeId = $parentTree . '.' . ($lastChildNumber + 1);

        // Simpan data anak baru
        $child = User::create([
            'parent_id' => $parent->user_id,
            'family_tree_id' => $newTreeId,
            'full_name' => $request->full_name,
            'address' => $request->address,
            'birth_year' => $request->birth_year,
        ]);

        return response()->json([
            'message' => 'Anak baru berhasil ditambahkan',
            'data' => $child
        ]);
    }

    public function getByTree($family_tree_id)
    {
        $user = User::where('family_tree_id', $family_tree_id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $userWithRelations = $this->loadChildrenAndSpouseRecursive($user);

        return response()->json($userWithRelations);
    }

    public function updateProfile(Request $request, $id)
{
    // Cari user berdasarkan ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    // Validasi input (semua opsional, karena bisa edit sebagian)
    $validated = $request->validate([
        'full_name' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'birth_year' => 'nullable|string|max:10',
        'avatar' => 'nullable|string|max:255',
    ]);

    // Update data user
    $user->update($validated);

    return response()->json([
        'message' => 'Profil berhasil diperbarui',
        'data' => $user
    ]);
}

// public function updateProfileWithCredential(Request $request)
// {
//     $request->validate([
//         'family_tree_id' => 'required|string',           // data yang mau diedit
//         'credential_family_tree_id' => 'required|string' // siapa yang edit
//     ]);

//     $targetFamilyTreeId = $request->family_tree_id;
//     $credentialTreeId = $request->credential_family_tree_id;

//     // Pastikan user target ada
//     $user = User::where('family_tree_id', $targetFamilyTreeId)->first();
//     if (!$user) {
//         return response()->json(['message' => 'User dengan family_tree_id tersebut tidak ditemukan'], 404);
//     }

//     // Pastikan credential cocok (boleh edit hanya dirinya sendiri)
//     if ($targetFamilyTreeId !== $credentialTreeId) {
//         return response()->json(['message' => 'Anda tidak memiliki izin untuk mengedit data ini'], 403);
//     }

//     // Ambil semua input kecuali key credential
//     $updates = $request->except(['family_tree_id', 'credential_family_tree_id']);

//     // Update data user
//     $user->update($updates);

//     return response()->json([
//         'message' => 'Data berhasil diperbarui',
//         'data' => $user
//     ]);
// }

public function updateProfileWithCredential(Request $request)
{
    $request->validate([
        'family_tree_id' => 'required|string',           // data target yang mau diubah
        'credential_family_tree_id' => 'required|string' // siapa yang ngedit
    ]);

    $targetTreeId = $request->family_tree_id;
    $credentialTreeId = $request->credential_family_tree_id;

    // Cek apakah data target ada
    $user = User::where('family_tree_id', $targetTreeId)->first();
    if (!$user) {
        return response()->json(['message' => 'User dengan family_tree_id tersebut tidak ditemukan'], 404);
    }

    // Cek izin akses:
    // 1. Kalau credential sama → boleh edit diri sendiri
    // 2. Kalau credential adalah parent langsung dari target → juga boleh
    $isParent = str_starts_with($targetTreeId, $credentialTreeId . '.')
                && substr_count($targetTreeId, '.') === substr_count($credentialTreeId, '.') + 1;

    if ($targetTreeId !== $credentialTreeId && !$isParent) {
        return response()->json(['message' => 'Anda tidak memiliki izin untuk mengedit data ini'], 403);
    }

    // Ambil semua input kecuali key credential
    $updates = $request->except(['family_tree_id', 'credential_family_tree_id']);

    // Update data user
    $user->update($updates);

    return response()->json([
        'message' => 'Data berhasil diperbarui',
        'data' => $user
    ]);
}


    public function searchByFamilyTreeId(Request $request)
{
    $search = $request->query('family_tree_id');

    if (!$search) {
        return response()->json(['message' => 'Parameter family_tree_id wajib diisi'], 400);
    }

    // Cari semua user yang family_tree_id-nya mengandung teks pencarian
    $users = User::where('family_tree_id', 'like', '%' . $search . '%')->get();

    if ($users->isEmpty()) {
        return response()->json(['message' => 'Tidak ada user dengan family_tree_id tersebut'], 404);
    }

    // Tambahkan relasi anak & pasangan (recursive)
    $results = [];
    foreach ($users as $user) {
        $results[] = $this->loadChildrenAndSpouseRecursive($user);
    }

    return response()->json([
        'keyword' => $search,
        'results' => $results
    ]);
}


    public function countFamilyMembers($rootId)
{
    // Cari user utama berdasarkan family_tree_id (misal '1')
    $mainUser = User::where('family_tree_id', $rootId)->first();

    if (!$mainUser) {
        return response()->json(['message' => 'Family tree ID tidak ditemukan'], 404);
    }

    // Hitung semua anggota keluarga yang punya family_tree_id diawali dengan rootId + titik
    $count = User::where('family_tree_id', 'like', $rootId . '%')->count();

    return response()->json([
        'root_family_tree_id' => $rootId,
        'root_name' => $mainUser->full_name,
        'root_avatar' => $mainUser->avatar,
        'total_members' => $count,
    ]);
}


    private function loadChildrenAndSpouseRecursive($user)
    {
        // Ambil semua anak
        $children = User::where('parent_id', $user->user_id)->get();

        $childrenWithDescendants = [];
        foreach ($children as $child) {
            $childrenWithDescendants[] = $this->loadChildrenAndSpouseRecursive($child);
        }

        // Ambil pasangan (dua arah)
        $spouses = Spouse::where('primary_child_id', $user->user_id)
            ->orWhere('related_user_id', $user->user_id)
            ->get();

        $spouseList = [];

        foreach ($spouses as $spouse) {
            $spouseUserId = $spouse->primary_child_id == $user->user_id
                ? $spouse->related_user_id
                : $spouse->primary_child_id;

            $spouseUser = User::find($spouseUserId);

            if ($spouseUser) {
                $spouseList[] = [
                    'user_id' => $spouseUser->user_id,
                    'full_name' => $spouseUser->full_name,
                    'address' => $spouseUser->address,
                    'birth_year' => $spouseUser->birth_year,
                ];
            }
        }

        return [
            'user_id' => $user->user_id,
            'family_tree_id' => $user->family_tree_id,
            'full_name' => $user->full_name,
            'address' => $user->address,
            'birth_year' => $user->birth_year,
            'spouse' => $spouseList,
            'children' => $childrenWithDescendants,
        ];
    }

    public function getById($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    return response()->json([
        'user_id' => $user->user_id,
        'family_tree_id' => $user->family_tree_id,
        'full_name' => $user->full_name,
        'address' => $user->address,
        'birth_year' => $user->birth_year,
        'avatar' => $user->avatar,
        'parent_id' => $user->parent_id,
    ]);
}


    public function login(Request $request)
    {
        $validated = $request->validate([
            'family_tree_id' => 'required|string',
            'birth_year' => 'nullable|string'
        ]);

        $user = User::where('family_tree_id', $validated['family_tree_id'])
            ->when($validated['birth_year'] ?? false, fn($q) => $q->where('birth_year', $validated['birth_year']))
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Silsilah NIK atau tanggal lahir tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Login berhasil',
            'data' => $user
        ]);
    }

    public function storeWithoutTree(Request $request)
    {
        $validated = $request->validate([
            'family_tree_id' => 'nullable',
            'full_name' => 'required|string',
            'address' => 'nullable|string',
            'birth_year' => 'nullable|string',
            'parent_id' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil dibuat tanpa family tree wajib!',
            'data' => $user
        ]);
    }

public function exportExcel()
{
    $users = User::all(['user_id', 'family_tree_id', 'full_name', 'address', 'birth_year']);

    $dataArray = $users->map(function ($user) {
        return [
            'User ID' => $user->user_id,
            'Family Tree ID' => $user->family_tree_id,
            'Full Name' => $user->full_name,
            'Address' => $user->address,
            'Birth Year' => $user->birth_year,
        ];
    })->toArray();

    $export = new class($dataArray) implements FromArray, WithHeadings {
        protected $data;
        public function __construct(array $data) { $this->data = $data; }
        public function array(): array { return $this->data; }
        public function headings(): array {
            return ['User ID', 'Family Tree ID', 'Full Name', 'Address', 'Birth Year'];
        }
    };

    $fileName = 'users_export_' . now()->format('Y-m-d_His') . '.xlsx';
    return Excel::download($export, $fileName);
}


}