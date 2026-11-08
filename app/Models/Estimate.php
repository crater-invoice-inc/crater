<?php

namespace Crater\Models;

use App;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Crater\Mail\SendEstimateMail;
use Crater\Services\SerialNumberFormatter;
use Crater\Traits\GeneratesPdfTrait;
use Crater\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Vinkla\Hashids\Facades\Hashids;

class Estimate extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_VIEWED = 'VIEWED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'estimate_date',
        'expiry_date'
    ];

    protected $appends = [
        'formattedExpiryDate',
        'formattedEstimateDate',
        'estimatePdfUrl',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'total' => 'integer',
        'tax' => 'integer',
        'sub_total' => 'integer',
        'discount' => 'float',
        'discount_val' => 'integer',
        'exchange_rate' => 'float'
    ];

    public function getEstimatePdfUrlAttribute()
    {
        return url('/estimates/pdf/'.$this->unique_hash);
    }

    public function emailLogs()
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    public function items()
    {
        return $this->hasMany('Crater\Models\EstimateItem');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo('Crater\Models\User', 'creator_id');
    }

    public function company()
    {
        return $this->belongsTo('Crater\Models\Company');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function getFormattedExpiryDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->expiry_date)->format($dateFormat);
    }

    public function getFormattedEstimateDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->estimate_date)->format($dateFormat);
    }

    public function scopeEstimatesBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'estimates.estimate_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('estimates.status', $status);
    }

    public function scopeWhereEstimateNumber($query, $estimateNumber)
    {
        return $query->where('estimates.estimate_number', 'LIKE', '%'.$estimateNumber.'%');
    }

    public function scopeWhereEstimate($query, $estimate_id)
    {
        $query->orWhere('id', $estimate_id);
    }

    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('customer', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('company_name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('estimate_number')) {
            $query->whereEstimateNumber($filters->get('estimate_number'));
        }

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('estimate_id')) {
            $query->whereEstimate($filters->get('estimate_id'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->estimatesBetween($start, $end);
        }

        if ($filters->get('customer_id')) {
            $query->whereCustomer($filters->get('customer_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'sequence_number';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'desc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('estimates.company_id', request()->header('company'));
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->where('estimates.customer_id', $customer_id);
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public static function createEstimate($request)
    {
        $data = $request->getEstimatePayload();

        if ($request->has('estimateSend')) {
            $data['status'] = self::STATUS_SENT;
        }

        $estimate = self::create($data);
        $estimate->unique_hash = Hashids::connection(Estimate::class)->encode($estimate->id);
        $serial = (new SerialNumberFormatter())
            ->setModel($estimate)
            ->setCompany($estimate->company_id)
            ->setCustomer($estimate->customer_id)
            ->setNextNumbers();

        $estimate->sequence_number = $serial->nextSequenceNumber;
        $estimate->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $estimate->save();

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string)$data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($estimate);
        }

        self::createItems($estimate, $request, $estimate->exchange_rate);

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($estimate, $request, $estimate->exchange_rate);
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $estimate->addCustomFields($customFields);
        }

        return $estimate;
    }

    public function updateEstimate($request)
    {
        $data = $request->getEstimatePayload();

        $serial = (new SerialNumberFormatter())
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;

        $this->update($data);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string)$data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        $this->items->map(function ($item) {
            $fields = $item->fields()->get();

            $fields->map(function ($field) {
                $field->delete();
            });
        });

        $this->items()->delete();
        $this->taxes()->delete();

        self::createItems($this, $request, $this->exchange_rate);

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($this, $request, $this->exchange_rate);
        }

        if ($request->customFields) {
            $this->updateCustomFields($request->customFields);
        }

        return Estimate::with([
                'items.taxes',
                'items.fields',
                'items.fields.customField',
                'customer',
                'taxes'
            ])
            ->find($this->id);
    }

    public static function createItems($estimate, $request, $exchange_rate)
    {
        $estimateItems = $request->items;

        foreach ($estimateItems as $estimateItem) {
            $estimateItem['company_id'] = $request->header('company');
            $estimateItem['exchange_rate'] = $exchange_rate;
            $estimateItem['base_price'] = $estimateItem['price'] * $exchange_rate;
            $estimateItem['base_discount_val'] = $estimateItem['discount_val'] * $exchange_rate;
            $estimateItem['base_tax'] = $estimate['tax'] * $exchange_rate;
            $estimateItem['base_total'] = $estimateItem['total'] * $exchange_rate;

            $item = $estimate->items()->create($estimateItem);

            if (array_key_exists('taxes', $estimateItem) && $estimateItem['taxes']) {
                foreach ($estimateItem['taxes'] as $tax) {
                    if (gettype($tax['amount']) !== "NULL") {
                        $tax['company_id'] = $request->header('company');
                        $item->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $estimateItem) && $estimateItem['custom_fields']) {
                $item->addCustomFields($estimateItem['custom_fields']);
            }
        }
    }

    public static function createTaxes($estimate, $request, $exchange_rate)
    {
        $estimateTaxes = $request->taxes;

        foreach ($estimateTaxes as $tax) {
            if (gettype($tax['amount']) !== "NULL") {
                $tax['company_id'] = $request->header('company');
                $tax['exchange_rate'] = $exchange_rate;
                $tax['base_amount'] = $tax['amount'] * $exchange_rate;
                $tax['currency_id'] = $estimate->currency_id;

                $estimate->taxes()->create($tax);
            }
        }
    }

    public function sendEstimateData($data)
    {
        $data['estimate'] = $this->toArray();
        $data['user'] = $this->customer->toArray();
        $data['company'] = $this->company->toArray();
        $data['body'] = $this->getEmailBody($data['body']);
        $data['attach']['data'] = ($this->getEmailAttachmentSetting()) ? $this->getPDFData() : null;

        return $data;
    }

    public function send($data)
    {
        $data = $this->sendEstimateData($data);

        if ($this->status == Estimate::STATUS_DRAFT) {
            $this->status = Estimate::STATUS_SENT;
            $this->save();
        }

        \Mail::to($data['to'])->send(new SendEstimateMail($data));

        return [
            'success' => true,
            'type' => 'send',
        ];
    }

    public function getPDFData()
    {
        $taxes = collect();

        if ($this->tax_per_item === 'YES') {
            foreach ($this->items as $item) {
                foreach ($item->taxes as $tax) {
                    $found = $taxes->filter(function ($item) use ($tax) {
                        return $item->tax_type_id == $tax->tax_type_id;
                    })->first();

                    if ($found) {
                        $found->amount += $tax->amount;
                    } else {
                        $taxes->push($tax);
                    }
                }
            }
        } else {
            foreach ($this->taxes as $tax) {
                $taxes->push($tax);
            }
        }

        $estimateTemplate = self::find($this->id)->template_name;

        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);
        $customFields = CustomField::where('model_type', 'Item')->get();

        App::setLocale($locale);

        $logo = $company->logo_path;

        view()->share([
            'estimate' => $this,
            'customFields' => $customFields,
            'logo' => $logo ?? null,
            'company_address' => $this->getCompanyAddress(),
            'shipping_address' => $this->getCustomerShippingAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'taxes' => $taxes,
        ]);

        if (request()->has('preview')) {
            return view('app.pdf.estimate.'.$estimateTemplate);
        }

        return PDF::loadView('app.pdf.estimate.'.$estimateTemplate);
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('estimate_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerShippingAddress()
    {
        if ($this->customer && (! $this->customer->shippingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('estimate_shipping_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('estimate_billing_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    public function getEmailAttachmentSetting()
    {
        $estimateAsAttachment = CompanySetting::getSetting('estimate_email_attachment', $this->company_id);

        if ($estimateAsAttachment == 'NO') {
            return false;
        }

        return true;
    }

    public function getEmailBody($body)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());

        $body = strtr($body, $values);

        return preg_replace('/{(.*?)}/', '', $body);
    }

    public function getExtraFields()
    {
        return [
            '{ESTIMATE_DATE}' => $this->formattedEstimateDate,
            '{ESTIMATE_EXPIRY_DATE}' => $this->formattedExpiryDate,
            '{ESTIMATE_NUMBER}' => $this->estimate_number,
            '{ESTIMATE_REF_NUMBER}' => $this->reference_number,
        ];
    }

    public static function estimateTemplates()
    {
        $templates = Storage::disk('views')->files('/app/pdf/estimate');
        $estimateTemplates = [];

        foreach ($templates as $key => $template) {
            $templateName = Str::before(basename($template), '.blade.php');
            $estimateTemplates[$key]['name'] = $templateName;
            $estimateTemplates[$key]['path'] = vite_asset('/img/PDF/'.$templateName.'.png');
        }

        return $estimateTemplates;
    }

    public function getInvoiceTemplateName()
    {
        $templateName = Str::replace('estimate', 'invoice', $this->template_name);

        $name = [];

        foreach (Invoice::invoiceTemplates() as $template) {
            $name[] = $template['name'];
        }

        if (in_array($templateName, $name) == false) {
            $templateName = 'invoice1';
        }

        return $templateName;
    }

    public function checkForEstimateConvertAction()
    {
        $convertEstimateAction = CompanySetting::getSetting(
            'estimate_convert_action',
            $this->company_id
        );

        if ($convertEstimateAction === 'delete_estimate') {
            $this->delete();
        }

        if ($convertEstimateAction === 'mark_estimate_as_accepted') {
            $this->status = self::STATUS_ACCEPTED;
            $this->save();
        }

        return true;
    }
}
