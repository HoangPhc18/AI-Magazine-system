<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewrittenArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'original_article' => $this->whenLoaded('originalArticle'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
