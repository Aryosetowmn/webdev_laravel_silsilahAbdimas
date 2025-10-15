<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $primaryKey = 'admin_id';
    protected $fillable = ['id_silsilah', 'name'];
}
