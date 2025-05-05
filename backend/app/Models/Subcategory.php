<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_category_id'
    ];

    /**
     * Get the parent category that owns the subcategory.
     */
    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    /**
     * Get the articles for this subcategory.
     */
    public function articles()
    {
        return $this->hasMany(ApprovedArticle::class, 'subcategory_id');
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Ensure parent_category_id is included
        if (!isset($array['parent_category_id']) && $this->parent_category_id) {
            $array['parent_category_id'] = $this->parent_category_id;
        }
        
        return $array;
    }
} 