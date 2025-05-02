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
        // Đường dẫn chính thức qua storage link
        $url = asset('storage/' . $this->file_path);
        
        // Kiểm tra xem file có tồn tại không
        $publicPath = public_path('storage/' . $this->file_path);
        
        // Nếu không tồn tại trong public, thử truy cập trực tiếp thông qua storage_path
        if (!file_exists($publicPath) && config('app.env') === 'local') {
            // Chỉ sử dụng phương pháp này trong môi trường development
            $storagePath = storage_path('app/public/' . $this->file_path);
            if (file_exists($storagePath)) {
                // Tạo URL tạm thời với route động
                return route('media.direct', ['path' => $this->file_path]);
            }
        }
        
        return $url;
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

    /**
     * Get the thumbnail URL for the media file
     * For now, it returns the same URL as the full image
     */
    public function getThumbnailAttribute()
    {
        return $this->url;
    }
} 