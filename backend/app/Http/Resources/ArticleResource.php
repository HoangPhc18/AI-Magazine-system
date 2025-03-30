<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'source' => $this->source,
            'content' => $this->content,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'rewritten_article' => new RewrittenArticleResource($this->whenLoaded('rewrittenArticle')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 