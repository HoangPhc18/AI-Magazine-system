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
            'rewritten_content' => $this->rewritten_content,
            'status' => $this->status,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'edit_history' => EditHistoryResource::collection($this->whenLoaded('editHistory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
