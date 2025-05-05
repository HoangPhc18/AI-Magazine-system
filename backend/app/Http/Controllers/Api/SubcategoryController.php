<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the subcategories.
     */
    public function index()
    {
        $subcategories = Subcategory::with('parentCategory')->get();
        return response()->json($subcategories);
    }

    /**
     * Display the specified subcategory.
     */
    public function show(Subcategory $subcategory)
    {
        $subcategory->load('parentCategory');
        return response()->json($subcategory);
    }

    /**
     * Get articles for a specific subcategory.
     */
    public function articles(Subcategory $subcategory)
    {
        $articles = ApprovedArticle::where('subcategory_id', $subcategory->id)
            ->where('status', 'approved')
            ->latest()
            ->get();
        return response()->json($articles);
    }
} 