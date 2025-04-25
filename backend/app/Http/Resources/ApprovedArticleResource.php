<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApprovedArticleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'featured_image_id' => $this->featured_image_id,
            'featured_image' => $this->featured_image,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name
                ];
            }),
            'original_article' => $this->whenLoaded('originalArticle', function () {
                return [
                    'id' => $this->originalArticle->id,
                    'title' => $this->originalArticle->title
                ];
            }),
            'featured_image_media' => $this->whenLoaded('featuredImage', function () {
                return [
                    'id' => $this->featuredImage->id,
                    'name' => $this->featuredImage->name,
                    'url' => $this->featuredImage->url,
                    'thumbnail' => $this->featuredImage->thumbnail
                ];
            }),
            'status' => $this->status,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
} 