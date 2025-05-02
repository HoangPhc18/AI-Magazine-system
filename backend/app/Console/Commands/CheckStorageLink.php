<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class CheckStorageLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-storage-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và sửa chữa symbolic link cho storage nếu cần thiết';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $publicStoragePath = public_path('storage');
        $storageAppPublicPath = storage_path('app/public');
        
        $this->info('Kiểm tra symbolic link cho storage...');
        
        // Kiểm tra xem thư mục public/storage có tồn tại không
        if (!File::exists($publicStoragePath)) {
            $this->warn('Không tìm thấy thư mục public/storage!');
            $this->createLink();
            return;
        }
        
        // Kiểm tra xem thư mục đích có tồn tại không
        if (!File::exists($storageAppPublicPath)) {
            $this->warn('Thư mục storage/app/public không tồn tại!');
            File::makeDirectory($storageAppPublicPath, 0755, true);
            $this->info('Đã tạo thư mục storage/app/public');
            $this->createLink();
            return;
        }
        
        // Kiểm tra xem thư mục public/storage có chứa nội dung giống với storage/app/public không
        // Nếu không, giả định rằng symbolic link không hoạt động
        $testFile = 'storage-link-test-' . time() . '.txt';
        $testContent = 'Test storage link: ' . date('Y-m-d H:i:s');
        
        try {
            // Tạo file test trong storage/app/public
            File::put($storageAppPublicPath . '/' . $testFile, $testContent);
            
            // Kiểm tra xem file có thể đọc từ public/storage không
            $publicTestPath = $publicStoragePath . '/' . $testFile;
            
            if (!File::exists($publicTestPath) || File::get($publicTestPath) !== $testContent) {
                $this->warn('Symbolic link không hoạt động chính xác!');
                
                // Xóa thư mục public/storage nếu tồn tại
                if (File::exists($publicStoragePath)) {
                    if (is_dir($publicStoragePath)) {
                        File::deleteDirectory($publicStoragePath);
                    } else {
                        File::delete($publicStoragePath);
                    }
                }
                
                $this->createLink();
            } else {
                $this->info('Symbolic link cho storage đang hoạt động chính xác.');
            }
            
            // Xóa file test
            File::delete($storageAppPublicPath . '/' . $testFile);
        } catch (\Exception $e) {
            $this->error('Lỗi khi kiểm tra symbolic link: ' . $e->getMessage());
            
            // Nếu xảy ra lỗi, thử tạo lại symbolic link
            $this->createLink();
        }
        
        // Kiểm tra quyền truy cập
        $this->checkPermissions();
    }
    
    /**
     * Tạo symbolic link giữa public/storage và storage/app/public
     */
    private function createLink()
    {
        $this->info('Tạo symbolic link mới...');
        Artisan::call('storage:link');
        $this->info(Artisan::output());
    }
    
    /**
     * Kiểm tra quyền truy cập của thư mục storage
     */
    private function checkPermissions()
    {
        $storageAppPublicPath = storage_path('app/public');
        
        // Kiểm tra xem thư mục storage/app/public có thể ghi không
        if (!is_writable($storageAppPublicPath)) {
            $this->warn('Thư mục storage/app/public không có quyền ghi!');
            $this->warn('Hãy đảm bảo web server có quyền ghi vào thư mục này.');
            
            // Trên Windows, không có chmod như Linux, nhưng bạn vẫn có thể đưa ra hướng dẫn
            if (PHP_OS_FAMILY === 'Windows') {
                $this->info('Trên Windows, bạn cần vào Properties của thư mục, chọn tab Security, và đảm bảo IIS_IUSRS hoặc NETWORK SERVICE có quyền Write.');
            } else {
                $this->info('Có thể chạy: chmod -R 775 ' . $storageAppPublicPath);
            }
        }
    }
}
