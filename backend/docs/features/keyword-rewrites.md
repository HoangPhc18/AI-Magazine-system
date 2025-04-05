# Keyword Rewrite Feature

## Overview

The Keyword Rewrite feature is a tool that allows administrators to generate new articles based on keywords. The system uses the keyword to search for relevant articles on Google News or other sources, then processes and rewrites the content to create a new article.

## Core Components

### Models

- `KeywordRewrite`: Represents a keyword rewrite request, storing the original keyword, source information, rewritten content, and processing status.

### Controllers

- `App\Http\Controllers\Admin\KeywordRewriteController`: Handles web routes for administrative keyword rewrite operations.
- `App\Http\Controllers\Api\Admin\KeywordRewriteController`: Handles API routes for keyword rewrite operations and the callback endpoint.

### Views

- `admin/keyword-rewrites/index.blade.php`: Lists all keyword rewrite requests.
- `admin/keyword-rewrites/create.blade.php`: Form to create a new keyword rewrite request.
- `admin/keyword-rewrites/show.blade.php`: Displays details for a specific keyword rewrite request.

### Routes

#### Web Routes

```php
// Admin routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    // Keyword Rewrite routes
    Route::resource('keyword-rewrites', KeywordRewriteController::class)->except(['edit', 'update']);
    Route::post('keyword-rewrites/{keywordRewrite}/retry', [KeywordRewriteController::class, 'retry'])->name('keyword-rewrites.retry');
    Route::get('keyword-rewrites/{keywordRewrite}/convert', [KeywordRewriteController::class, 'convert'])->name('keyword-rewrites.convert');
});
```

#### API Routes

```php
// Admin routes within middleware group
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Keyword Rewrite Management
    Route::get('/keyword-rewrites', [KeywordRewriteController::class, 'index']);
    Route::post('/keyword-rewrites', [KeywordRewriteController::class, 'store']);
    Route::get('/keyword-rewrites/{keywordRewrite}', [KeywordRewriteController::class, 'show']);
    Route::delete('/keyword-rewrites/{keywordRewrite}', [KeywordRewriteController::class, 'destroy']);
});

// Public callback for keyword rewrite
Route::post('/admin/keyword-rewrites/callback', [KeywordRewriteController::class, 'callback']);
```

## Database Schema

```php
Schema::create('keyword_rewrites', function (Blueprint $table) {
    $table->id();
    $table->string('keyword');
    $table->string('source_url')->nullable();
    $table->string('source_title')->nullable();
    $table->longText('source_content')->nullable();
    $table->longText('rewritten_content')->nullable();
    $table->unsignedBigInteger('created_by');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->text('error_message')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('created_by')->references('id')->on('users');
});
```

## Process Flow

1. **Create Request**: Admin enters a keyword in the create form.
2. **Process Request**: The system sends the keyword to the AI service for processing.
3. **Callback**: The AI service processes the request and calls back to the `/api/admin/keyword-rewrites/callback` endpoint with the results.
4. **View Results**: Admin can view the results and choose to convert the rewritten content to a regular article.

## Feature Functions

### Creating a Keyword Rewrite Request

1. Navigate to Admin > Keyword Rewrites > Create New
2. Enter a keyword and submit the form
3. The system will process the request and update the status accordingly

### Converting a Completed Rewrite to an Article

1. Navigate to Admin > Keyword Rewrites
2. Find a completed rewrite and click the "Convert" button
3. The system will create a new rewritten article from the keyword rewrite content
4. You will be redirected to the article edit form for final adjustments

### Retrying a Failed Rewrite

1. Navigate to Admin > Keyword Rewrites
2. Find a failed rewrite and click the "Retry" button
3. The system will resubmit the request to the AI service

## AI Service Interaction

The feature interacts with the AI service through the following endpoints:

- **Process Endpoint**: `{AI_SERVICE_URL}/api/keyword_rewrite/process`
  - Payload: `{ "keyword": "example", "rewrite_id": 1, "callback_url": "..." }`

- **Callback Endpoint**: `/api/admin/keyword-rewrites/callback`
  - Expected payload: 
    ```json
    {
      "rewrite_id": 1,
      "status": "completed",
      "source_url": "https://example.com",
      "source_title": "Example Title",
      "source_content": "Original content...",
      "rewritten_content": "Rewritten content...",
      "error_message": null
    }
    ``` 