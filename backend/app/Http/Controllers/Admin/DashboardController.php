<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'articles' => ApprovedArticle::count(),
            'categories' => Category::all()
        ];

        return view('admin.dashboard', compact('stats'));
    }
} 