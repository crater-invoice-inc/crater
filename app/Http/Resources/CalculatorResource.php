<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalculatorResource extends JsonResource
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
            'name' => $this->name,
            'notes' => $this->notes,
            'company_id' => $this->company_id,
            'creator_id' => $this->creator_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'calculator_items' => CalculatorItemResource::collection($this->whenLoaded('calculatorItems')),
            'items' => CalculatorItemResource::collection($this->whenLoaded('calculatorItems')),
            'creator' => $this->when($this->creator()->exists(), function () {
                return new UserResource($this->creator);
            }),
        ];
    }
}
