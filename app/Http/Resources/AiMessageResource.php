<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AiMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Assistant messages carry the normalized proposal in metadata so the
     * panel can re-display a past proposal without calling the model again.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
