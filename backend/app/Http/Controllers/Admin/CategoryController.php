<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::withCount(['articles', 'rewrittenArticles', 'subcategories'])->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories'],
            'description' => ['nullable', 'string'],
            'subcategories' => ['nullable', 'array'],
            'subcategories.*.name' => ['required', 'string', 'max:255'],
            'subcategories.*.description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        DB::beginTransaction();
        try {
            // Create the parent category
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description']
            ]);

            // Create subcategories if any
            if (isset($validated['subcategories']) && count($validated['subcategories']) > 0) {
                foreach ($validated['subcategories'] as $subcategoryData) {
                    Subcategory::create([
                        'name' => $subcategoryData['name'],
                        'slug' => Str::slug($subcategoryData['name']),
                        'description' => $subcategoryData['description'] ?? null,
                        'parent_category_id' => $category->id
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.categories.index')
                ->with('success', 'Danh mục đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo danh mục: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $category->load('subcategories');
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'subcategories' => ['nullable', 'array'],
            'subcategories.*.id' => ['nullable', 'exists:subcategories,id'],
            'subcategories.*.name' => ['required', 'string', 'max:255'],
            'subcategories.*.description' => ['nullable', 'string'],
            'delete_subcategory_ids' => ['nullable', 'array'],
            'delete_subcategory_ids.*' => ['nullable', 'exists:subcategories,id'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        DB::beginTransaction();
        try {
            // Update the category
            $category->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description']
            ]);

            // Handle subcategories updates
            if (isset($validated['subcategories'])) {
                foreach ($validated['subcategories'] as $subcategoryData) {
                    // If ID exists, update the subcategory
                    if (isset($subcategoryData['id'])) {
                        $subcategory = Subcategory::find($subcategoryData['id']);
                        if ($subcategory) {
                            $subcategory->update([
                                'name' => $subcategoryData['name'],
                                'slug' => Str::slug($subcategoryData['name']),
                                'description' => $subcategoryData['description'] ?? null
                            ]);
                        }
                    } else {
                        // Create new subcategory
                        Subcategory::create([
                            'name' => $subcategoryData['name'],
                            'slug' => Str::slug($subcategoryData['name']),
                            'description' => $subcategoryData['description'] ?? null,
                            'parent_category_id' => $category->id
                        ]);
                    }
                }
            }

            // Handle deleted subcategories
            if (isset($validated['delete_subcategory_ids'])) {
                Subcategory::whereIn('id', $validated['delete_subcategory_ids'])->delete();
            }

            DB::commit();

            return redirect()->route('admin.categories.index')
                ->with('success', 'Danh mục đã được cập nhật thành công.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật danh mục: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Check if the category has articles
        $articlesCount = $category->articles()->count() + $category->rewrittenArticles()->count();

        if ($articlesCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('error', "Không thể xóa danh mục này. Có {$articlesCount} bài viết đang thuộc danh mục này.");
        }

        // Delete subcategories first
        $category->subcategories()->delete();
        
        // Force delete the category
        $category->forceDelete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được xóa thành công.');
    }

    /**
     * Get subcategories for a given category
     */
    public function getSubcategories(Category $category)
    {
        $subcategories = $category->subcategories()->orderBy('name')->get();
        return response()->json($subcategories);
    }
}
