<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditHistory extends Model
{
    protected $fillable = [
        'rewritten_article_id',
        'edited_by',
        'previous_content'
    ];

    public function rewrittenArticle(): BelongsTo
    {
        return $this->belongsTo(RewrittenArticle::class, 'rewritten_article_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
} 