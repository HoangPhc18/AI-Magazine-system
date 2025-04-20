<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

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
    }
}
