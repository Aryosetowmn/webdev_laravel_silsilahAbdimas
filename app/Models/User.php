<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'id_silsilah',
        'id_parent',
        'name',
        'tempat_tinggal',
        'tanggal_lahir',
        'avatar',
    ];

    // Relasi ke orang tua
    public function parent() {
        return $this->belongsTo(User::class, 'id_parent');
    }

    // Relasi ke pasangan
    // public function pasangan() {
    //     return $this->belongsTo(User::class, 'id_pasangan');
    // }

    // Relasi ke anak-anak
    public function children() {
        return $this->hasMany(User::class, 'id_parent');
    }
}
