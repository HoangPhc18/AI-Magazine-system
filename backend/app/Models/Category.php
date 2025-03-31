<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function rewrittenArticles()
    {
        return $this->hasMany(RewrittenArticle::class);
    }

    public function approvedArticles()
    {
        return $this->hasMany(ApprovedArticle::class);
    }
} 