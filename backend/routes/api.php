<?php

use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\AiSettingController as AdminAiSettingController;
use App\Http\Controllers\Api\Admin\RewrittenArticleController;
use App\Http\Controllers\Api\Admin\ApprovedArticleController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AISettingsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public article and category routes
Route::get('/articles', [ArticleController::class, 'index']);
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

    // User routes
    Route::apiResource('users', UserController::class);
    Route::put('users/{id}/role', [UserController::class, 'updateRole']);

    // AI Settings routes
    Route::get('ai-settings', [AISettingsController::class, 'index']);
    Route::put('ai-settings', [AISettingsController::class, 'update']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Rewritten Articles Management
    Route::get('/articles/rewritten', [RewrittenArticleController::class, 'index']);
    Route::get('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'show']);
    Route::put('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'update']);
    Route::post('/articles/rewritten/{rewrittenArticle}/approve', [RewrittenArticleController::class, 'approve']);
    Route::post('/articles/rewritten/{rewrittenArticle}/reject', [RewrittenArticleController::class, 'reject']);
    Route::delete('/articles/rewritten/{rewrittenArticle}', [RewrittenArticleController::class, 'destroy']);

    // Approved Articles Management
    Route::get('/articles/approved', [ApprovedArticleController::class, 'index']);
    Route::get('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'show']);
    Route::put('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'update']);
    Route::post('/articles/approved/{approvedArticle}/archive', [ApprovedArticleController::class, 'archive']);
    Route::delete('/articles/approved/{approvedArticle}', [ApprovedArticleController::class, 'destroy']);

    // Categories Management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    // User management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole']);

    // AI Settings management
    Route::get('/ai-settings', [AdminAiSettingController::class, 'index']);
    Route::put('/ai-settings', [AdminAiSettingController::class, 'update']);
    Route::post('/ai-settings/test-connection', [AISettingsController::class, 'testConnection']);
    Route::post('/ai-settings/reset', [AISettingsController::class, 'resetSettings']);
}); 