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
        'featured_image_id',
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
    
    /**
     * Get the featured image media object
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

    /**
     * Xử lý nội dung để cải thiện hiển thị hình ảnh
     */
    public function getProcessedContentAttribute()
    {
        $content = $this->content;
        
        // Xử lý các đoạn văn bản trước khi xử lý hình ảnh
        // Chuyển đổi hai khoảng trắng xuống dòng liên tiếp thành thẻ đoạn văn mới
        $content = preg_replace('/(\r\n|\n){2,}/', '</p><p>', $content);
        
        // Chuyển đổi một khoảng trắng xuống dòng thành thẻ <br>
        $content = preg_replace('/(\r\n|\n)/', '<br>', $content);
        
        // Bọc toàn bộ nội dung trong thẻ p nếu chưa có
        if (!preg_match('/^<p>/', $content)) {
            $content = '<p>' . $content . '</p>';
        }
        
        // Thêm class img-fluid cho tất cả các hình ảnh
        $content = preg_replace('/<img(.*?)>/i', '<img$1 class="img-fluid">', $content);
        
        // Loại bỏ các thuộc tính width/height cứng có thể gây vỡ layout
        $content = preg_replace('/(width|height)=["\']\d+["\']/i', '', $content);
        
        // Tải thông tin của tất cả media đã liên kết với bài viết
        // Tạo mảng associative với url được tính thủ công
        $mediaItems = $this->media()->get();
        \Log::info('Processing content with media for article ' . $this->id, [
            'media_count' => $mediaItems->count(),
            'media_ids' => $mediaItems->pluck('id')->toArray(),
        ]);
        
        $mediaMap = [];
        
        foreach ($mediaItems as $media) {
            $mediaMap[$media->id] = [
                'id' => $media->id,
                'name' => $media->name,
                'file_path' => $media->file_path,
                'url' => asset('storage/' . $media->file_path),
            ];
        }
        
        // Debug để kiểm tra thông tin media
        \Log::debug('Media items for article ' . $this->id . ':', $mediaMap);
        
        // Đảm bảo các thẻ img có data-media-id đều có src chính xác
        if (count($mediaMap) > 0) {
            // Xử lý bằng regex để thay thế các thẻ img có data-media-id
            // với url chính xác từ media đã liên kết
            $content = preg_replace_callback(
                '/<img([^>]*)data-media-id=["\'](\d+)["\']([^>]*)>/i',
                function($matches) use ($mediaMap) {
                    $mediaId = $matches[2];
                    if (isset($mediaMap[$mediaId])) {
                        // Đảm bảo src luôn được cập nhật đúng
                        $url = $mediaMap[$mediaId]['url'];
                        // Loại bỏ src cũ nếu có
                        $attributes = preg_replace('/src=["\'][^"\']*["\']/i', '', $matches[1] . $matches[3]);
                        // Thêm src mới
                        return "<img{$attributes} data-media-id=\"{$mediaId}\" src=\"{$url}\" alt=\"{$mediaMap[$mediaId]['name']}\">";
                    }
                    // Trả về nguyên bản nếu không tìm thấy media
                    \Log::warning('Media ID ' . $mediaId . ' not found for article ' . $this->id);
                    return $matches[0];
                },
                $content
            );
        }
        
        // Bọc hình ảnh trong thẻ div khi chúng nằm trong một thẻ p
        $content = preg_replace('/<p>\s*<img(.*?)>\s*<\/p>/i', '<div class="image-wrapper full-width"><img$1></div>', $content);
        
        // Xử lý các tiêu đề
        $content = preg_replace('/\*\*(.*?)\*\*/', '<h3>$1</h3>', $content);
        
        // Xử lý nếu đã có style float, bọc trong container phù hợp
        $content = preg_replace('/<img(.*?)style="[^"]*float:\s*(left|right)[^"]*"(.*?)>/i', '<div class="image-container"><img$1style="float:$2"$3></div>', $content);
        
        // Thay thế phương pháp sử dụng DOM để xử lý các img đứng riêng lẻ
        $dom = new \DOMDocument();
        
        // Tắt báo lỗi để tránh warnings với HTML không hợp lệ
        libxml_use_internal_errors(true);
        
        // Thêm prefix để tránh lỗi với HTML5 tags
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        
        // Lấy tất cả thẻ img
        $images = $dom->getElementsByTagName('img');
        
        // Mảng chứa các img cần bọc
        $imagesToWrap = [];
        
        // Chỉ xử lý khi có ít nhất một img
        if ($images->length > 0) {
            // Duyệt từ cuối lên đầu để việc thay thế không ảnh hưởng đến các phần tử chưa xử lý
            for ($i = $images->length - 1; $i >= 0; $i--) {
                $img = $images->item($i);
                $parent = $img->parentNode;
                
                // Thêm class full-width cho img để kéo dãn nếu quá nhỏ
                if ($img->hasAttribute('class')) {
                    $classes = $img->getAttribute('class');
                    if (strpos($classes, 'full-width') === false) {
                        $img->setAttribute('class', $classes . ' full-width');
                    }
                } else {
                    $img->setAttribute('class', 'full-width');
                }
                
                // Kiểm tra nếu img có data-media-id nhưng không có src hoặc src không đúng
                if ($img->hasAttribute('data-media-id') && (!$img->hasAttribute('src') || $img->getAttribute('src') === '')) {
                    $mediaId = $img->getAttribute('data-media-id');
                    if (isset($mediaMap[$mediaId])) {
                        $img->setAttribute('src', $mediaMap[$mediaId]['url']);
                        $img->setAttribute('alt', $mediaMap[$mediaId]['name']);
                    }
                }
                
                // Nếu img không nằm trong p hoặc div, bọc nó lại
                if ($parent->nodeName !== 'p' && $parent->nodeName !== 'div' && $parent->nodeName !== 'figure') {
                    $imagesToWrap[] = $img;
                }
            }
            
            // Bọc các img đã xác định
            foreach ($imagesToWrap as $img) {
                $wrapper = $dom->createElement('div');
                $wrapper->setAttribute('class', 'image-wrapper full-width');
                
                $parent = $img->parentNode;
                $clone = $img->cloneNode(true);
                
                // Thêm wrapper vào vị trí của img
                $parent->replaceChild($wrapper, $img);
                $wrapper->appendChild($clone);
            }
            
            // Lấy nội dung HTML đã cập nhật
            $content = $dom->saveHTML();
            
            // Loại bỏ prefix XML
            $content = preg_replace('/^<!DOCTYPE.+?>/', '', $content);
            $content = preg_replace('/<\?xml.+?\?>/', '', $content);
            $content = preg_replace('/<html><body>|<\/body><\/html>/', '', $content);
        }
        
        // Đảm bảo các hình ảnh không vượt quá width và có style đầy đủ
        $content = preg_replace('/<img([^>]+)>/', '<img$1 style="max-width:100%; width:100%; height:auto; object-fit:cover;">', $content);
        
        return $content;
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
        // First check if we have a media relationship
        if ($this->featuredImage) {
            return $this->featuredImage->url;
        }
        
        // Fallback to the old implementation for backward compatibility
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