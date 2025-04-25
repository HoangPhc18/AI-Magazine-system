<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovedArticleController extends Controller
{
    /**
     * Display a listing of approved articles
     */
    public function index()
    {
        $query = ApprovedArticle::query();

        // Filter by status
        if (request()->has('status') && request('status') != '') {
            $query->where('status', request('status'));
        }

        // Filter by creation date range
        if (request()->has('created_from') && request('created_from') != '') {
            $query->whereDate('created_at', '>=', request('created_from'));
        }

        if (request()->has('created_to') && request('created_to') != '') {
            $query->whereDate('created_at', '<=', request('created_to'));
        }

        // Filter by publication date range
        if (request()->has('published_from') && request('published_from') != '') {
            $query->whereDate('published_at', '>=', request('published_from'));
        }

        if (request()->has('published_to') && request('published_to') != '') {
            $query->whereDate('published_at', '<=', request('published_to'));
        }

        // Filter by category
        if (request()->has('category_id') && request('category_id') != '') {
            $query->where('category_id', request('category_id'));
        }

        $articles = $query->with(['category', 'user', 'featuredImage'])->latest()->paginate(10);

        // Load categories for filter
        $categories = Category::all();

        return view('admin.approved-articles.index', compact('articles', 'categories'));
    }

    /**
     * Show the form for creating a new approved article
     */
    public function create()
    {
        $categories = Category::all();
        $images = Media::where('type', 'image')->latest()->take(10)->get();
        
        return view('admin.approved-articles.create', compact('categories', 'images'));
    }

    /**
     * Store a newly created approved article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'original_article_id' => 'nullable|exists:rewritten_articles,id',
            'ai_generated' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
        ]);

        try {
            // Bắt đầu transaction
            DB::beginTransaction();

            // Generate slug from title
            $validated['slug'] = Str::slug($validated['title']);
            
            // Set user ID
            $validated['user_id'] = Auth::id();
            
            // Handle featured image 
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Upload as a Media object
                $fileName = time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $path = 'images/' . date('Y/m');
                $filePath = $image->storeAs($path, $fileName, 'public');
                
                $media = Media::create([
                    'name' => $request->title,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'type' => 'image',
                    'user_id' => Auth::id(),
                ]);
                
                $validated['featured_image_id'] = $media->id;
                
                Log::info('New media created from article upload', [
                    'media_id' => $media->id,
                    'path' => $filePath,
                ]);
            }

            // Set published_at if status is published
            if ($validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $approvedArticle = ApprovedArticle::create($validated);
            
            // Process article media entities from content
            $this->processArticleMedia($approvedArticle, $request->input('content_media_ids', []));
            
            // Commit transaction
            DB::commit();

            return redirect()->route('admin.approved-articles.show', $approvedArticle)
                ->with('success', 'Bài viết đã được tạo và xuất bản thành công.');
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            
            Log::error('Lỗi khi tạo bài viết mới: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo bài viết: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing an approved article
     */
    public function edit(ApprovedArticle $approvedArticle)
    {
        $categories = Category::all();
        $images = Media::where('type', 'image')->latest()->take(10)->get();
        
        return view('admin.approved-articles.edit', compact('approvedArticle', 'categories', 'images'));
    }

    /**
     * Update the specified approved article
     */
    public function update(Request $request, ApprovedArticle $approvedArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
        ]);

        try {
            // Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
            DB::beginTransaction();
            
            // Generate slug from title
            $validated['slug'] = Str::slug($validated['title']);

            // Nếu không có featured_image_id mới và bài viết đã có
            // Giữ lại giá trị cũ để không bị mất
            if (!isset($validated['featured_image_id']) && $approvedArticle->featured_image_id) {
                $validated['featured_image_id'] = $approvedArticle->featured_image_id;
            }

            // Xử lý ảnh đại diện
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Upload as a Media object
                $fileName = time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $path = 'images/' . date('Y/m');
                $filePath = $image->storeAs($path, $fileName, 'public');
                
                $media = Media::create([
                    'name' => $request->title,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'type' => 'image',
                    'user_id' => Auth::id(),
                ]);
                
                $validated['featured_image_id'] = $media->id;
                
                Log::info('New media created from article update', [
                    'media_id' => $media->id,
                    'path' => $filePath,
                ]);
            }

            // Update publish status and date
            if ($validated['status'] === 'published' && $approvedArticle->status !== 'published') {
                $validated['published_at'] = now();
            } elseif ($validated['status'] === 'unpublished') {
                $validated['published_at'] = null;
            }

            $approvedArticle->update($validated);
            
            // Process article media entities from content
            $this->processArticleMedia($approvedArticle, $request->input('content_media_ids', []));
            
            // Commit transaction
            DB::commit();

            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được cập nhật thành công.');
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            
            Log::error('Lỗi khi cập nhật bài viết: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật bài viết: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified approved article
     */
    public function show(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->load('featuredImage', 'media');
        return view('admin.approved-articles.show', compact('approvedArticle'));
    }

    /**
     * Remove the specified article from publication
     */
    public function unpublish(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update([
            'status' => 'unpublished',
            'published_at' => null
        ]);
        
        return redirect()->route('admin.approved-articles.index')
            ->with('success', 'Article has been unpublished.');
    }

    /**
     * Publish the specified article
     */
    public function publish(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        
        return redirect()->route('admin.approved-articles.index')
            ->with('success', 'Article has been published.');
    }

    /**
     * Remove the specified resource permanently
     */
    public function destroy(ApprovedArticle $approvedArticle)
    {
        try {
            DB::beginTransaction();
            
            $approvedArticle->delete();
            
            DB::commit();
            
            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được xóa thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Lỗi khi xóa bài viết: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa bài viết: ' . $e->getMessage());
        }
    }
    
    /**
     * Process and associate media with the article
     */
    private function processArticleMedia($article, $mediaIds)
    {
        if (empty($mediaIds)) {
            return;
        }
        
        // Convert to array if it's a string
        if (is_string($mediaIds)) {
            $mediaIds = explode(',', $mediaIds);
        }
        
        // Filter out any non-numeric values and convert to integers
        $mediaIds = array_filter(array_map('intval', $mediaIds));
        
        if (empty($mediaIds)) {
            return;
        }
        
        // Sync media with the article
        $article->media()->sync($mediaIds);
        
        Log::info('Associated media with article', [
            'article_id' => $article->id,
            'media_ids' => $mediaIds
        ]);
    }
} 