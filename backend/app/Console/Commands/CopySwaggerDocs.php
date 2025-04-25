<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopySwaggerDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Swagger API documentation files to public directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = base_path('docs');
        $destination = public_path('docs');

        if (!File::isDirectory($source)) {
            $this->error("Docs directory not found at {$source}");
            return 1;
        }

        // Create destination directory if it doesn't exist
        if (!File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
            $this->info("Created docs directory at {$destination}");
        }

        // Copy all files from source to destination
        File::copyDirectory($source, $destination);
        
        $this->info("Swagger documentation has been published to public/docs");
        
        return 0;
    }
} 