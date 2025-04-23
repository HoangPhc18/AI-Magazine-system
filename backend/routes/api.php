<?php

use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\AISettingsController;
use App\Http\Controllers\Api\Admin\RewrittenArticleController;
use App\Http\Controllers\Api\Admin\ApprovedArticleController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\KeywordRewriteController;
use App\Http\Controllers\Api\Admin\FacebookRewriteController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController as PublicUserController;
use App\Http\Controllers\Api\AISettingsController as PublicAISettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\FacebookPostController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\Admin\WebsiteConfigController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public article and category routes
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/latest', [ArticleController::class, 'latest']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
Route::get('/articles/search', [ArticleController::class, 'search']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/articles', [CategoryController::class, 'articles']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // User profile routes
    Route::get('/users/profile', [UserProfileController::class, 'profile']);
    Route::put('/users/profile', [UserProfileController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserProfileController::class, 'changePassword']);

    // AI Settings routes
    Route::get('ai-settings', [PublicAISettingsController::class, 'index']);
    Route::put('ai-settings', [PublicAISettingsController::class, 'update']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    // User Management
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}/status', [UserController::class, 'updateStatus']);

    // Rewritten Articles Management
    Route::get('/articles/rewritten', [RewrittenArticleController::class, 'index']);
    Route::get('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'show']);
    Route::put('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'update']);
    Route::post('/articles/rewritten/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'approve']);
    Route::post('/articles/rewritten/{rewrittenArticle}/reject', [RewrittenArticleController::class, 'reject']);
    Route::delete('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'destroy']);

    // Facebook Rewrite Management
    Route::get('/facebook/random-post', [FacebookRewriteController::class, 'getRandomPost']);
    Route::post('/facebook/rewrite', [FacebookRewriteController::class, 'rewritePost']);
    Route::post('/facebook/create-article', [FacebookRewriteController::class, 'createRewrittenArticle']);
    Route::post('/facebook/process-batch', [FacebookRewriteController::class, 'processBatch']);

    // Approved Articles Management
    Route::get('/articles/approved', [ApprovedArticleController::class, 'index']);
    Route::get('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'show']);
    Route::put('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'update']);
    Route::post('/articles/approved/{approvedArticle}/archive', [ApprovedArticleController::class, 'archive']);
    Route::delete('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'destroy']);

    // Keyword Rewrite Management
    Route::get('/keyword-rewrites', [KeywordRewriteController::class, 'index']);
    Route::post('/keyword-rewrites', [KeywordRewriteController::class, 'store']);
    Route::get('/keyword-rewrites/{keywordRewrite}', [KeywordRewriteController::class, 'show']);
    Route::delete('/keyword-rewrites/{keywordRewrite}', [KeywordRewriteController::class, 'destroy']);

    // Categories Management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    // AI Settings management
    Route::get('/ai-settings', [AISettingsController::class, 'index']);
    Route::put('/ai-settings', [AISettingsController::class, 'update']);
    Route::post('/ai-settings/test-connection', [AISettingsController::class, 'testConnection']);
    Route::post('/ai-settings/reset', [AISettingsController::class, 'resetSettings']);

    // Website Config Management
    Route::get('/website-config', [WebsiteConfigController::class, 'index']);
    Route::get('/website-config/{group}', [WebsiteConfigController::class, 'getGroup']);
    Route::put('/website-config/general', [WebsiteConfigController::class, 'updateGeneral']);
    Route::put('/website-config/seo', [WebsiteConfigController::class, 'updateSeo']);
    Route::put('/website-config/social', [WebsiteConfigController::class, 'updateSocial']);
    Route::post('/website-config/generate-robots', [WebsiteConfigController::class, 'generateRobotsTxt']);
    Route::post('/website-config/generate-sitemap', [WebsiteConfigController::class, 'generateSitemap']);
});

// Public callback for keyword rewrite
Route::post('/admin/keyword-rewrites/callback', [KeywordRewriteController::class, 'callback']);
// Thêm route thay thế không có admin prefix cho callback
Route::post('/keyword-rewrites/callback', [KeywordRewriteController::class, 'callback']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Article routes
Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('/{id}', [ArticleController::class, 'show']);
    Route::put('/{id}', [ArticleController::class, 'update']);
    Route::delete('/{id}', [ArticleController::class, 'destroy']);
    
    // Additional article routes
    Route::get('/unprocessed', [ArticleController::class, 'getUnprocessedArticles']);
    Route::post('/{id}/ai-content', [ArticleController::class, 'updateAiContent']);
    Route::post('/import', [ArticleController::class, 'importFromScraper']);
});

// Facebook Post Routes
Route::prefix('facebook-posts')->group(function () {
    Route::get('/', [FacebookPostController::class, 'index']);
    Route::post('/scrape', [FacebookPostController::class, 'scrape']);
    Route::post('/scrape-api', [FacebookPostController::class, 'scrapeFromApi'])->name('api.facebook-posts.scrape-api');
    Route::get('/jobs', [FacebookPostController::class, 'getAllJobs']);
    Route::get('/jobs/{jobId}', [FacebookPostController::class, 'getJobStatus']);
    Route::get('/{id}', [FacebookPostController::class, 'show']);
    Route::patch('/{id}/status', [FacebookPostController::class, 'updateStatus']);
    Route::delete('/{id}', [FacebookPostController::class, 'destroy']);
}); 