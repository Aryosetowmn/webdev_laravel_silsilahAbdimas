<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spouse extends Model
{
    use HasFactory;

    protected $primaryKey = 'spouse_id';

    protected $fillable = [
        'primary_child_id',
        'related_user_id'
    ];

        // Relasi ke user utama
    public function primaryUser()
    {
        return $this->belongsTo(User::class, 'primary_child_id', 'user_id');
    }

    // Relasi ke user yang jadi pasangan
    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id', 'user_id');
    }

    public function store(Request $request)
    {
        // Validasi awal: kedua user harus ada di tabel users
        $validated = $request->validate([
            'primary_child_id' => 'required|exists:users,user_id',
            'related_user_id' => 'required|exists:users,user_id',
        ]);

        // Ambil user utama (primary)
        $primaryUser = User::find($validated['primary_child_id']);

        // Cek apakah user utama punya id_silsilah
        if (empty($primaryUser->id_silsilah)) {
            return response()->json([
                'message' => 'User utama (primary_child_id) harus memiliki id_silsilah!'
            ], 422);
        }

        // Cegah pasangan duplikat dua arah (1–2 dan 2–1)
        // $existing = Pasangan::where(function ($q) use ($validated) {
        //     $q->where('primary_child_id', $validated['primary_child_id'])
        //       ->where('related_user_id', $validated['related_user_id']);
        // })
        // ->orWhere(function ($q) use ($validated) {
        //     $q->where('primary_child_id', $validated['related_user_id'])
        //       ->where('related_user_id', $validated['primary_child_id']);
        // })
        // ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Pasangan sudah terdaftar!',
                'data' => $existing
            ], 409);
        }

        // Simpan pasangan baru
        $pasangan = Pasangan::create($validated);

        return response()->json([
            'message' => 'Pasangan berhasil ditambahkan!',
            'data' => $pasangan
        ]);
    }
}
