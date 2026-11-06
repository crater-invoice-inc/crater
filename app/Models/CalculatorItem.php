<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'integer',
        'quantity' => 'float',
    ];

    public function calculator()
    {
        return $this->belongsTo(Calculator::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('calculator_items.company_id', request()->header('company'));
    }
}
