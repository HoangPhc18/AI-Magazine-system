<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Article;
use App\Models\RewrittenArticle;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_articles' => Article::count(),
                'pending_articles' => RewrittenArticle::where('status', 'pending')->count(),
                'approved_articles' => RewrittenArticle::where('status', 'approved')->count(),
                'recent_articles' => RewrittenArticle::with(['article', 'category'])
                    ->latest()
                    ->take(5)
                    ->get(),
                'recent_users' => User::latest()
                    ->take(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thống kê',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 