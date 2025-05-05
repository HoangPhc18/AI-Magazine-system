<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    public function articles()
    {
        return $this->hasMany(ApprovedArticle::class);
    }

    public function rewrittenArticles()
    {
        return $this->hasMany(RewrittenArticle::class);
    }

    /**
     * Get the subcategories for this category.
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'parent_category_id');
    }
} 