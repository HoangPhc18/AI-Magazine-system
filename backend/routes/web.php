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
use App\Http\Controllers\Admin\FacebookPostController;
use App\Http\Controllers\Admin\WebsiteConfigController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

// Public routes - Frontend
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/articles', [HomeController::class, 'allArticles'])->name('articles.all');
Route::get('/articles/{slug}', [HomeController::class, 'showArticle'])->name('articles.show');
Route::get('/category/{slug}', [HomeController::class, 'categoryArticles'])->name('articles.category');
Route::get('/category/{category_slug}/subcategory/{subcategory_slug}', [HomeController::class, 'subcategoryArticles'])->name('articles.subcategory');
Route::get('/search', [HomeController::class, 'search'])->name('articles.search');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Registration routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// User profile routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [UserProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Admin routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // User management
    Route::resource('users', UserController::class);
    
    // Category management
    Route::resource('categories', CategoryController::class);
    Route::get('categories/{category}/subcategories', [CategoryController::class, 'getSubcategories'])->name('categories.subcategories');
    
    // Article management
    Route::resource('articles', ArticleController::class);
    
    // Media management
    Route::resource('media', MediaController::class)->except(['edit', 'update']);
    Route::get('media/select', [MediaController::class, 'select'])->name('media.select');
    Route::post('media/upload-content-image', [MediaController::class, 'uploadContentImage'])->name('media.upload-content-image');
    
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
    Route::get('keyword-rewrites/{keywordRewrite}/check-status', [KeywordRewriteController::class, 'checkStatus'])->name('keyword-rewrites.check-status');
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
    Route::get('approved-articles/scraper-results', [ApprovedArticleController::class, 'scraperResults'])->name('approved-articles.scraper-results');
    Route::post('approved-articles/run-scraper', [ApprovedArticleController::class, 'runScraper'])->name('approved-articles.run-scraper');
    Route::get('approved-articles/check-scraper-status', [ApprovedArticleController::class, 'checkScraperStatus'])->name('approved-articles.check-scraper-status');
    
    // Rewriter routes
    Route::get('approved-articles/rewriter-results', [ApprovedArticleController::class, 'rewriterResults'])->name('approved-articles.rewriter-results');
    Route::post('approved-articles/run-rewriter', [ApprovedArticleController::class, 'runRewriter'])->name('approved-articles.run-rewriter');
    Route::get('approved-articles/check-rewriter-status', [ApprovedArticleController::class, 'checkRewriterStatus'])->name('approved-articles.check-rewriter-status');
    
    Route::resource('approved-articles', ApprovedArticleController::class)->except(['create']);
    Route::patch('approved-articles/{approvedArticle}/publish', [ApprovedArticleController::class, 'publish'])->name('approved-articles.publish');
    Route::patch('approved-articles/{approvedArticle}/unpublish', [ApprovedArticleController::class, 'unpublish'])->name('approved-articles.unpublish');
    Route::post('approved-articles/{approvedArticle}/update-media', [ApprovedArticleController::class, 'updateMedia'])->name('approved-articles.update-media');
    Route::post('approved-articles/{approvedArticle}/update-featured-image', [ApprovedArticleController::class, 'updateFeaturedImage'])->name('approved-articles.update-featured-image');

    // Facebook Posts routes
    Route::resource('facebook-posts', FacebookPostController::class);
    Route::patch('facebook-posts/{facebookPost}/mark-processed', [FacebookPostController::class, 'markAsProcessed'])->name('facebook-posts.mark-processed');
    Route::patch('facebook-posts/{facebookPost}/mark-unprocessed', [FacebookPostController::class, 'markAsUnprocessed'])->name('facebook-posts.mark-unprocessed');
    Route::post('facebook-posts/{facebookPost}/rewrite', [FacebookPostController::class, 'rewrite'])->name('facebook-posts.rewrite');
    Route::post('facebook-posts/process-batch', [FacebookPostController::class, 'processBatch'])->name('facebook-posts.process-batch');
    Route::get('facebook-posts/{facebookPost}/rewrite-form', [FacebookPostController::class, 'showRewriteForm'])->name('facebook-posts.rewrite-form');
    Route::post('facebook-posts/{facebookPost}/save-rewritten', [FacebookPostController::class, 'saveRewrittenArticle'])->name('facebook-posts.save-rewritten');

    // Website Configuration
    Route::get('/website-config/general', [WebsiteConfigController::class, 'showGeneralForm'])->name('website-config.general');
    Route::post('/website-config/general', [WebsiteConfigController::class, 'updateGeneral'])->name('website-config.general.update');
    Route::get('/website-config/seo', [WebsiteConfigController::class, 'showSeoForm'])->name('website-config.seo');
    Route::post('/website-config/seo', [WebsiteConfigController::class, 'updateSeo'])->name('website-config.seo.update');
    Route::get('/website-config/social', [WebsiteConfigController::class, 'showSocialForm'])->name('website-config.social');
    Route::post('/website-config/social', [WebsiteConfigController::class, 'updateSocial'])->name('website-config.social.update');
    Route::get('/website-config/ui', [WebsiteConfigController::class, 'showUiForm'])->name('website-config.ui');
    Route::post('/website-config/ui', [WebsiteConfigController::class, 'updateUi'])->name('website-config.ui.update');
    Route::get('/website-config/metadata', [WebsiteConfigController::class, 'showMetadataForm'])->name('website-config.metadata');
    Route::post('/website-config/metadata', [WebsiteConfigController::class, 'updateMetadata'])->name('website-config.metadata.update');

    // Approved articles
    Route::get('/approved-articles', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'index'])->name('approved-articles.index');
    Route::get('/approved-articles/create', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'create'])->name('approved-articles.create');
    Route::post('/approved-articles', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'store'])->name('approved-articles.store');
    Route::get('/approved-articles/{approvedArticle}', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'show'])->name('approved-articles.show');
    Route::get('/approved-articles/{approvedArticle}/edit', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'edit'])->name('approved-articles.edit');
    Route::put('/approved-articles/{approvedArticle}', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'update'])->name('approved-articles.update');
    Route::delete('/approved-articles/{approvedArticle}', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'destroy'])->name('approved-articles.destroy');
    Route::post('/approved-articles/{approvedArticle}/update-media', [App\Http\Controllers\Admin\ApprovedArticleController::class, 'updateMedia'])->name('approved-articles.update-media');
});

// Test route for image debugging
Route::get('/debug-images', [App\Http\Controllers\Admin\HomeController::class, 'debugImagePaths']);

// Serve API Documentation
Route::get('/docs', function () {
    return view('docs.index');
});

// Test route for scraper results
Route::get('/test-scraper-results', function () {
    return view('admin.approved-articles.scraper-results', ['output' => ['Đây là trang kiểm tra scraper results.', 'Nếu bạn thấy trang này, tức là view đã hoạt động đúng.']]);
});

// Phục vụ media trong trường hợp khẩn cấp (khi symbolic link không hoạt động)
Route::get('/media/direct/{path}', function ($path) {
    // Chỉ cho phép trong môi trường local
    if (config('app.env') !== 'local') {
        abort(404);
    }
    
    $storagePath = storage_path('app/public/' . $path);
    
    if (!file_exists($storagePath)) {
        abort(404);
    }
    
    // Lấy mimetype của file
    $mime = mime_content_type($storagePath);
    
    // Phục vụ file trực tiếp với header đúng
    return response()->file($storagePath, [
        'Content-Type' => $mime,
    ]);
})->where('path', '.*')->name('media.direct');
