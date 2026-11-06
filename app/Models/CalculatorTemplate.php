<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorTemplate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'integer',
        'is_default' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('calculator_templates.company_id', request()->header('company'));
    }

    public function scopeWhereCategory($query, $category)
    {
        return $query->where('calculator_templates.category', $category);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('category')) {
            $query->whereCategory($filters->get('category'));
        }
    }

    public static function createTemplate($request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->header('company');

        return self::create($data);
    }

    public function updateTemplate($request)
    {
        $this->update($request->validated());

        return $this;
    }
}
