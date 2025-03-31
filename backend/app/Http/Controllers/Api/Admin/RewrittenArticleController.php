<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewrittenArticle;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewrittenArticleController extends Controller
{
    public function index()
    {
        $articles = RewrittenArticle::with('category')->get();
        return response()->json($articles);
    }

    public function show(RewrittenArticle $rewrittenArticle)
    {
        return response()->json($rewrittenArticle->load('category'));
    }

    public function update(Request $request, RewrittenArticle $rewrittenArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        $rewrittenArticle->update($validated);
        return response()->json($rewrittenArticle->load('category'));
    }

    public function approve(RewrittenArticle $rewrittenArticle)
    {
        try {
            DB::beginTransaction();

            // Tạo bài viết mới trong bảng approved_articles
            ApprovedArticle::create([
                'title' => $rewrittenArticle->title,
                'content' => $rewrittenArticle->content,
                'category_id' => $rewrittenArticle->category_id,
                'status' => 'approved'
            ]);

            // Xóa bài viết từ bảng rewritten_articles
            $rewrittenArticle->delete();

            DB::commit();
            return response()->json(['message' => 'Article approved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error approving article'], 500);
        }
    }

    public function reject(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->update(['status' => 'rejected']);
        return response()->json(['message' => 'Article rejected successfully']);
    }

    public function destroy(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }
} 