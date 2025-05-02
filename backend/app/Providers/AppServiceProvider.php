<?php

namespace App\Providers;

use App\Services\WebsiteConfigService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set max execution time from .env
        $maxExecutionTime = env('MAX_EXECUTION_TIME', 60);
        ini_set('max_execution_time', $maxExecutionTime);
        
        // Ensure proper URL generation for assets
        if(config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        
        // Share website configuration with all views
        View::composer('*', function ($view) {
            $configService = app(WebsiteConfigService::class);
            $view->with('generalConfig', $configService->getByGroup(WebsiteConfigService::GROUP_GENERAL));
            $view->with('seoConfig', $configService->getByGroup(WebsiteConfigService::GROUP_SEO));
            $view->with('socialConfig', $configService->getByGroup(WebsiteConfigService::GROUP_SOCIAL));
            $view->with('uiConfig', $configService->getByGroup(WebsiteConfigService::GROUP_UI));
            $view->with('metadataConfig', $configService->getByGroup(WebsiteConfigService::GROUP_METADATA));
        });
        
        // Add validation rule for URL or localhost
        Validator::extend('url_or_local_host', function ($attribute, $value, $parameters, $validator) {
            if (empty($value)) {
                return true;
            }
            
            // Check if it's a valid URL
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return true;
            }
            
            // Check if it's a localhost URL
            if (preg_match('/^https?:\/\/localhost(:[0-9]+)?(\/.*)?$/', $value)) {
                return true;
            }
            
            // Check if it's just an IP with port
            if (preg_match('/^https?:\/\/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(:[0-9]+)?(\/.*)?$/', $value)) {
                return true;
            }
            
            return false;
        }, 'The :attribute must be a valid URL or localhost address.');
        
        // Đảm bảo thư mục storage/app/public tồn tại
        $this->ensureStorageDirectoriesExist();
    }
    
    /**
     * Đảm bảo các thư mục cần thiết trong storage tồn tại
     */
    private function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            storage_path('app/public'),
            storage_path('app/public/images'),
            storage_path('app/public/images/' . date('Y')),
            storage_path('app/public/images/' . date('Y') . '/' . date('m')),
            storage_path('app/public/documents'),
        ];
        
        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true, true);
            }
        }
    }
}
