<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'source_name',
        'source_url',
        'source_icon',
        'published_at',
        'category',
        'meta_data',
        'is_processed',
        'is_ai_rewritten',
        'ai_rewritten_content',
        'featured_image_id',
        'subcategory_id'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'meta_data' => 'array',
        'is_processed' => 'boolean',
        'is_ai_rewritten' => 'boolean',
    ];

    /**
     * Ghi đè phương thức create để thêm log
     */
    public static function create(array $attributes = [])
    {
        // Log debug thông tin trước khi tạo
        Log::debug('Đang tạo bài viết mới:', [
            'title' => $attributes['title'] ?? null,
            'slug' => $attributes['slug'] ?? null,
            'source_url' => $attributes['source_url'] ?? null,
            'attrs' => array_keys($attributes)
        ]);
        
        try {
            $model = static::query()->create($attributes);
            Log::debug('Đã tạo bài viết thành công:', ['id' => $model->id]);
            return $model;
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo bài viết trong model:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category', 'name');
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function rewrittenArticles()
    {
        return $this->hasMany(RewrittenArticle::class, 'original_article_id');
    }

    /**
     * Get the featured image for the article
     */
    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * Get the article-related media
     */
    public function media()
    {
        return $this->belongsToMany(Media::class, 'article_media', 'article_id', 'media_id')
                    ->withTimestamps();
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeRewritten($query)
    {
        return $query->where('is_ai_rewritten', true);
    }

    /**
     * Scope để kiểm tra bài viết trùng lặp mà không quan tâm đến soft delete
     */
    public function scopeIncludingTrashed($query)
    {
        return $query->withTrashed();
    }

    /**
     * Tìm kiếm bài viết với slug, bao gồm cả đã bị xóa
     */
    public static function findBySlug($slug)
    {
        return static::withTrashed()->where('slug', $slug)->first();
    }

    /**
     * Tìm kiếm bài viết với URL nguồn, bao gồm cả đã bị xóa
     */
    public static function findBySourceUrl($url)
    {
        return static::withTrashed()->where('source_url', $url)->first();
    }

    public function markAsProcessed()
    {
        $this->update(['is_processed' => true]);
    }

    public function markAsRewritten($content)
    {
        $this->update([
            'is_ai_rewritten' => true,
            'ai_rewritten_content' => $content
        ]);
    }
} 