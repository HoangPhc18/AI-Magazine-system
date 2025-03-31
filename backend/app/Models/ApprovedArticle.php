<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovedArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category_id',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
} 