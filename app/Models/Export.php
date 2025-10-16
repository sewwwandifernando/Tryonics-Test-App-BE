<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'format',
        'file_path',
        'file_name',
        'record_count',
        'user_id',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}