<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
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

        $articles = $query->with(['category', 'user'])->latest()->paginate(10);

        // Loop through articles to check if they have a corresponding rewritten_article
        // that needs to be deleted
        foreach ($articles as $article) {
            if ($article->original_article_id) {
                $rewrittenArticle = \App\Models\RewrittenArticle::find($article->original_article_id);
                if ($rewrittenArticle) {
                    try {
                        // Permanently delete the rewritten article
                        $rewrittenArticle->forceDelete();
                        
                        // Log for tracking purposes
                        \Log::info('Deleted rewritten article after finding it in approved articles list', [
                            'rewritten_article_id' => $rewrittenArticle->id,
                            'approved_article_id' => $article->id
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error deleting rewritten article', [
                            'rewritten_article_id' => $rewrittenArticle->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return view('admin.approved-articles.index', compact('articles'));
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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'current_featured_image' => 'nullable|string',
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
                
                // Tạo tên file duy nhất
                $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
                
                // Lưu ảnh vào thư mục public/articles
                $imagePath = $image->storeAs('articles', $filename, 'public');
                
                // Ensure we store only the path without 'public/' prefix
                $validated['featured_image'] = $imagePath;
                
                Log::info('Đã upload ảnh mới cho bài viết', [
                    'image_path' => $imagePath,
                    'filename' => $filename
                ]);
            } elseif ($request->has('current_featured_image')) {
                $validated['featured_image'] = $request->input('current_featured_image');
            }

            // Set published_at if status is published
            if ($validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $approvedArticle = ApprovedArticle::create($validated);
            
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
        return view('admin.approved-articles.edit', compact('approvedArticle', 'categories'));
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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
        ]);

        try {
            // Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
            DB::beginTransaction();
            
            // Generate slug from title
            $validated['slug'] = Str::slug($validated['title']);

            // Xử lý ảnh đại diện
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Tạo tên file duy nhất
                $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
                
                // Lưu ảnh vào thư mục public/articles
                $imagePath = $image->storeAs('articles', $filename, 'public');
                
                // Nếu đã có ảnh cũ, xóa ảnh cũ
                if ($approvedArticle->featured_image) {
                    // Xóa file cũ nếu nó tồn tại trong storage
                    if (Storage::disk('public')->exists($approvedArticle->featured_image)) {
                        Storage::disk('public')->delete($approvedArticle->featured_image);
                    }
                }
                
                // Ensure we store only the path without 'public/' prefix
                $validated['featured_image'] = $imagePath;
                
                Log::info('Đã cập nhật ảnh mới cho bài viết', [
                    'article_id' => $approvedArticle->id,
                    'old_image' => $approvedArticle->featured_image,
                    'new_image' => $imagePath,
                    'filename' => $filename
                ]);
            } else {
                // Nếu không có ảnh mới, giữ nguyên ảnh cũ
                $validated['featured_image'] = $approvedArticle->featured_image;
            }

            // Update published_at if status changed
            if ($validated['status'] === 'published' && $approvedArticle->status !== 'published') {
                $validated['published_at'] = now();
            } elseif ($validated['status'] === 'unpublished' && $approvedArticle->status === 'published') {
                $validated['published_at'] = null;
            }

            // Cập nhật bài viết
            $approvedArticle->update($validated);
            
            // Commit transaction
            DB::commit();

            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được cập nhật thành công.');
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            
            Log::error('Lỗi khi cập nhật bài viết: ' . $e->getMessage(), [
                'article_id' => $approvedArticle->id,
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
            // Lưu thông tin bài viết trước khi xóa để log
            $articleInfo = [
                'id' => $approvedArticle->id,
                'title' => $approvedArticle->title,
                'status' => $approvedArticle->status,
                'published_at' => $approvedArticle->published_at,
                'featured_image' => $approvedArticle->featured_image
            ];
            
            // Delete the featured image if exists
            if ($approvedArticle->featured_image) {
                $imagePath = $approvedArticle->featured_image;
                if (Storage::disk('public')->exists($imagePath)) {
                    try {
                        Storage::disk('public')->delete($imagePath);
                        Log::info('Đã xóa ảnh đại diện của bài viết', [
                            'article_id' => $approvedArticle->id,
                            'image_path' => $imagePath
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Lỗi khi xóa file ảnh đại diện: ' . $e->getMessage(), [
                            'article_id' => $approvedArticle->id,
                            'image_path' => $imagePath
                        ]);
                    }
                } else {
                    Log::warning('Không tìm thấy file ảnh đại diện để xóa', [
                        'article_id' => $approvedArticle->id,
                        'image_path' => $imagePath
                    ]);
                }
            }
            
            // Xóa triệt để
            $approvedArticle->forceDelete();
            
            // Kiểm tra xem bài viết đã thực sự bị xóa chưa
            $checkArticle = ApprovedArticle::withTrashed()->find($articleInfo['id']);
            if ($checkArticle) {
                Log::warning('Bài viết vẫn tồn tại sau khi forceDelete trong destroy()', [
                    'article_id' => $articleInfo['id'],
                    'is_trashed' => $checkArticle->trashed(),
                    'status' => $checkArticle->status
                ]);
                
                // Thử xóa một lần nữa bằng query builder
                DB::table('approved_articles')->where('id', $articleInfo['id'])->delete();
                
                // Kiểm tra lại lần nữa
                $checkAgain = ApprovedArticle::withTrashed()->find($articleInfo['id']);
                if ($checkAgain) {
                    Log::error('Không thể xóa hoàn toàn bài viết sau nhiều lần thử', [
                        'article_id' => $articleInfo['id']
                    ]);
                }
            }
            
            Log::info('Đã xóa bài viết hoàn toàn', $articleInfo);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được xóa hoàn toàn.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa bài viết: ' . $e->getMessage(), [
                'article_id' => $approvedArticle->id
            ]);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('error', 'Lỗi khi xóa bài viết: ' . $e->getMessage());
        }
    }
} 