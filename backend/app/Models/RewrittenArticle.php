<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewrittenArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'user_id',
        'category_id',
        'original_article_id',
        'status',
        'ai_generated',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'ai_generated' => 'boolean',
    ];
    
    protected $attributes = [
        'status' => 'pending',
    ];
    
    // Đảm bảo soft delete đang hoạt động đúng bằng cách định nghĩa rõ cột deleted_at
    protected $dates = ['deleted_at', 'published_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function originalArticle()
    {
        return $this->belongsTo(ApprovedArticle::class, 'original_article_id');
    }

    public function getStatusBadgeClassAttribute()
    {
        return [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ][$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFormattedContentAttribute()
    {
        return nl2br($this->content);
    }
} 