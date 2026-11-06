<?php

namespace Crater\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;

class EstimateItemResource extends JsonResource
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
            'description' => $this->description,
            'deliverables' => $this->deliverables,
            'delivery_delay' => $this->delivery_delay,
            'terms' => $this->terms,
            'discount_type' => $this->discount_type,
            'quantity' => $this->quantity,
            'unit_name' => $this->unit_name,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'price' => $this->price,
            'tax' => $this->tax,
            'total' => $this->total,
            'item_id' => $this->item_id,
            'estimate_id' => $this->estimate_id,
            'company_id' => $this->company_id,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_price' => $this->base_price,
            'base_tax' => $this->base_tax,
            'base_total' => $this->base_total,
            'taxes' => $this->when($this->taxes()->exists(), function () {
                return TaxResource::collection($this->taxes);
            }),
            'fields' => $this->when($this->fields()->exists(), function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
        ];
    }
}
