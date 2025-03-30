<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'previous_content' => $this->previous_content,
            'editor' => new UserResource($this->whenLoaded('editor')),
            'edited_at' => $this->edited_at,
        ];
    }
} 