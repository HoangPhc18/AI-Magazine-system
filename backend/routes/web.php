<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AISettingsController;
use App\Http\Controllers\Admin\RewrittenArticleController;
use App\Http\Controllers\Admin\AiSettingController;
use App\Http\Controllers\Admin\ApprovedArticleController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/articles/{id}', [HomeController::class, 'showArticle'])->name('articles.show');
Route::get('/category/{id}', [HomeController::class, 'categoryArticles'])->name('articles.category');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

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
    Route::resource('rewritten-articles', RewrittenArticleController::class);
    Route::post('rewritten-articles/rewrite', [RewrittenArticleController::class, 'rewriteArticle'])->name('rewritten-articles.rewrite-process');
    Route::get('rewritten-articles/rewrite/{originalArticleId?}', [RewrittenArticleController::class, 'rewriteForm'])->name('rewritten-articles.rewrite-form');
    Route::get('rewritten-articles/ai-rewrite/{rewrittenArticle}', [RewrittenArticleController::class, 'aiRewriteForm'])->name('rewritten-articles.ai-rewrite-form');
    Route::post('rewritten-articles/ai-rewrite/{rewrittenArticle}', [RewrittenArticleController::class, 'aiRewrite'])->name('rewritten-articles.ai-rewrite');
    Route::patch('rewritten-articles/{rewrittenArticle}/reject', [RewrittenArticleController::class, 'reject'])->name('rewritten-articles.reject');
    Route::get('rewritten-articles/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'approve'])->name('rewritten-articles.approve');
    Route::post('rewritten-articles/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'storeApproved'])->name('rewritten-articles.store-approved');
    
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
