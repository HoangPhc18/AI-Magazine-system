<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class CheckStorageLinkMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ kiểm tra khi đường dẫn chứa /storage/ và là hình ảnh
        if (str_contains($request->path(), 'storage') && $this->isMediaRequest($request)) {
            $this->ensureStorageLinkExists();
        }
        
        return $next($request);
    }
    
    /**
     * Kiểm tra xem request có phải cho media không
     */
    private function isMediaRequest(Request $request): bool
    {
        $path = $request->path();
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        
        foreach ($extensions as $ext) {
            if (str_ends_with(strtolower($path), '.' . $ext)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Đảm bảo storage link tồn tại
     */
    private function ensureStorageLinkExists(): void
    {
        $publicStoragePath = public_path('storage');
        
        // Nếu thư mục public/storage không tồn tại, tạo lại symbolic link
        if (!File::exists($publicStoragePath)) {
            // Ghi log về việc tạo lại storage link
            info('Storage link missing, recreating...');
            
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // Ghi log lỗi nhưng không ngăn chặn request
                info('Error recreating storage link: ' . $e->getMessage());
            }
        }
    }
}
