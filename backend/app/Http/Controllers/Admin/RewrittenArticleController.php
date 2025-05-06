<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewrittenArticle;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\AISetting;
use App\Services\AIService;
use App\Http\Requests\ApproveArticleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Subcategory;

class RewrittenArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = RewrittenArticle::query();

        // Filter by status
        if (request()->has('status') && request('status') != '') {
            $query->where('status', request('status'));
        } else {
            // Mặc định chỉ hiển thị các bài viết chưa được duyệt
            $query->where('status', '!=', 'approved');
        }

        // Filter by creation date range
        if (request()->has('created_from') && request('created_from') != '') {
            $query->whereDate('created_at', '>=', request('created_from'));
        }

        if (request()->has('created_to') && request('created_to') != '') {
            $query->whereDate('created_at', '<=', request('created_to'));
        }

        // Filter by category
        if (request()->has('category_id') && request('category_id') != '') {
            $query->where('category_id', request('category_id'));
        }

        $rewrittenArticles = $query->with(['category', 'user'])
            ->latest()
            ->paginate(10);

        return view('admin.rewritten-articles.index', compact('rewrittenArticles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        $originalArticles = ApprovedArticle::where('status', 'published')->get();
        return view('admin.rewritten-articles.create', compact('categories', 'originalArticles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Validate that the subcategory belongs to the selected category
        if (!empty($validated['subcategory_id'])) {
            $subcategory = Subcategory::find($validated['subcategory_id']);
            if (!$subcategory || $subcategory->parent_category_id != $validated['category_id']) {
                return redirect()->back()->withErrors([
                    'subcategory_id' => 'Danh mục con phải thuộc danh mục cha đã chọn.'
                ])->withInput();
            }
        }

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending';

        if ($request->hasFile('featured_image')) {
            // Lấy file ảnh từ request
            $image = $request->file('featured_image');
            
            // Tạo tên file duy nhất
            $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
            
            // Lưu ảnh vào thư mục public/articles
            $imagePath = $image->storeAs('articles', $filename, 'public');
            
            // Ensure we store only the path without 'public/' prefix
            $validated['featured_image'] = $imagePath;
            
            Log::info('Uploaded featured image', [
                'filename' => $filename,
                'path' => $imagePath
            ]);
        }

        RewrittenArticle::create($validated);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được tạo thành công và đang chờ duyệt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RewrittenArticle $rewrittenArticle)
    {
        return view('admin.rewritten-articles.show', compact('rewrittenArticle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RewrittenArticle $rewrittenArticle)
    {
        $categories = Category::all();
        return view('admin.rewritten-articles.edit', compact('rewrittenArticle', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     * Always include validation for subcategory_id to ensure it's preserved when updating.
     */
    public function update(Request $request, RewrittenArticle $rewrittenArticle)
    {
        // Debug: Log all request data to see what's being received
        \Log::info('RewrittenArticle update request data', [
            'all_data' => $request->all(),
            'subcategory_id' => $request->input('subcategory_id'),
            'explicit_subcategory_id' => $request->input('explicit_subcategory_id'),
            'has_subcategory_id' => $request->has('subcategory_id'),
            'has_explicit_subcategory_id' => $request->has('explicit_subcategory_id'),
            'article_id' => $rewrittenArticle->id,
            'current_subcategory_id' => $rewrittenArticle->subcategory_id
        ]);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'explicit_subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        // Validate that the subcategory belongs to the selected category
        if (!empty($validated['subcategory_id'])) {
            $subcategory = Subcategory::find($validated['subcategory_id']);
            if (!$subcategory || $subcategory->parent_category_id != $validated['category_id']) {
                return redirect()->back()->withErrors([
                    'subcategory_id' => 'Danh mục con phải thuộc danh mục cha đã chọn.'
                ])->withInput();
            }
        }

        // Use explicit_subcategory_id if provided - this helps preserve subcategory selections
        if (isset($validated['explicit_subcategory_id']) && !empty($validated['explicit_subcategory_id'])) {
            $subcategory = Subcategory::find($validated['explicit_subcategory_id']);
            if ($subcategory && $subcategory->parent_category_id == $validated['category_id']) {
                $validated['subcategory_id'] = $validated['explicit_subcategory_id'];
            }
            // Remove the explicit field as it's not in the fillable attributes
            unset($validated['explicit_subcategory_id']);
        }
        
        // TEST CODE: Use test_subcategory_id for testing purposes
        if ($request->has('test_subcategory_id')) {
            $testSubcategoryId = $request->input('test_subcategory_id');
            \Log::info('Using test_subcategory_id for debugging', [
                'test_subcategory_id' => $testSubcategoryId
            ]);
            
            // Just set the value directly for testing
            $validated['subcategory_id'] = $testSubcategoryId;
        }
        
        // Debug logging for subcategory changes
        Log::info('Updating rewritten article with subcategory data', [
            'article_id' => $rewrittenArticle->id,
            'old_category_id' => $rewrittenArticle->category_id,
            'new_category_id' => $validated['category_id'],
            'old_subcategory_id' => $rewrittenArticle->subcategory_id,
            'new_subcategory_id' => $validated['subcategory_id'] ?? null,
            'submitted_subcategory_id' => $request->input('subcategory_id'),
            'explicit_subcategory_id' => $request->input('explicit_subcategory_id')
        ]);
        
        // If category changed but subcategory is still from old category, clear it
        if ($rewrittenArticle->category_id != $validated['category_id'] && !empty($rewrittenArticle->subcategory_id)) {
            $oldSubcategory = Subcategory::find($rewrittenArticle->subcategory_id);
            if ($oldSubcategory && $oldSubcategory->parent_category_id != $validated['category_id']) {
                // Force subcategory to null if it doesn't belong to the new category
                $validated['subcategory_id'] = null;
                Log::info('Cleared subcategory because category changed', [
                    'article_id' => $rewrittenArticle->id,
                    'old_category_id' => $rewrittenArticle->category_id,
                    'new_category_id' => $validated['category_id']
                ]);
            }
        }
        
        // Ensure subcategory_id is explicitly set to null if the field was emptied in the form
        if ($request->has('subcategory_id') && $request->input('subcategory_id') === '') {
            $validated['subcategory_id'] = null;
            Log::info('Subcategory explicitly cleared by user', [
                'article_id' => $rewrittenArticle->id
            ]);
        }

        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('featured_image')) {
            // Lấy file ảnh từ request
            $image = $request->file('featured_image');
            
            // Tạo tên file duy nhất
            $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
            
            // Lưu ảnh vào thư mục public/articles
            $imagePath = $image->storeAs('articles', $filename, 'public');
            
            // Ensure we store only the path without 'public/' prefix
            $validated['featured_image'] = $imagePath;
            
            Log::info('Uploaded featured image', [
                'filename' => $filename,
                'path' => $imagePath
            ]);
        }

        // Debug: Log final validated data right before update
        Log::info('Final data before update', [
            'article_id' => $rewrittenArticle->id,
            'validated_data' => $validated,
            'subcategory_id' => $validated['subcategory_id'] ?? null
        ]);
        
        // Set values explicitly one by one to ensure they're properly saved
        $rewrittenArticle->title = $validated['title'];
        $rewrittenArticle->slug = $validated['slug'];
        $rewrittenArticle->content = $validated['content'];
        $rewrittenArticle->category_id = $validated['category_id'];
        
        // Set subcategory_id explicitly
        if (isset($validated['subcategory_id'])) {
            $rewrittenArticle->subcategory_id = $validated['subcategory_id'];
            Log::info('Setting subcategory_id explicitly', [
                'article_id' => $rewrittenArticle->id,
                'subcategory_id' => $validated['subcategory_id']
            ]);
        } else {
            // Make sure to set it to null if not provided
            $rewrittenArticle->subcategory_id = null;
            Log::info('Setting subcategory_id to null explicitly', [
                'article_id' => $rewrittenArticle->id
            ]);
        }
        
        // Set other fields if they exist
        if (isset($validated['meta_title'])) $rewrittenArticle->meta_title = $validated['meta_title'];
        if (isset($validated['meta_description'])) $rewrittenArticle->meta_description = $validated['meta_description'];
        if (isset($validated['featured_image'])) $rewrittenArticle->featured_image = $validated['featured_image'];
        if (isset($validated['status'])) $rewrittenArticle->status = $validated['status'];
        
        // Save the article explicitly
        $rewrittenArticle->save();
        
        Log::info('Article updated successfully', [
            'article_id' => $rewrittenArticle->id,
            'subcategory_id' => $rewrittenArticle->subcategory_id
        ]);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được cập nhật thành công.');
    }

    public function approve(RewrittenArticle $rewrittenArticle)
    {
        try {
            // Chuyển bài viết sang ApprovedArticle
            $approvedArticle = $this->moveToApprovedArticles($rewrittenArticle);
            
            return redirect()->route('admin.approved-articles.show', $approvedArticle)
                ->with('success', 'Bài viết đã được phê duyệt và chuyển đến bài viết đã xuất bản thành công.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi duyệt bài viết', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                ->with('error', 'Đã xảy ra lỗi khi duyệt bài viết: ' . $e->getMessage());
        }
    }

    /**
     * Store the approved article from a rewritten article
     */
    public function storeApproved(ApproveArticleRequest $request, RewrittenArticle $rewrittenArticle)
    {
        try {
            // Xác thực dữ liệu từ form
            $validated = $request->validated();
            
            // Add explicit_subcategory_id to validated data if it's in the request
            if ($request->has('explicit_subcategory_id')) {
                $validated['explicit_subcategory_id'] = $request->input('explicit_subcategory_id');
                
                Log::info('Received explicit subcategory ID during approval', [
                    'rewritten_article_id' => $rewrittenArticle->id,
                    'explicit_subcategory_id' => $validated['explicit_subcategory_id']
                ]);
            }
            
            // Thêm slug vào dữ liệu đã xác thực
            $baseSlug = Str::slug($validated['title'] ?? $rewrittenArticle->title);
            $uniqueString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $microtime = str_replace('.', '', microtime(true));
            $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}";
            
            // Kiểm tra xem slug đã tồn tại chưa
            $existingCount = ApprovedArticle::where('slug', $uniqueSlug)->count();
            if ($existingCount > 0) {
                // Thêm một số ngẫu nhiên nếu vẫn bị trùng
                $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}-" . rand(1000, 9999);
            }
            
            // Ghi log slug được tạo
            Log::info('Tạo slug mới cho bài viết được duyệt', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'new_slug' => $uniqueSlug
            ]);
            
            $validated['slug'] = $uniqueSlug;
            
            // Tạo bài viết đã duyệt từ bài viết được viết lại
            try {
                $approvedArticle = $this->moveToApprovedArticles($rewrittenArticle, $validated);
                
                // Thông báo thành công
                return redirect()->route('admin.approved-articles.show', $approvedArticle)
                    ->with('success', 'Bài viết đã được duyệt và chuyển đến bài viết đã xuất bản thành công.');
            } catch (\Illuminate\Database\QueryException $e) {
                // Kiểm tra xem lỗi có phải do trùng lặp slug không
                if (strpos($e->getMessage(), 'approved_articles_slug_unique') !== false) {
                    Log::warning('Lỗi trùng lặp slug, tạo slug mới', [
                        'rewritten_article_id' => $rewrittenArticle->id,
                        'previous_slug' => $uniqueSlug,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Tạo slug mới với số ngẫu nhiên lớn hơn
                    $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}-" . rand(10000, 99999);
                    $validated['slug'] = $uniqueSlug;
                    
                    // Thử lại với slug mới
                    $approvedArticle = $this->moveToApprovedArticles($rewrittenArticle, $validated);
                    
                    return redirect()->route('admin.approved-articles.show', $approvedArticle)
                        ->with('success', 'Bài viết đã được duyệt và chuyển đến bài viết đã xuất bản thành công (slug mới được tạo).');
                }
                
                // Nếu là lỗi khác, ném lại ngoại lệ
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi duyệt bài viết', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                ->with('error', 'Đã xảy ra lỗi khi duyệt bài viết: ' . $e->getMessage());
        }
    }

    public function reject(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->update(['status' => 'rejected']);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã bị từ chối.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewrittenArticle $rewrittenArticle)
    {
        try {
            // Lưu thông tin bài viết trước khi xóa để log
            $articleInfo = [
                'id' => $rewrittenArticle->id,
                'title' => $rewrittenArticle->title,
                'status' => $rewrittenArticle->status,
                'featured_image' => $rewrittenArticle->featured_image
            ];
            
            // Delete the featured image if exists
            if ($rewrittenArticle->featured_image) {
                $imagePath = $rewrittenArticle->featured_image;
                if (Storage::disk('public')->exists($imagePath)) {
                    try {
                        Storage::disk('public')->delete($imagePath);
                        Log::info('Đã xóa ảnh đại diện của bài viết chưa duyệt', [
                            'article_id' => $rewrittenArticle->id,
                            'image_path' => $imagePath
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Lỗi khi xóa file ảnh đại diện của bài viết chưa duyệt: ' . $e->getMessage(), [
                            'article_id' => $rewrittenArticle->id,
                            'image_path' => $imagePath
                        ]);
                    }
                } else {
                    Log::warning('Không tìm thấy file ảnh đại diện của bài viết chưa duyệt để xóa', [
                        'article_id' => $rewrittenArticle->id,
                        'image_path' => $imagePath
                    ]);
                }
            }
            
            $rewrittenArticle->delete();
            
            Log::info('Đã xóa bài viết chưa duyệt', $articleInfo);

            return redirect()->route('admin.rewritten-articles.index')
                ->with('success', 'Bài viết đã được xóa thành công.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa bài viết chưa duyệt: ' . $e->getMessage(), [
                'article_id' => $rewrittenArticle->id
            ]);
            
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'Lỗi khi xóa bài viết: ' . $e->getMessage());
        }
    }

    /**
     * Process approving a rewritten article
     */
    private function moveToApprovedArticles(RewrittenArticle $rewrittenArticle, array $validated = null)
    {
        try {
            // Debug: Log all data before processing
            \Log::info('Before moveToApprovedArticles processing', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'rewritten_article_data' => $rewrittenArticle->toArray(),
                'validated_data' => $validated ?? 'No validated data provided',
                'has_subcategory_id' => isset($validated['subcategory_id']),
                'subcategory_id_value' => $validated['subcategory_id'] ?? null,
                'has_explicit_subcategory_id' => isset($validated['explicit_subcategory_id']),
                'explicit_subcategory_id_value' => $validated['explicit_subcategory_id'] ?? null,
                'original_article_id' => $rewrittenArticle->original_article_id,
            ]);
            
            DB::beginTransaction();
            
            // Tạo bài viết đã duyệt
            $approvedArticle = new ApprovedArticle();
            $approvedArticle->title = $validated['title'] ?? $rewrittenArticle->title;
            $approvedArticle->slug = $validated['slug'] ?? $rewrittenArticle->slug;
            $approvedArticle->content = $validated['content'] ?? $rewrittenArticle->content;
            $approvedArticle->meta_title = $validated['meta_title'] ?? $rewrittenArticle->meta_title;
            $approvedArticle->meta_description = $validated['meta_description'] ?? $rewrittenArticle->meta_description;
            
            // Handle featured image properly
            if (isset($validated['featured_image'])) {
                // Use the new image from validation if provided
                $approvedArticle->featured_image = $validated['featured_image'];
            } elseif ($rewrittenArticle->featured_image) {
                // If rewritten article has an image, copy it
                $approvedArticle->featured_image = $rewrittenArticle->featured_image;
                
                // Log the transfer of image
                Log::info('Chuyển ảnh từ bài viết được viết lại sang bài viết đã duyệt', [
                    'rewritten_id' => $rewrittenArticle->id,
                    'featured_image' => $rewrittenArticle->featured_image
                ]);
            }
            
            $approvedArticle->user_id = Auth::id();
            $approvedArticle->category_id = $validated['category_id'] ?? $rewrittenArticle->category_id;
            
            // Log subcategory information before processing
            Log::info('Subcategory data before approval', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'rewritten_subcategory_id' => $rewrittenArticle->subcategory_id,
                'validated_subcategory_id' => $validated['subcategory_id'] ?? 'not provided',
                'explicit_subcategory_id' => $validated['explicit_subcategory_id'] ?? 'not provided',
                'target_category_id' => $approvedArticle->category_id
            ]);
            
            // Enhanced subcategory handling with priority order:
            
            // Priority 1: Use explicit_subcategory_id if provided (highest priority)
            if (isset($validated['explicit_subcategory_id']) && !empty($validated['explicit_subcategory_id'])) {
                $subcategory = Subcategory::find($validated['explicit_subcategory_id']);
                if ($subcategory && $subcategory->parent_category_id == $approvedArticle->category_id) {
                    $approvedArticle->subcategory_id = $validated['explicit_subcategory_id'];
                    Log::info('Using explicit_subcategory_id from form submission', [
                        'subcategory_id' => $validated['explicit_subcategory_id'],
                        'subcategory_name' => $subcategory->name
                    ]);
                } 
                else if ($subcategory) {
                    Log::warning('Explicit subcategory_id belongs to different category, cannot use', [
                        'subcategory_id' => $validated['explicit_subcategory_id'],
                        'subcategory_category_id' => $subcategory->parent_category_id,
                        'article_category_id' => $approvedArticle->category_id
                    ]);
                }
            }
            // Priority 2: Use validated subcategory_id if provided
            else if (isset($validated['subcategory_id']) && !empty($validated['subcategory_id'])) {
                $subcategory = Subcategory::find($validated['subcategory_id']);
                if ($subcategory && $subcategory->parent_category_id == $approvedArticle->category_id) {
                    $approvedArticle->subcategory_id = $validated['subcategory_id'];
                    Log::info('Using subcategory_id from validation data', [
                        'subcategory_id' => $validated['subcategory_id'],
                        'subcategory_name' => $subcategory->name
                    ]);
                } 
                else if ($subcategory) {
                    Log::warning('Validated subcategory_id belongs to different category, cannot use', [
                        'subcategory_id' => $validated['subcategory_id'],
                        'subcategory_category_id' => $subcategory->parent_category_id,
                        'article_category_id' => $approvedArticle->category_id
                    ]);
                }
            } 
            // Priority 3: Use rewritten article's subcategory_id if it exists
            else if ($rewrittenArticle->subcategory_id) {
                $subcategory = Subcategory::find($rewrittenArticle->subcategory_id);
                if ($subcategory && $subcategory->parent_category_id == $approvedArticle->category_id) {
                    $approvedArticle->subcategory_id = $rewrittenArticle->subcategory_id;
                    Log::info('Using subcategory_id from rewritten article', [
                        'subcategory_id' => $rewrittenArticle->subcategory_id,
                        'subcategory_name' => $subcategory->name
                    ]);
                } 
                else if ($subcategory) {
                    Log::warning('Rewritten article subcategory_id belongs to different category, cannot use', [
                        'subcategory_id' => $rewrittenArticle->subcategory_id,
                        'subcategory_category_id' => $subcategory->parent_category_id,
                        'article_category_id' => $approvedArticle->category_id
                    ]);
                }
            }
            
            // Transfer original_article_id and look up source_url if available
            $sourceUrl = null;
            
            if ($rewrittenArticle->original_article_id) {
                $approvedArticle->original_article_id = $rewrittenArticle->original_article_id;
                
                // Try to get source_url from the original article
                $originalArticle = \App\Models\Article::find($rewrittenArticle->original_article_id);
                if ($originalArticle && $originalArticle->source_url) {
                    $sourceUrl = $originalArticle->source_url;
                    Log::info('Found source_url in original article', [
                        'original_article_id' => $rewrittenArticle->original_article_id,
                        'source_url' => $sourceUrl
                    ]);
                }
            }
            
            $approvedArticle->status = 'published';
            $approvedArticle->ai_generated = $rewrittenArticle->ai_generated;
            $approvedArticle->published_at = now();
            
            // Debug: Log the final subcategory_id value right before save
            Log::info('Final subcategory_id before save', [
                'subcategory_id' => $approvedArticle->subcategory_id,
                'article_id' => $approvedArticle->id ?? 'not yet created',
                'source_url' => $sourceUrl
            ]);
            
            // Save the article
            $approvedArticle->save();
            
            // Double check after save
            Log::info('Article subcategory_id after save', [
                'article_id' => $approvedArticle->id,
                'subcategory_id' => $approvedArticle->subcategory_id,
                'direct_db_value' => DB::table('approved_articles')->where('id', $approvedArticle->id)->value('subcategory_id')
            ]);
            
            // Đánh dấu bài viết đã được duyệt
            $rewrittenArticle->status = 'approved';
            $rewrittenArticle->save();
            
            // Xóa bài viết đã duyệt từ RewrittenArticle để không hiển thị nữa
            $rewrittenArticle->forceDelete();
            
            DB::commit();

            Log::info('Bài viết đã được duyệt và chuyển sang ApprovedArticle', [
                'rewritten_id' => $rewrittenArticle->id,
                'approved_id' => $approvedArticle->id,
                'category_id' => $approvedArticle->category_id,
                'subcategory_id' => $approvedArticle->subcategory_id,
                'original_article_id' => $approvedArticle->original_article_id
            ]);
            
            return $approvedArticle;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi chuyển bài viết sang ApprovedArticle: ' . $e->getMessage(), [
                'rewritten_id' => $rewrittenArticle->id
            ]);
            throw $e;
        }
    }

    /**
     * Show form for rewriting an article using AI
     */
    public function rewriteForm($originalArticleId = null)
    {
        try {
            // Log để kiểm tra hàm có được gọi không
            Log::info('Accessing rewriteForm method', [
                'originalArticleId' => $originalArticleId,
                'user_id' => Auth::id()
            ]);
            
            $categories = Category::all();
            $originalArticles = ApprovedArticle::where('status', 'published')->get();
            $selectedArticle = null;
            
            if ($originalArticleId) {
                $selectedArticle = ApprovedArticle::findOrFail($originalArticleId);
                Log::info('Selected original article', ['article_id' => $selectedArticle->id, 'title' => $selectedArticle->title]);
            }
            
            // Get AI settings
            try {
                $aiSettings = DB::table('a_i_settings')->first();
                Log::info('AI settings loaded', ['settings' => $aiSettings ? true : false]);
            } catch (\Exception $e) {
                Log::error('Error querying ai_settings table: ' . $e->getMessage());
                return redirect()->route('admin.rewritten-articles.index')
                    ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
            }
            
            if (!$aiSettings) {
                Log::error('AI settings not found');
                return redirect()->route('admin.ai-settings.index')
                    ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng cài đặt API trước.');
            }
            
            if (empty($aiSettings->api_key)) {
                Log::error('AI API key is empty');
                return redirect()->route('admin.ai-settings.index')
                    ->with('error', 'API key AI chưa được cấu hình. Vui lòng thêm API key của bạn trong phần cài đặt AI.');
            }
            
            // Check daily limit
            if ($aiSettings->max_daily_rewrites > 0) {
                $today = now()->startOfDay();
                $dailyCount = RewrittenArticle::where('user_id', Auth::id())
                    ->where('created_at', '>=', $today)
                    ->where('ai_generated', true)
                    ->count();
                
                Log::info('Checking daily limit', [
                    'daily_count' => $dailyCount,
                    'max_limit' => $aiSettings->max_daily_rewrites
                ]);
                
                if ($dailyCount >= $aiSettings->max_daily_rewrites) {
                    return redirect()->route('admin.rewritten-articles.index')
                        ->with('error', "Bạn đã đạt đến giới hạn tối đa hàng ngày là {$aiSettings->max_daily_rewrites} bài viết AI.");
                }
            }
            
            return view('admin.rewritten-articles.rewrite', compact('categories', 'originalArticles', 'selectedArticle', 'aiSettings'));
        } catch (\Exception $e) {
            Log::error('Error in rewriteForm method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi hiển thị form viết lại: ' . $e->getMessage());
        }
    }

    /**
     * Process AI rewriting of an article
     */
    public function rewriteArticle(Request $request)
    {
        $request->validate([
            'original_article_id' => 'required|exists:approved_articles,id',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Validate that the subcategory belongs to the selected category
            if (!empty($request->subcategory_id)) {
                $subcategory = Subcategory::find($request->subcategory_id);
                if (!$subcategory || $subcategory->parent_category_id != $request->category_id) {
                    return redirect()->back()->withErrors([
                        'subcategory_id' => 'Danh mục con phải thuộc danh mục cha đã chọn.'
                    ])->withInput();
                }
            }
            
            DB::beginTransaction();

            $originalArticle = ApprovedArticle::findOrFail($request->original_article_id);
            
            $rewrittenArticle = new RewrittenArticle();
            $rewrittenArticle->title = $originalArticle->title;
            $rewrittenArticle->slug = Str::slug($originalArticle->title . '-' . time());
            $rewrittenArticle->content = $request->content;
            $rewrittenArticle->meta_title = $originalArticle->meta_title;
            $rewrittenArticle->meta_description = $originalArticle->meta_description;
            $rewrittenArticle->user_id = Auth::id();
            $rewrittenArticle->category_id = $request->category_id;
            
            // Ensure subcategory_id is properly set, either from form request or from original article
            if (!empty($request->subcategory_id)) {
                $rewrittenArticle->subcategory_id = $request->subcategory_id;
            } elseif ($originalArticle->subcategory_id) {
                // Check if original article's subcategory belongs to the selected category
                $subcategory = Subcategory::find($originalArticle->subcategory_id);
                if ($subcategory && $subcategory->parent_category_id == $request->category_id) {
                    $rewrittenArticle->subcategory_id = $originalArticle->subcategory_id;
                }
            }
            
            $rewrittenArticle->original_article_id = $originalArticle->id;
            $rewrittenArticle->status = 'pending';
            $rewrittenArticle->ai_generated = false;

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Tạo tên file duy nhất
                $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
                
                // Lưu ảnh vào thư mục public/articles
                $imagePath = $image->storeAs('articles', $filename, 'public');
                
                $rewrittenArticle->featured_image = $imagePath;
                
                Log::info('Đã upload ảnh mới cho bài viết được viết lại', [
                    'original_article_id' => $originalArticle->id,
                    'image_path' => $imagePath
                ]);
            } elseif ($originalArticle->featured_image) {
                // Copy featured image from original article if exists and no new image uploaded
                $rewrittenArticle->featured_image = $originalArticle->featured_image;
                
                Log::info('Sử dụng ảnh từ bài viết gốc cho bài viết được viết lại', [
                    'original_article_id' => $originalArticle->id,
                    'image_path' => $originalArticle->featured_image
                ]);
            }

            $rewrittenArticle->save();

            DB::commit();

            return redirect()->route('admin.rewritten-articles.index')
                ->with('success', 'Bài viết đã được tạo thành công và đang chờ duyệt.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating rewritten article: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo bài viết. Vui lòng thử lại.')
                ->withInput();
        }
    }

    /**
     * Show form for AI rewriting of an existing rewritten article
     */
    public function aiRewriteForm(RewrittenArticle $rewrittenArticle)
    {
        $categories = Category::orderBy('name')->get();
        
        try {
            $aiSettings = DB::table('a_i_settings')->first();
        } catch (\Exception $e) {
            Log::error('Error querying ai_settings table: ' . $e->getMessage());
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
        }
        
        if (!$aiSettings) {
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'AI settings are not configured. Please configure them first.');
        }
        
        if (empty($aiSettings->api_key)) {
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'AI API key is not configured. Please add your API key in the AI settings.');
        }
        
        // Check daily limit
        if ($aiSettings->max_daily_rewrites > 0) {
            $today = now()->startOfDay();
            $dailyCount = RewrittenArticle::where('user_id', Auth::id())
                ->where('created_at', '>=', $today)
                ->where('ai_generated', true)
                ->count();
            
            if ($dailyCount >= $aiSettings->max_daily_rewrites) {
                return redirect()->route('admin.rewritten-articles.index')
                    ->with('error', "Bạn đã đạt đến giới hạn tối đa hàng ngày là {$aiSettings->max_daily_rewrites} bài viết AI.");
            }
        }
        
        return view('admin.rewritten-articles.ai-rewrite', compact('rewrittenArticle', 'categories', 'aiSettings'));
    }

    /**
     * Process AI rewriting of an existing rewritten article
     */
    public function aiRewrite(Request $request, RewrittenArticle $rewrittenArticle)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id'
        ]);

        try {
            // Validate that the subcategory belongs to the selected category
            if (!empty($validated['subcategory_id'])) {
                $subcategory = Subcategory::find($validated['subcategory_id']);
                if (!$subcategory || $subcategory->parent_category_id != $validated['category_id']) {
                    return redirect()->back()->withErrors([
                        'subcategory_id' => 'Danh mục con phải thuộc danh mục cha đã chọn.'
                    ])->withInput();
                }
            }
            
            // Cập nhật nội dung bài viết đã viết lại bằng AI
            $rewrittenArticle->content = $validated['content'];
            $rewrittenArticle->category_id = $validated['category_id'];
            $rewrittenArticle->subcategory_id = $validated['subcategory_id'];
            $rewrittenArticle->ai_generated = true;
            $rewrittenArticle->save();
            
            // If the article is from an approved article and auto-approval is enabled, update the original
            if ($rewrittenArticle->status === 'approved' && $rewrittenArticle->original_article_id && $aiSettings->auto_approval) {
                $originalArticle = ApprovedArticle::find($rewrittenArticle->original_article_id);
                if ($originalArticle) {
                    $originalArticle->content = $validated['content'];
                    
                    // Also update the featured image if a new one was uploaded
                    if ($request->hasFile('featured_image')) {
                        $originalArticle->featured_image = $rewrittenArticle->featured_image;
                    }
                    
                    $originalArticle->save();
                    
                    return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                        ->with('success', 'Bài viết đã được viết lại bằng AI và cập nhật vào bài viết gốc.');
                }
            }
            
            return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                ->with('success', 'Bài viết đã được viết lại bằng AI. Vui lòng xem lại và chỉnh sửa nếu cần thiết.');
            
        } catch (\Exception $e) {
            Log::error('AI rewriting error: ' . $e->getMessage(), [
                'article_id' => $rewrittenArticle->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Đã xảy ra lỗi trong quá trình viết lại bằng AI: ' . $e->getMessage());
        }
    }
}
