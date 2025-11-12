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
            'birth_date' => 'nullable|date',
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
            'birth_date' => 'nullable|date',
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
            'birth_date' => $request->birth_date,
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
                    'birth_date' => $spouseUser->birth_date,
                ];
            }
        }

        return [
            'user_id' => $user->user_id,
            'family_tree_id' => $user->family_tree_id,
            'full_name' => $user->full_name,
            'address' => $user->address,
            'birth_date' => $user->birth_date,
            'spouse' => $spouseList,
            'children' => $childrenWithDescendants,
        ];
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'family_tree_id' => 'required|string',
            'birth_date' => 'nullable|date'
        ]);

        $user = User::where('family_tree_id', $validated['family_tree_id'])
            ->when($validated['birth_date'] ?? false, fn($q) => $q->where('birth_date', $validated['birth_date']))
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
            'birth_date' => 'nullable|date',
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
        // Ambil semua data user dari database
        $users = User::all(['user_id', 'family_tree_id', 'full_name', 'address', 'birth_date']);

        // Ubah ke array biasa
        $dataArray = $users->map(function ($user) {
            return [
                'User ID' => $user->user_id,
                'Family Tree ID' => $user->family_tree_id,
                'Full Name' => $user->full_name,
                'Address' => $user->address,
                'Birth Date' => $user->birth_date,
            ];
        })->toArray();

        // Buat export class inline (tanpa file terpisah)
        $export = new class($dataArray) implements FromArray, WithHeadings {
            protected $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return ['User ID', 'Family Tree ID', 'Full Name', 'Address', 'Birth Date'];
            }
        };

        $fileName = 'users_export_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download($export, $fileName);
    }
}


    