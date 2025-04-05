<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeywordRewrite extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyword',
        'source_url',
        'source_title',
        'source_content',
        'rewritten_content',
        'created_by',
        'status',
        'error_message',
    ];

    /**
     * Get the user who created this keyword rewrite.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 