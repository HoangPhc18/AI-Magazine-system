<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\Media;
use App\Models\ArticleFeaturedImage;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        $articles = $query->with(['category', 'subcategory', 'user', 'featuredImage'])->latest()->paginate(10);

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
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'original_article_id' => 'nullable|exists:rewritten_articles,id',
            'ai_generated' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
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

            // Create article
            $approvedArticle = ApprovedArticle::create($validated);
            
            // Process article media entities from content immediately after creation
            $contentMediaIds = $request->input('content_media_ids', '');
            if (!empty($contentMediaIds)) {
                Log::info('Processing content media IDs during article creation', [
                    'article_id' => $approvedArticle->id,
                    'content_media_ids' => $contentMediaIds
                ]);
                
                $this->processArticleMedia($approvedArticle, $contentMediaIds);
                
                // Refresh article to ensure we have updated content
                $approvedArticle->refresh();
            }
            
            // Process featured image (if provided)
            if (isset($validated['featured_image_id']) && $validated['featured_image_id'] > 0) {
                Log::info('Processing featured image for article', [
                    'article_id' => $approvedArticle->id,
                    'featured_image_id' => $validated['featured_image_id']
                ]);
                
                // Tạo mới bản ghi trong bảng article_featured_images
                $media = Media::find($validated['featured_image_id']);
                
                if ($media) {
                    ArticleFeaturedImage::create([
                        'article_id' => $approvedArticle->id,
                        'media_id' => $media->id,
                        'position' => 'featured',
                        'is_main' => true,
                        'alt_text' => $media->name,
                    ]);
                }
            }
            
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
     * Always include validation for subcategory_id to ensure it's preserved when updating.
     */
    public function update(Request $request, ApprovedArticle $approvedArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
            'explicit_subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        try {
            // Use explicit_subcategory_id if provided
            if (isset($validated['explicit_subcategory_id'])) {
                $validated['subcategory_id'] = $validated['explicit_subcategory_id'];
                unset($validated['explicit_subcategory_id']); // Remove it from the data that will be saved
            }
            
            // Also check for the additional selected_subcategory_id field
            if (isset($request->selected_subcategory_id)) {
                $validated['subcategory_id'] = $request->selected_subcategory_id === '' ? null : $request->selected_subcategory_id;
                Log::info('Using selected_subcategory_id value', [
                    'article_id' => $approvedArticle->id,
                    'selected_subcategory_id' => $validated['subcategory_id']
                ]);
            }
            
            // Prioritize the forced_subcategory_id field if it exists
            if (isset($request->forced_subcategory_id)) {
                $validated['subcategory_id'] = $request->forced_subcategory_id === '' ? null : $request->forced_subcategory_id;
                Log::info('Using forced_subcategory_id value', [
                    'article_id' => $approvedArticle->id,
                    'forced_subcategory_id' => $validated['subcategory_id']
                ]);
            }
            
            // Ensure subcategory_id is explicitly set to null if empty string is provided
            // This ensures empty subcategory selections are properly processed
            if (isset($validated['subcategory_id']) && $validated['subcategory_id'] === '') {
                $validated['subcategory_id'] = null;
            }
            
            // Validate that the subcategory belongs to the selected category
            if (!empty($validated['subcategory_id'])) {
                $subcategory = Subcategory::find($validated['subcategory_id']);
                if (!$subcategory || $subcategory->parent_category_id != $validated['category_id']) {
                    return redirect()->back()->withErrors([
                        'subcategory_id' => 'Danh mục con phải thuộc danh mục cha đã chọn.'
                    ])->withInput();
                }
            }
            
            // Debug logging for subcategory changes
            Log::info('Updating article with subcategory data', [
                'article_id' => $approvedArticle->id,
                'old_category_id' => $approvedArticle->category_id,
                'new_category_id' => $validated['category_id'],
                'old_subcategory_id' => $approvedArticle->subcategory_id,
                'new_subcategory_id' => $validated['subcategory_id'] ?? null,
                'request_data' => $request->all(),
                'has_subcategory_change' => ($approvedArticle->subcategory_id != ($validated['subcategory_id'] ?? null))
            ]);
            
            // If category changed but subcategory is still from old category, clear it
            if ($approvedArticle->category_id != $validated['category_id'] && !empty($approvedArticle->subcategory_id)) {
                $oldSubcategory = Subcategory::find($approvedArticle->subcategory_id);
                if ($oldSubcategory && $oldSubcategory->parent_category_id != $validated['category_id']) {
                    // Force subcategory to null if it doesn't belong to the new category
                    $validated['subcategory_id'] = null;
                    Log::info('Cleared subcategory because category changed', [
                        'article_id' => $approvedArticle->id,
                        'old_category_id' => $approvedArticle->category_id,
                        'new_category_id' => $validated['category_id']
                    ]);
                }
            }
            
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
                
                // Cập nhật hoặc tạo mới featured image trong bảng article_featured_images
                $existingMainImage = $approvedArticle->mainFeaturedImage;
                
                if ($existingMainImage) {
                    // Cập nhật featured image hiện có
                    $existingMainImage->update([
                        'media_id' => $media->id,
                        'alt_text' => $media->name,
                    ]);
                } else {
                    // Tạo mới featured image
                    ArticleFeaturedImage::create([
                        'article_id' => $approvedArticle->id,
                        'media_id' => $media->id,
                        'position' => 'featured',
                        'is_main' => true,
                        'alt_text' => $media->name,
                    ]);
                }
            } else if (isset($validated['featured_image_id']) && $validated['featured_image_id']) {
                // Nếu featured_image_id được cung cấp (qua thư viện ảnh), cập nhật trong bảng mới
                $media = Media::find($validated['featured_image_id']);
                
                if ($media) {
                    $existingMainImage = $approvedArticle->mainFeaturedImage;
                    
                    if ($existingMainImage) {
                        // Cập nhật featured image hiện có
                        $existingMainImage->update([
                            'media_id' => $media->id,
                            'alt_text' => $media->name,
                        ]);
                    } else {
                        // Tạo mới featured image
                        ArticleFeaturedImage::create([
                            'article_id' => $approvedArticle->id,
                            'media_id' => $media->id,
                            'position' => 'featured',
                            'is_main' => true,
                            'alt_text' => $media->name,
                        ]);
                    }
                }
            }

            // Update publish status and date
            if ($validated['status'] === 'published' && $approvedArticle->status !== 'published') {
                $validated['published_at'] = now();
            } elseif ($validated['status'] === 'unpublished') {
                $validated['published_at'] = null;
            }

            // Update article with validated data
            $approvedArticle->fill($validated);
            
            // Explicitly set the subcategory_id field to ensure it's updated
            // This ensures the subcategory change is applied even if it's not detected by other means
            if (isset($request->subcategory_id)) {
                $approvedArticle->subcategory_id = $request->subcategory_id === '' ? null : $request->subcategory_id;
                Log::info('Explicitly setting subcategory_id', [
                    'article_id' => $approvedArticle->id,
                    'new_subcategory_id' => $approvedArticle->subcategory_id
                ]);
            }
            
            // Save the article
            $approvedArticle->save();
            
            // Process article media entities from content
            // Make sure to process even if only content media has changed and no other fields
            $contentMediaIds = $request->input('content_media_ids', '');
            
            // Luôn xử lý media IDs nếu có, bất kể có thay đổi ảnh đại diện hay không
            if (!empty($contentMediaIds)) {
                Log::info('Processing content media IDs during article update', [
                    'article_id' => $approvedArticle->id,
                    'content_media_ids' => $contentMediaIds
                ]);
                
                $this->processArticleMedia($approvedArticle, $contentMediaIds);
            }
            
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
        // Explicitly load relationships with logging
        $approvedArticle->load('featuredImage', 'media');
        
        Log::info('Displaying article with media', [
            'article_id' => $approvedArticle->id,
            'media_count' => $approvedArticle->media->count(),
            'media_ids' => $approvedArticle->media->pluck('id')->toArray(),
            'has_featured_image' => $approvedArticle->featuredImage ? true : false
        ]);
        
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
     * Process and attach media items to an article, and ensure content references are correct
     */
    private function processArticleMedia($article, $mediaIds)
    {
        // Convert string to array if needed
        if (is_string($mediaIds)) {
            $mediaIds = explode(',', $mediaIds);
        }

        // Filter out empty values and ensure all IDs are integers
        $mediaIds = array_filter(array_map('intval', array_filter($mediaIds)));

        // If no valid media IDs remain after filtering, log and return
        if (empty($mediaIds)) {
            Log::info('No valid media IDs to process for article content', [
                'article_id' => $article->id
            ]);
            return;
        }

        Log::info('Processing content media for article', [
            'article_id' => $article->id,
            'media_ids' => $mediaIds
        ]);

        try {
            // Get the media items
            $mediaItems = Media::whereIn('id', $mediaIds)->get();

            if ($mediaItems->isEmpty()) {
                Log::warning('No media items found for IDs', [
                    'article_id' => $article->id,
                    'media_ids' => $mediaIds
                ]);
                return;
            }

            // Get IDs of media items that actually exist
            $validMediaIds = $mediaItems->pluck('id')->toArray();

            // Attach media to article using sync to ensure we have exactly the media items
            // that were submitted (not using syncWithoutDetaching to avoid accumulating old references)
            $article->media()->sync($validMediaIds);

            // Log the found vs requested media items for debugging
            Log::info('Content media attached to article', [
                'article_id' => $article->id,
                'requested_ids' => $mediaIds,
                'found_ids' => $validMediaIds,
                'media_count' => $mediaItems->count()
            ]);

            // Ensure article content has correct media src URLs
            $content = $article->content;

            // Create a map of media items for quick lookup
            $mediaMap = [];
            foreach ($mediaItems as $media) {
                $mediaMap[$media->id] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_path' => $media->file_path,
                    'url' => asset('storage/' . $media->file_path),
                ];
            }

            // Process all img tags with data-media-id attribute
            $content = preg_replace_callback(
                '/<img([^>]*)data-media-id=["\'](\d+)["\']([^>]*)>/i',
                function($matches) use ($mediaMap, $article) {
                    $mediaId = (int)$matches[2];
                    if (isset($mediaMap[$mediaId])) {
                        $url = $mediaMap[$mediaId]['url'];
                        // Remove existing src attribute if present
                        $attributes = preg_replace('/src=["\'][^"\']*["\']/i', '', $matches[1] . $matches[3]);
                        // Add the new src attribute with correct URL
                        return "<img{$attributes} data-media-id=\"{$mediaId}\" src=\"{$url}\" alt=\"{$mediaMap[$mediaId]['name']}\">";
                    }
                    
                    // If media not found, leave tag as is
                    Log::warning("Media ID {$mediaId} referenced in article {$article->id} content not found");
                    return $matches[0];
                },
                $content
            );

            // Update article content with the processed version
            $article->update(['content' => $content]);
            
            Log::info('Updated article content with media URLs', [
                'article_id' => $article->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing article media', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Rethrow to allow proper handling upstream
        }
    }

    /**
     * Run the scraper to fetch and process news articles
     */
    public function runScraper()
    {
        try {
            // Sử dụng HTTP request đến API endpoint thống nhất
            $response = Http::post('http://localhost:55025/scraper/run');
            
            // Lấy dữ liệu phản hồi dưới dạng JSON
            $data = $response->json();
            
            // Kiểm tra trạng thái phản hồi
            if ($response->successful()) {
                // Kiểm tra xem phản hồi có chứa thông báo lỗi không
                if (isset($data['status']) && $data['status'] === 'error') {
                    if (strpos($data['message'] ?? '', 'đang chạy') !== false) {
                        // Trường hợp scraper đang chạy
                        \Log::info('Scraper is already running', [
                            'response' => $data
                        ]);
                        
                        // Tạo thông báo cho người dùng
                        $output = [
                            'Scraper đang chạy và không thể kích hoạt lại.',
                            'Vui lòng đợi quá trình hiện tại hoàn tất.',
                            'Kiểm tra trang kết quả để biết thêm chi tiết.'
                        ];
                        
                        // Lưu thông báo vào session
                        session(['scraper_output' => $output]);
                        
                        return redirect()->route('admin.approved-articles.scraper-results')
                            ->with('info', 'Scraper đang chạy và không thể kích hoạt lại.');
                    } else {
                        // Các trường hợp lỗi khác
                        throw new \Exception('API returned error: ' . ($data['message'] ?? $response->body()));
                    }
                }
                
                // Ghi log thông báo đã kích hoạt scraper
                \Log::info('Scraper triggered successfully via API', [
                    'response' => $data
                ]);
                
                // Tạo mảng output mẫu để hiển thị
                $output = [
                    'Scraper đã được kích hoạt và đang chạy trong nền.',
                    'Quá trình sẽ tiếp tục chạy ngay cả khi bạn rời khỏi trang này.',
                    'Bài viết sẽ được tự động thu thập và import vào hệ thống.',
                    'Vui lòng quay lại danh sách bài viết sau vài phút để xem kết quả.'
                ];
                
                // Lưu output mẫu vào session
                session(['scraper_output' => $output]);
                
                return redirect()->route('admin.approved-articles.scraper-results')
                    ->with('success', 'Đã kích hoạt scraper thành công. Quá trình đang chạy trong nền.');
            } else {
                throw new \Exception('API returned error: ' . $response->body());
            }
                
        } catch (\Exception $e) {
            \Log::error('Error triggering scraper', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi kích hoạt scraper: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the results of the scraper execution
     */
    public function scraperResults()
    {
        $output = session('scraper_output', []);
        // Đảm bảo luôn có dữ liệu để hiển thị
        if (empty($output)) {
            $output = [
                'Không có dữ liệu output từ lần chạy gần nhất của scraper.',
                'Bạn có thể chạy scraper bằng cách nhấn nút "Chạy lại Scraper" bên dưới.'
            ];
        }
        
        // Kiểm tra xem có log output không
        $log_output = $this->getScraperLogOutput();
        if (!empty($log_output)) {
            $output = array_merge($output, ['---', 'Log output từ file:'], $log_output);
        }
        
        return view('admin.approved-articles.scraper-results', compact('output'));
    }
    
    /**
     * Check scraper status and return log output
     */
    private function getScraperLogOutput()
    {
        $log_file = storage_path('logs/scraper_output.log');
        if (file_exists($log_file)) {
            // Đọc 50 dòng cuối cùng của file log
            $lines = [];
            $fp = fopen($log_file, 'r');
            if ($fp) {
                // Đọc file vào mảng và lấy tối đa 50 dòng cuối
                $position = -1;
                $line_count = 0;
                $max_lines = 50;
                
                while ($line_count < $max_lines && fseek($fp, $position, SEEK_END) >= 0) {
                    $char = fgetc($fp);
                    if ($char === "\n") {
                        $line_count++;
                    }
                    $position--;
                }
                
                // Nếu đã đọc đủ số dòng hoặc đã đọc hết file
                if ($line_count < $max_lines || fseek($fp, $position, SEEK_END) < 0) {
                    rewind($fp); // Quay về đầu file
                }
                
                // Đọc các dòng
                while (!feof($fp) && count($lines) < $max_lines) {
                    $line = fgets($fp);
                    if ($line !== false) {
                        $lines[] = trim($line);
                    }
                }
                fclose($fp);
            }
            return $lines;
        }
        return [];
    }

    /**
     * Check the status of running scraper job (AJAX endpoint)
     */
    public function checkScraperStatus()
    {
        $log_output = $this->getScraperLogOutput();
        $is_running = false;
        $status_message = '';
        
        try {
            // Kiểm tra trạng thái qua API endpoint thống nhất
            $response = Http::get('http://localhost:55025/scraper/health');
            
            if ($response->successful()) {
                $data = $response->json();
                $is_running = $data['running'] ?? false;
                
                // Lấy thêm thông tin từ API response nếu có
                if (isset($data['last_run'])) {
                    $last_run_time = $data['last_run'];
                    if ($last_run_time) {
                        $last_run = \Carbon\Carbon::parse($last_run_time);
                        $status_message = 'Lần chạy gần nhất: ' . $last_run->format('H:i:s d/m/Y');
                    }
                }
                
                // Log thông tin debug
                \Log::debug('Scraper status check response', [
                    'data' => $data,
                    'is_running' => $is_running
                ]);
            } else {
                \Log::warning('Error response from scraper health check', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error checking scraper status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return response()->json([
            'is_running' => $is_running,
            'status_message' => $status_message,
            'log_output' => $log_output,
            'last_updated' => now()->format('H:i:s - d/m/Y')
        ]);
    }

    /**
     * Run the article rewriter to process articles
     */
    public function runRewriter(Request $request)
    {
        try {
            // Sử dụng HTTP request đến API endpoint thống nhất
            $response = Http::post('http://localhost:55025/rewrite/run');
            
            if ($response->successful()) {
                // Ghi log thông báo đã kích hoạt rewriter
                \Log::info('Article rewriter triggered successfully via API', [
                    'response' => $response->json()
                ]);
                
                // Tạo mảng output mẫu để hiển thị
                $output = [
                    'Module rewriter đã được kích hoạt và đang chạy trong nền.',
                    'Quá trình sẽ tiếp tục chạy ngay cả khi bạn rời khỏi trang này.',
                    'Các bài viết sẽ được tự động viết lại và import vào hệ thống.',
                    'Vui lòng kiểm tra danh sách bài viết đã viết lại sau vài phút.'
                ];
                
                // Lưu output mẫu vào session
                session(['rewriter_output' => $output]);
                
                return redirect()->route('admin.approved-articles.rewriter-results')
                    ->with('success', 'Đã kích hoạt module rewriter thành công. Quá trình đang chạy trong nền.');
            } else {
                throw new \Exception('API returned error: ' . $response->body());
            }
                
        } catch (\Exception $e) {
            \Log::error('Error triggering rewriter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi kích hoạt rewriter: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the results of the rewriter execution
     */
    public function rewriterResults()
    {
        $output = session('rewriter_output', []);
        // Đảm bảo luôn có dữ liệu để hiển thị
        if (empty($output)) {
            $output = [
                'Không có dữ liệu output từ lần chạy gần nhất của rewriter.',
                'Bạn có thể chạy rewriter bằng cách nhấn nút "Chạy Rewriter" bên dưới.'
            ];
        }
        
        // Kiểm tra xem có log output không
        $log_output = $this->getRewriterLogOutput();
        if (!empty($log_output)) {
            $output = array_merge($output, ['---', 'Log output từ file:'], $log_output);
            
            // Kiểm tra nếu có thông báo thành công trong log
            $log_text = implode(' ', $log_output);
            if (strpos($log_text, 'Successfully rewritten and saved') !== false || 
                strpos($log_text, 'Saved rewritten article') !== false) {
                session()->flash('success', 'Quá trình viết lại bài viết đã hoàn thành thành công!');
            }
        }
        
        return view('admin.approved-articles.rewriter-results', compact('output'));
    }
    
    /**
     * Check rewriter status and return log output
     */
    private function getRewriterLogOutput()
    {
        $log_file = storage_path('logs/rewriter_output.log');
        if (file_exists($log_file)) {
            // Đọc 50 dòng cuối cùng của file log
            $lines = [];
            $fp = fopen($log_file, 'r');
            if ($fp) {
                // Đọc file vào mảng và lấy tối đa 50 dòng cuối
                $position = -1;
                $line_count = 0;
                $max_lines = 50;
                
                while ($line_count < $max_lines && fseek($fp, $position, SEEK_END) >= 0) {
                    $char = fgetc($fp);
                    if ($char === "\n") {
                        $line_count++;
                    }
                    $position--;
                }
                
                // Nếu đã đọc đủ số dòng hoặc đã đọc hết file
                if ($line_count < $max_lines || fseek($fp, $position, SEEK_END) < 0) {
                    rewind($fp); // Quay về đầu file
                }
                
                // Đọc các dòng
                while (!feof($fp) && count($lines) < $max_lines) {
                    $line = fgets($fp);
                    if ($line !== false) {
                        $lines[] = trim($line);
                    }
                }
                fclose($fp);
            }
            return $lines;
        }
        return [];
    }

    /**
     * Check the status of running rewriter job (AJAX endpoint)
     */
    public function checkRewriterStatus()
    {
        $log_output = $this->getRewriterLogOutput();
        $is_running = false;
        
        try {
            // Kiểm tra trạng thái qua API endpoint thống nhất
            $response = Http::get('http://localhost:55025/rewrite/health');
            
            if ($response->successful()) {
                $data = $response->json();
                $is_running = $data['running'] ?? false;
            }
        } catch (\Exception $e) {
            \Log::error('Error checking rewriter status', [
                'error' => $e->getMessage()
            ]);
        }
        
        return response()->json([
            'is_running' => $is_running,
            'log_output' => $log_output,
            'last_updated' => now()->format('H:i:s - d/m/Y')
        ]);
    }

    /**
     * Update article media (AJAX)
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ApprovedArticle $approvedArticle
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMedia(Request $request, ApprovedArticle $approvedArticle)
    {
        Log::info('Updating media for article via AJAX', [
            'article_id' => $approvedArticle->id,
            'request_data' => $request->all()
        ]);

        try {
            // Get media IDs from the request
            $mediaIds = $request->input('content_media_ids', '');
            
            if (empty($mediaIds)) {
                Log::warning('No media IDs provided for update', [
                    'article_id' => $approvedArticle->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No media IDs provided'
                ]);
            }
            
            // Process the article media relationship
            $this->processArticleMedia($approvedArticle, $mediaIds);
            
            // Refresh the article from database to make sure we have the latest content
            $approvedArticle->refresh();
            
            return response()->json([
                'success' => true,
                'message' => 'Media IDs updated successfully',
                'media_count' => is_array($mediaIds) ? count($mediaIds) : count(explode(',', $mediaIds)),
                'article_id' => $approvedArticle->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating media for article', [
                'article_id' => $approvedArticle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating media: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update featured image for a given article (AJAX)
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ApprovedArticle $approvedArticle
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFeaturedImage(Request $request, ApprovedArticle $approvedArticle)
    {
        Log::info('Updating featured image for article via AJAX', [
            'article_id' => $approvedArticle->id,
            'request_data' => $request->all()
        ]);

        try {
            // Validate the featured image ID
            $featuredImageId = (int) $request->input('featured_image_id');
            
            if ($featuredImageId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid featured image ID provided'
                ]);
            }
            
            // Check if the media exists and is an image
            $media = Media::find($featuredImageId);
            
            if (!$media || $media->type !== 'image') {
                Log::warning('Invalid or non-image media ID provided for featured image', [
                    'article_id' => $approvedArticle->id,
                    'featured_image_id' => $featuredImageId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'The selected media is not a valid image'
                ]);
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            try {
                // Update using article_featured_images table (new approach)
                // First, check if there's already a main featured image
                $existingMainImage = $approvedArticle->mainFeaturedImage;
                
                if ($existingMainImage) {
                    // Update existing main featured image
                    $existingMainImage->update([
                        'media_id' => $featuredImageId,
                        'alt_text' => $media->name,
                    ]);
                    
                    $featuredImage = $existingMainImage;
                } else {
                    // Create new main featured image
                    $featuredImage = ArticleFeaturedImage::create([
                        'article_id' => $approvedArticle->id,
                        'media_id' => $featuredImageId,
                        'position' => 'featured',
                        'is_main' => true,
                        'alt_text' => $media->name,
                        'caption' => '',
                    ]);
                }
                
                // For backward compatibility, also update the featured_image_id field
                $approvedArticle->featured_image_id = $featuredImageId;
                $approvedArticle->save();
                
                DB::commit();
                
                Log::info('Featured image updated successfully', [
                    'article_id' => $approvedArticle->id,
                    'featured_image_id' => $featuredImageId,
                    'article_featured_image_id' => $featuredImage->id,
                    'image_url' => $media->url
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Featured image updated successfully',
                    'featured_image' => [
                        'id' => $media->id,
                        'name' => $media->name,
                        'url' => $media->url
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error updating featured image for article', [
                'article_id' => $approvedArticle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating featured image: ' . $e->getMessage()
            ]);
        }
    }
} 