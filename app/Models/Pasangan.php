<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasangan extends Model
{
    use HasFactory;

    protected $primaryKey = 'pasangan_id';

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
}
