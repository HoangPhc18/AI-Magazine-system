<?php

use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\AiSettingController as AdminAiSettingController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
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
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Article management
    Route::get('/articles/rewritten', [AdminArticleController::class, 'index']);
    Route::get('/articles/rewritten/{rewrittenArticle}', [AdminArticleController::class, 'show']);
    Route::post('/articles/rewritten/{rewrittenArticle}/approve', [AdminArticleController::class, 'approve']);
    Route::put('/articles/rewritten/{rewrittenArticle}', [AdminArticleController::class, 'update']);
    Route::delete('/articles/rewritten/{rewrittenArticle}', [AdminArticleController::class, 'destroy']);

    // Category management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
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
}); 