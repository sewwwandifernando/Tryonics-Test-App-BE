<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all posts that belong to this category.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'category_post')
                    ->withTimestamps();
    }
}