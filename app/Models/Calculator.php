<?php

namespace Crater\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Calculator extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'total',
        'itemsCount',
    ];

    public const CATEGORIES = [
        'pre_production',
        'production',
        'post_production',
        'travel',
        'production_expenses',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo('Crater\Models\User', 'creator_id');
    }

    public function calculatorItems()
    {
        return $this->hasMany(CalculatorItem::class);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('calculators.company_id', request()->header('company'));
    }

    public function scopeWhereSearch($query, $search)
    {
        return $query->where('calculators.name', 'LIKE', '%'.$search.'%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'created_at';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'desc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', request()->header('company'));

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getTotalAttribute()
    {
        return (int) $this->calculatorItems->sum(function ($item) {
            return round($item->unit_price * $item->quantity);
        });
    }

    public function getItemsCountAttribute()
    {
        return $this->calculatorItems->count();
    }

    public static function createCalculator($request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->header('company');
        $data['creator_id'] = Auth::id();

        $calculator = self::create($data);

        $calculator->saveItems($request);

        return self::with('calculatorItems')->find($calculator->id);
    }

    public function updateCalculator($request)
    {
        $this->update($request->validated());

        $this->saveItems($request);

        return self::with('calculatorItems')->find($this->id);
    }

    public function saveItems($request)
    {
        $this->calculatorItems()->delete();

        if ($request->has('calculator_items')) {
            foreach ($request->calculator_items as $item) {
                $item['company_id'] = $request->header('company');
                $this->calculatorItems()->create($item);
            }
        }
    }
}
