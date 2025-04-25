<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'type', // 'image' or 'document'
        'user_id', // who uploaded it
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that uploaded the media
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the URL for the media file
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Check if the media is an image
     */
    public function getIsImageAttribute()
    {
        return $this->type === 'image';
    }

    /**
     * Check if the media is a document
     */
    public function getIsDocumentAttribute()
    {
        return $this->type === 'document';
    }
} 