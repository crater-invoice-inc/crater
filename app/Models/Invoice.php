<?php

namespace Crater\Models;

use App;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Crater\Mail\SendInvoiceMail;
use Crater\Services\SerialNumberFormatter;
use Crater\Traits\GeneratesPdfTrait;
use Crater\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Vinkla\Hashids\Facades\Hashids;

class Invoice extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_VIEWED = 'VIEWED';
    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_UNPAID = 'UNPAID';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'invoice_date',
        'due_date'
    ];

    protected $casts = [
        'total' => 'integer',
        'tax' => 'integer',
        'sub_total' => 'integer',
        'discount' => 'float',
        'discount_val' => 'integer',
        'exchange_rate' => 'float'
    ];

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedInvoiceDate',
        'formattedDueDate',
        'invoicePdfUrl',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function emailLogs()
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    public function items()
    {
        return $this->hasMany('Crater\Models\InvoiceItem');
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function recurringInvoice()
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getInvoicePdfUrlAttribute()
    {
        return url('/invoices/pdf/'.$this->unique_hash);
    }

    public function getPaymentModuleEnabledAttribute()
    {
        if (Module::has('Payments')) {
            return Module::isEnabled('Payments');
        }

        return false;
    }

    public function getAllowEditAttribute()
    {
        $retrospective_edit = CompanySetting::getSetting('retrospective_edits', $this->company_id);

        $allowed = true;

        $status = [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_COMPLETED,
        ];

        if ($retrospective_edit == 'disable_on_invoice_sent' && (in_array($this->status, $status)) && ($this->paid_status === Invoice::STATUS_PARTIALLY_PAID || $this->paid_status === Invoice::STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_partial_paid' && ($this->paid_status === Invoice::STATUS_PARTIALLY_PAID || $this->paid_status === Invoice::STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_paid' && $this->paid_status === Invoice::STATUS_PAID) {
            $allowed = false;
        }

        return $allowed;
    }

    public function getPreviousStatus()
    {
        if ($this->viewed) {
            return self::STATUS_VIEWED;
        } elseif ($this->sent) {
            return self::STATUS_SENT;
        } else {
            return self::STATUS_DRAFT;
        }
    }

    public function getFormattedNotesAttribute($value)
    {
        return $this->getNotes();
    }

    public function getPaymentInformation()
    {
        if ($this->payment_information === null || $this->payment_information === '') {
            return null;
        }

        return $this->getFormattedString($this->payment_information);
    }

    public function getFormattedPaymentInformationAttribute($value)
    {
        return $this->getPaymentInformation();
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getFormattedDueDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->due_date)->format($dateFormat);
    }

    public function getFormattedInvoiceDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->invoice_date)->format($dateFormat);
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('invoices.status', $status);
    }

    public function scopeWherePaidStatus($query, $status)
    {
        return $query->where('invoices.paid_status', $status);
    }

    public function scopeWhereDueStatus($query, $status)
    {
        return $query->whereIn('invoices.paid_status', [
            self::STATUS_UNPAID,
            self::STATUS_PARTIALLY_PAID,
        ]);
    }

    public function scopeWhereInvoiceNumber($query, $invoiceNumber)
    {
        return $query->where('invoices.invoice_number', 'LIKE', '%'.$invoiceNumber.'%');
    }

    public function scopeInvoicesBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'invoices.invoice_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
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

        if ($filters->get('status')) {
            if (
                $filters->get('status') == self::STATUS_UNPAID ||
                $filters->get('status') == self::STATUS_PARTIALLY_PAID ||
                $filters->get('status') == self::STATUS_PAID
            ) {
                $query->wherePaidStatus($filters->get('status'));
            } elseif ($filters->get('status') == 'DUE') {
                $query->whereDueStatus($filters->get('status'));
            } else {
                $query->whereStatus($filters->get('status'));
            }
        }

        if ($filters->get('paid_status')) {
            $query->wherePaidStatus($filters->get('status'));
        }

        if ($filters->get('invoice_id')) {
            $query->whereInvoice($filters->get('invoice_id'));
        }

        if ($filters->get('invoice_number')) {
            $query->whereInvoiceNumber($filters->get('invoice_number'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->invoicesBetween($start, $end);
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

    public function scopeWhereInvoice($query, $invoice_id)
    {
        $query->orWhere('id', $invoice_id);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('invoices.company_id', request()->header('company'));
    }

    public function scopeWhereCompanyId($query, $company)
    {
        $query->where('invoices.company_id', $company);
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->where('invoices.customer_id', $customer_id);
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public static function createInvoice($request)
    {
        $data = $request->getInvoicePayload();

        if ($request->has('invoiceSend')) {
            $data['status'] = Invoice::STATUS_SENT;
        }

        $invoice = Invoice::create($data);

        $serial = (new SerialNumberFormatter())
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
        $invoice->save();

        self::createItems($invoice, $request->items);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string)$data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($invoice);
        }

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($invoice, $request->taxes);
        }

        if ($request->customFields) {
            $invoice->addCustomFields($request->customFields);
        }

        $invoice = Invoice::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes'
        ])
            ->find($invoice->id);

        return $invoice;
    }

    public function updateInvoice($request)
    {
        $serial = (new SerialNumberFormatter())
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data = $request->getInvoicePayload();
        $oldTotal = $this->total;

        $total_paid_amount = $this->total - $this->due_amount;

        if ($total_paid_amount > 0 && $this->customer_id !== $request->customer_id) {
            return 'customer_cannot_be_changed_after_payment_is_added';
        }

        if ($request->total < $total_paid_amount) {
            return 'total_invoice_amount_must_be_more_than_paid_amount';
        }

        if ($oldTotal != $request->total) {
            $oldTotal = (int) round($request->total) - (int) $oldTotal;
        } else {
            $oldTotal = 0;
        }

        $data['due_amount'] = ($this->due_amount + $oldTotal);
        $data['base_due_amount'] = $data['due_amount'] * $data['exchange_rate'];
        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;

        $this->changeInvoiceStatus($data['due_amount']);

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

        self::createItems($this, $request->items);

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($this, $request->taxes);
        }

        if ($request->customFields) {
            $this->updateCustomFields($request->customFields);
        }

        $invoice = Invoice::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes'
        ])
            ->find($this->id);

        return $invoice;
    }

    public function sendInvoiceData($data)
    {
        $data['invoice'] = $this->toArray();
        $data['customer'] = $this->customer->toArray();
        $data['company'] = Company::find($this->company_id);
        $data['subject'] = $this->getEmailString($data['subject']);
        $data['body'] = $this->getEmailString($data['body']);
        $data['attach']['data'] = ($this->getEmailAttachmentSetting()) ? $this->getPDFData() : null;

        return $data;
    }

    public function preview($data)
    {
        $data = $this->sendInvoiceData($data);

        return [
            'type' => 'preview',
            'view' => new SendInvoiceMail($data)
        ];
    }

    public function send($data)
    {
        $data = $this->sendInvoiceData($data);

        \Mail::to($data['to'])->send(new SendInvoiceMail($data));

        if ($this->status == Invoice::STATUS_DRAFT) {
            $this->status = Invoice::STATUS_SENT;
            $this->sent = true;
            $this->save();
        }

        return [
            'success' => true,
            'type' => 'send',
        ];
    }

    public static function createItems($invoice, $invoiceItems)
    {
        $exchange_rate = $invoice->exchange_rate;

        foreach ($invoiceItems as $invoiceItem) {
            $invoiceItem['company_id'] = $invoice->company_id;
            $invoiceItem['exchange_rate'] = $exchange_rate;
            $invoiceItem['base_price'] = $invoiceItem['price'] * $exchange_rate;
            $invoiceItem['base_discount_val'] = $invoiceItem['discount_val'] * $exchange_rate;
            $invoiceItem['base_tax'] = $invoiceItem['tax'] * $exchange_rate;
            $invoiceItem['base_total'] = $invoiceItem['total'] * $exchange_rate;

            if (array_key_exists('recurring_invoice_id', $invoiceItem)) {
                unset($invoiceItem['recurring_invoice_id']);
            }

            $item = $invoice->items()->create($invoiceItem);

            if (array_key_exists('taxes', $invoiceItem) && $invoiceItem['taxes']) {
                foreach ($invoiceItem['taxes'] as $tax) {
                    $tax['company_id'] = $invoice->company_id;
                    $tax['exchange_rate'] = $invoice->exchange_rate;
                    $tax['base_amount'] = $tax['amount'] * $exchange_rate;
                    $tax['currency_id'] = $invoice->currency_id;

                    if (gettype($tax['amount']) !== "NULL") {
                        if (array_key_exists('recurring_invoice_id', $invoiceItem)) {
                            unset($invoiceItem['recurring_invoice_id']);
                        }

                        $item->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $invoiceItem) && $invoiceItem['custom_fields']) {
                $item->addCustomFields($invoiceItem['custom_fields']);
            }
        }
    }

    public static function createTaxes($invoice, $taxes)
    {
        $exchange_rate = $invoice->exchange_rate;

        foreach ($taxes as $tax) {
            $tax['company_id'] = $invoice->company_id;
            $tax['exchange_rate'] = $invoice->exchange_rate;
            $tax['base_amount'] = $tax['amount'] * $exchange_rate;
            $tax['currency_id'] = $invoice->currency_id;

            if (gettype($tax['amount']) !== "NULL") {
                if (array_key_exists('recurring_invoice_id', $tax)) {
                    unset($tax['recurring_invoice_id']);
                }

                $invoice->taxes()->create($tax);
            }
        }
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
        }

        $invoiceTemplate = self::find($this->id)->template_name;

        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);
        $customFields = CustomField::where('model_type', 'Item')->get();

        App::setLocale($locale);

        $logo = $company->logo_path;

        view()->share([
            'invoice' => $this,
            'customFields' => $customFields,
            'company_address' => $this->getCompanyAddress(),
            'shipping_address' => $this->getCustomerShippingAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'payment_information' => $this->getPaymentInformation(),
            'logo' => $logo ?? null,
            'taxes' => $taxes,
        ]);

        if (request()->has('preview')) {
            return view('app.pdf.invoice.'.$invoiceTemplate);
        }

        return PDF::loadView('app.pdf.invoice.'.$invoiceTemplate);
    }

    public function getEmailAttachmentSetting()
    {
        $invoiceAsAttachment = CompanySetting::getSetting('invoice_email_attachment', $this->company_id);

        if ($invoiceAsAttachment == 'NO') {
            return false;
        }

        return true;
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerShippingAddress()
    {
        if ($this->customer && (! $this->customer->shippingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_shipping_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_billing_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    public function getEmailString($body)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());

        $body = strtr($body, $values);

        return preg_replace('/{(.*?)}/', '', $body);
    }

    public function getExtraFields()
    {
        return [
            '{INVOICE_DATE}' => $this->formattedInvoiceDate,
            '{INVOICE_DUE_DATE}' => $this->formattedDueDate,
            '{INVOICE_NUMBER}' => $this->invoice_number,
            '{INVOICE_REF_NUMBER}' => $this->reference_number,
        ];
    }

    public static function invoiceTemplates()
    {
        $templates = Storage::disk('views')->files('/app/pdf/invoice');
        $invoiceTemplates = [];

        foreach ($templates as $key => $template) {
            $templateName = Str::before(basename($template), '.blade.php');
            $invoiceTemplates[$key]['name'] = $templateName;
            $invoiceTemplates[$key]['path'] = vite_asset('img/PDF/'.$templateName.'.png');
        }

        return $invoiceTemplates;
    }

    public function addInvoicePayment($amount)
    {
        $this->due_amount += $amount;
        $this->base_due_amount = $this->due_amount * $this->exchange_rate;

        $this->changeInvoiceStatus($this->due_amount);
    }

    public function subtractInvoicePayment($amount)
    {
        $this->due_amount -= $amount;
        $this->base_due_amount = $this->due_amount * $this->exchange_rate;

        $this->changeInvoiceStatus($this->due_amount);
    }

    public function changeInvoiceStatus($amount)
    {
        if ($amount < 0) {
            return [
                'error' => 'invalid_amount',
            ];
        }

        if ($amount == 0) {
            $this->status = Invoice::STATUS_COMPLETED;
            $this->paid_status = Invoice::STATUS_PAID;
            $this->overdue = false;
        } elseif ($amount == $this->total) {
            $this->status = $this->getPreviousStatus();
            $this->paid_status = Invoice::STATUS_UNPAID;
        } else {
            $this->status = $this->getPreviousStatus();
            $this->paid_status = Invoice::STATUS_PARTIALLY_PAID;
        }

        $this->save();
    }

    public static function deleteInvoices($ids)
    {
        foreach ($ids as $id) {
            $invoice = self::find($id);

            if ($invoice->transactions()->exists()) {
                $invoice->transactions()->delete();
            }

            $invoice->delete();
        }

        return true;
    }
}
