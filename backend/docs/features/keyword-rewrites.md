# Keyword Rewrite Feature

## Overview

The Keyword Rewrite feature is a tool that allows administrators to generate new articles based on keywords. The system uses the keyword to search for relevant articles on Google News or other sources, then processes and rewrites the content to create new articles.

## Core Components

### Models

- `KeywordRewrite`: Represents a keyword rewrite request, storing the original keyword, source information, rewritten content, multiple articles data, and processing status.

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
    $table->longText('all_articles')->nullable(); // JSON containing multiple article data
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
3. **AI Processing**: The AI service searches for and processes up to 3 different articles based on the keyword.
4. **Callback**: The AI service processes the request and calls back to the `/api/admin/keyword-rewrites/callback` endpoint with the results, including multiple article data.
5. **View Results**: Admin can view the results and choose to convert the rewritten content into multiple articles.

## Multi-Article Feature

The system now generates multiple articles (up to 3) for each keyword:

1. When a keyword is submitted, the AI service searches for multiple relevant articles on Google News.
2. Each article is processed independently, extracting the source content and rewriting it.
3. All successful articles are returned to the backend in the `all_articles` field.
4. When the admin converts the keyword rewrite, all successful articles are converted into separate RewrittenArticles.
5. Each article maintains its own source information and attribution.

## Feature Functions

### Creating a Keyword Rewrite Request

1. Navigate to Admin > Keyword Rewrites > Create New
2. Enter a keyword and submit the form
3. The system will process the request and update the status accordingly

### Converting a Completed Rewrite to Multiple Articles

1. Navigate to Admin > Keyword Rewrites
2. Find a completed rewrite and click the "Convert" button
3. The system will create multiple new rewritten articles from the keyword rewrite content (up to 3 articles)
4. You will be redirected to the first article's edit form for final adjustments

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
      "error_message": null,
      "all_articles": [
        {
          "index": 0,
          "status": "completed",
          "source_url": "https://example1.com",
          "source_title": "Example Title 1",
          "source_content": "Original content 1...",
          "rewritten_content": "Rewritten content 1..."
        },
        {
          "index": 1,
          "status": "completed",
          "source_url": "https://example2.com",
          "source_title": "Example Title 2",
          "source_content": "Original content 2...",
          "rewritten_content": "Rewritten content 2..."
        },
        {
          "index": 2,
          "status": "completed",
          "source_url": "https://example3.com",
          "source_title": "Example Title 3",
          "source_content": "Original content 3...",
          "rewritten_content": "Rewritten content 3..."
        }
      ]
    }
    ```

## Source URL Handling

The keyword rewrite feature now preserves the source URL throughout the article lifecycle:

1. When the AI service scrapes content based on a keyword, it captures the source URL for each article and sends it back to the backend via the callback mechanism.

2. During keyword rewrite conversion to RewrittenArticles:
   - For each article, an Article record is created with the source_url from that article
   - Each RewrittenArticle links to its original Article via original_article_id

3. When approving a RewrittenArticle and moving it to ApprovedArticle:
   - The source_url is transferred from the original Article to the ApprovedArticle
   - This preserves the source attribution throughout the content lifecycle

4. UI displays:
   - Source URLs are displayed in both RewrittenArticle and ApprovedArticle index views
   - Clickable links are provided to navigate to the original source
   - Special handling for Facebook URLs with the Facebook logo for better visual identification

This ensures proper attribution of content and allows administrators to quickly access the original source of content when needed. 