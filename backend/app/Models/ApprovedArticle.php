<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovedArticle extends Model
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
        'ai_generated',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'ai_generated' => 'boolean',
    ];

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
        return $this->belongsTo(RewrittenArticle::class, 'original_article_id');
    }

    public function getStatusBadgeClassAttribute()
    {
        return [
            'published' => 'bg-green-100 text-green-800',
            'unpublished' => 'bg-red-100 text-red-800',
        ][$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFormattedContentAttribute()
    {
        return nl2br($this->content);
    }

    public function getIsPublishedAttribute()
    {
        return $this->status === 'published';
    }

    /**
     * Scope a query to only include published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope a query to only include AI generated articles
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('ai_generated', true);
    }

    /**
     * Trả về URL đầy đủ của ảnh đại diện
     */
    public function getFeaturedImageUrlAttribute()
    {
        if (!$this->featured_image) {
            return null;
        }
        
        // If it's already a full URL, return it
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }
        
        // Remove any leading slash for consistency
        $path = ltrim($this->featured_image, '/');
        
        // First check if the path starts with storage/
        if (strpos($path, 'storage/') === 0) {
            $publicPath = $path;
            
            // Check if file exists in public path
            if (file_exists(public_path($publicPath))) {
                return asset($publicPath);
            }
            
            // If not, try using the path without 'storage/'
            $storagePath = substr($path, 8); // Remove 'storage/'
        } else {
            // If path doesn't start with storage/, use as is for storage
            $storagePath = $path;
            $publicPath = 'storage/' . $path;
            
            // Check if file exists in public path
            if (file_exists(public_path($publicPath))) {
                return asset($publicPath);
            }
        }
        
        // Double check if file exists in storage
        if (\Storage::disk('public')->exists($storagePath)) {
            return asset('storage/' . $storagePath);
        }
        
        // Default fallback (might not work, but it's our best guess)
        return asset('storage/' . $path);
    }
} 