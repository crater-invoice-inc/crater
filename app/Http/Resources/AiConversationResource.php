<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AiConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_id' => $this->document_id,
            'draft_key' => $this->draft_key,
            'company_id' => $this->company_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => AiMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
