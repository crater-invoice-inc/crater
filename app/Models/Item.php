<?php

namespace Crater\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Item extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formattedCreatedAt',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo('Crater\Models\User', 'creator_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeWhereSearch($query, $search)
    {
        return $query->where('items.name', 'LIKE', '%'.$search.'%');
    }

    public function scopeWherePrice($query, $price)
    {
        return $query->where('items.price', $price);
    }

    public function scopeWhereUnit($query, $unit_id)
    {
        return $query->where('items.unit_id', $unit_id);
    }

    public function scopeWhereActive($query, $active = true)
    {
        return $query->where('items.is_active', $active);
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWhereItem($query, $item_id)
    {
        $query->orWhere('id', $item_id);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('price')) {
            $query->wherePrice($filters->get('price'));
        }

        if ($filters->get('unit_id')) {
            $query->whereUnit($filters->get('unit_id'));
        }

        // Accepts 1 (active only), 0 (inactive only); absent or empty
        // keeps the unfiltered behaviour for existing consumers.
        if ($filters->get('active') !== null && $filters->get('active') !== '') {
            $query->whereActive((bool) $filters->get('active'));
        }

        if ($filters->get('item_id')) {
            $query->whereItem($filters->get('item_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'name';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
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

    public function taxes()
    {
        return $this->hasMany(Tax::class)
            ->where('invoice_item_id', null)
            ->where('estimate_item_id', null);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('items.company_id', request()->header('company'));
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function estimateItems()
    {
        return $this->hasMany(EstimateItem::class);
    }

    public static function createItem($request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->header('company');
        $data['creator_id'] = Auth::id();
        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));
        $data['currency_id'] = $company_currency;
        $item = self::create($data);

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $item->tax_per_item = true;
                $item->save();
                $tax['company_id'] = $request->header('company');
                $item->taxes()->create($tax);
            }
        }

        $item = self::with('taxes')->find($item->id);

        return $item;
    }

    public function updateItem($request)
    {
        $this->update($request->validated());

        $this->taxes()->delete();

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $this->tax_per_item = true;
                $this->save();
                $tax['company_id'] = $request->header('company');
                $this->taxes()->create($tax);
            }
        }

        return Item::with('taxes')->find($this->id);
    }
}
