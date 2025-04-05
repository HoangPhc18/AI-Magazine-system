<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AISettingsController;
use App\Http\Controllers\Admin\RewrittenArticleController;
use App\Http\Controllers\Admin\AiSettingController;
use App\Http\Controllers\Admin\ApprovedArticleController;
use App\Http\Controllers\Admin\KeywordRewriteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Public routes - Frontend
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/articles', [HomeController::class, 'allArticles'])->name('articles.all');
Route::get('/articles/{slug}', [HomeController::class, 'showArticle'])->name('articles.show');
Route::get('/category/{slug}', [HomeController::class, 'categoryArticles'])->name('articles.category');
Route::get('/search', [HomeController::class, 'search'])->name('articles.search');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Registration routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// Admin routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // User management
    Route::resource('users', UserController::class);
    
    // Category management
    Route::resource('categories', CategoryController::class);
    
    // Article management
    Route::resource('articles', ArticleController::class);
    
    // Rewritten Article routes
    Route::resource('rewritten-articles', RewrittenArticleController::class)->except(['create', 'store']);
    Route::post('rewritten-articles/rewrite', [RewrittenArticleController::class, 'rewriteArticle'])->name('rewritten-articles.rewrite-process');
    Route::get('rewritten-articles/rewrite/{originalArticleId?}', [RewrittenArticleController::class, 'rewriteForm'])->name('rewritten-articles.rewrite-form');
    Route::get('rewritten-articles/ai-rewrite/{rewrittenArticle}', [RewrittenArticleController::class, 'aiRewriteForm'])->name('rewritten-articles.ai-rewrite-form');
    Route::post('rewritten-articles/ai-rewrite/{rewrittenArticle}', [RewrittenArticleController::class, 'aiRewrite'])->name('rewritten-articles.ai-rewrite');
    Route::patch('rewritten-articles/{rewrittenArticle}/reject', [RewrittenArticleController::class, 'reject'])->name('rewritten-articles.reject');
    Route::get('rewritten-articles/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'approve'])->name('rewritten-articles.approve');
    Route::post('rewritten-articles/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'storeApproved'])->name('rewritten-articles.store-approved');
    
    // Keyword Rewrite routes
    Route::resource('keyword-rewrites', KeywordRewriteController::class)->except(['edit', 'update']);
    Route::post('keyword-rewrites/{keywordRewrite}/retry', [KeywordRewriteController::class, 'retry'])->name('keyword-rewrites.retry');
    Route::get('keyword-rewrites/{keywordRewrite}/convert', [KeywordRewriteController::class, 'convert'])->name('keyword-rewrites.convert');
    Route::post('keyword-rewrites/quick-process', [KeywordRewriteController::class, 'quickProcess'])->name('keyword-rewrites.quick-process');
    Route::get('keyword-rewrites/{keywordRewrite}/status', [KeywordRewriteController::class, 'getStatus'])->name('keyword-rewrites.status');
    
    // AI Settings
    Route::get('/ai-settings', [AISettingsController::class, 'index'])->name('ai-settings.index');
    Route::post('/ai-settings', [AISettingsController::class, 'update'])->name('ai-settings.update');
    Route::post('/ai-settings/test-connection', [AISettingsController::class, 'testConnection'])->name('ai-settings.test-connection');
    Route::post('/ai-settings/reset', [AISettingsController::class, 'reset'])->name('ai-settings.reset');

    // Approved Articles routes
    Route::resource('approved-articles', ApprovedArticleController::class)->except(['create']);
    Route::patch('approved-articles/{approvedArticle}/publish', [ApprovedArticleController::class, 'publish'])->name('approved-articles.publish');
    Route::patch('approved-articles/{approvedArticle}/unpublish', [ApprovedArticleController::class, 'unpublish'])->name('approved-articles.unpublish');
});
