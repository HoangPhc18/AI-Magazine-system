<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Article\ApproveRequest;
use App\Http\Requests\Api\Admin\Article\UpdateRequest;
use App\Http\Resources\ArticleResource;
use App\Models\RewrittenArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovedArticle;

class ArticleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $articles = RewrittenArticle::with(['originalArticle.category'])
            ->where('status', '!=', 'approved')
            ->latest()
            ->paginate(10);

        return ArticleResource::collection($articles);
    }

    public function show(RewrittenArticle $rewrittenArticle): ArticleResource
    {
        return new ArticleResource($rewrittenArticle->load(['originalArticle.category']));
    }

    public function approve(ApproveRequest $request, RewrittenArticle $rewrittenArticle): JsonResponse
    {
        // Kiểm tra nếu trạng thái là approved, tạo ApprovedArticle và xóa bài viết
        if ($request->status === 'approved') {
            try {
                DB::beginTransaction();
                
                // Tạo bài viết đã duyệt trong ApprovedArticle
                ApprovedArticle::create([
                    'title' => $rewrittenArticle->title,
                    'slug' => $rewrittenArticle->slug,
                    'content' => $rewrittenArticle->content,
                    'meta_title' => $rewrittenArticle->meta_title,
                    'meta_description' => $rewrittenArticle->meta_description,
                    'featured_image' => $rewrittenArticle->featured_image,
                    'user_id' => auth()->id(),
                    'category_id' => $rewrittenArticle->category_id,
                    'original_article_id' => $rewrittenArticle->original_article_id,
                    'status' => 'published',
                    'ai_generated' => $rewrittenArticle->ai_generated ?? false,
                    'published_at' => now()
                ]);
                
                // Xóa bài viết đã được duyệt
                $rewrittenArticleId = $rewrittenArticle->id;
                $rewrittenArticle->forceDelete();
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Article approved and published successfully'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'message' => 'Error approving article: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Cập nhật trạng thái nếu không phải approved
            $rewrittenArticle->update([
                'status' => $request->status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            return response()->json([
                'message' => 'Article status updated successfully',
                'article' => new ArticleResource($rewrittenArticle->load(['originalArticle.category']))
            ]);
        }
    }

    public function update(UpdateRequest $request, RewrittenArticle $rewrittenArticle): JsonResponse
    {
        $rewrittenArticle->update([
            'rewritten_content' => $request->rewritten_content,
        ]);

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => new ArticleResource($rewrittenArticle->load(['originalArticle.category']))
        ]);
    }

    public function destroy(RewrittenArticle $rewrittenArticle): JsonResponse
    {
        $rewrittenArticle->delete();

        return response()->json([
            'message' => 'Article deleted successfully'
        ]);
    }
} 