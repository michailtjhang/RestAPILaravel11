<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class topup extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];

    // Menambahkan relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
