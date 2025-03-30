<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewrittenArticle extends Model
{
    protected $fillable = [
        'article_id',
        'rewritten_content',
        'status',
        'reviewed_by'
    ];

    public function originalArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function editHistory(): HasMany
    {
        return $this->hasMany(EditHistory::class, 'rewritten_article_id');
    }
} 