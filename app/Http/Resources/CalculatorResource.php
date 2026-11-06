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
            'total' => $this->total,
            'items_count' => $this->itemsCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'calculator_items' => CalculatorItemResource::collection($this->whenLoaded('calculatorItems')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'creator' => $this->when($this->creator()->exists(), function () {
                return new UserResource($this->creator);
            }),
        ];
    }
}
