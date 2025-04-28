<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleFeaturedImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'media_id',
        'position',
        'is_main',
        'alt_text',
        'caption',
    ];

    /**
     * Get the article that owns the featured image.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(ApprovedArticle::class, 'article_id');
    }

    /**
     * Get the media that owns the featured image.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Get the URL of the featured image.
     */
    public function getUrlAttribute(): string
    {
        return $this->media ? $this->media->url : '';
    }

    /**
     * Get the name of the featured image.
     */
    public function getNameAttribute(): string
    {
        return $this->media ? $this->media->name : '';
    }
} 