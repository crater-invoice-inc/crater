<?php

namespace Crater\Models;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Crater\Jobs\GeneratePaymentPdfJob;
use Crater\Mail\SendPaymentMail;
use Crater\Services\SerialNumberFormatter;
use Crater\Traits\GeneratesPdfTrait;
use Crater\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Vinkla\Hashids\Facades\Hashids;

class Payment extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;

    public const PAYMENT_MODE_CHECK = 'CHECK';
    public const PAYMENT_MODE_OTHER = 'OTHER';
    public const PAYMENT_MODE_CASH = 'CASH';
    public const PAYMENT_MODE_CREDIT_CARD = 'CREDIT_CARD';
    public const PAYMENT_MODE_BANK_TRANSFER = 'BANK_TRANSFER';

    protected $dates = ['created_at', 'updated_at', 'payment_date'];

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'formattedPaymentDate',
        'paymentPdfUrl',
    ];

    protected $casts = [
        'notes' => 'string',
        'exchange_rate' => 'float'
    ];

    protected static function booted()
    {
        static::created(function ($payment) {
            GeneratePaymentPdfJob::dispatch($payment);
        });

        static::updated(function ($payment) {
            GeneratePaymentPdfJob::dispatch($payment, true);
        });
    }

    public function setSettingsAttribute($value)
    {
        if ($value) {
            $this->attributes['settings'] = json_encode($value);
        }
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getFormattedPaymentDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->payment_date)->format($dateFormat);
    }

    public function getPaymentPdfUrlAttribute()
    {
        return url('/payments/pdf/'.$this->unique_hash);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function emailLogs()
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo('Crater\Models\User', 'creator_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function sendPaymentData($data)
    {
        $data['payment'] = $this->toArray();
        $data['user'] = $this->customer->toArray();
        $data['company'] = Company::find($this->company_id);
        $data['body'] = $this->getEmailBody($data['body']);
        $data['attach']['data'] = ($this->getEmailAttachmentSetting()) ? $this->getPDFData() : null;

        return $data;
    }

    public function send($data)
    {
        $data = $this->sendPaymentData($data);

        \Mail::to($data['to'])->send(new SendPaymentMail($data));

        return [
            'success' => true,
        ];
    }

    public static function createPayment($request)
    {
        $data = $request->getPaymentPayload();

        if ($request->invoice_id) {
            $invoice = Invoice::find($request->invoice_id);
            $invoice->subtractInvoicePayment($request->amount);
        }

        $payment = Payment::create($data);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);

        $serial = (new SerialNumberFormatter())
            ->setModel($payment)
            ->setCompany($payment->company_id)
            ->setCustomer($payment->customer_id)
            ->setNextNumbers();

        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string)$payment['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($payment);
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $payment->addCustomFields($customFields);
        }

        $payment = Payment::with([
            'customer',
            'invoice',
            'paymentMethod',
            'fields'
        ])->find($payment->id);

        return $payment;
    }

    public function updatePayment($request)
    {
        $data = $request->getPaymentPayload();

        if ($request->invoice_id && (! $this->invoice_id || $this->invoice_id !== $request->invoice_id)) {
            $invoice = Invoice::find($request->invoice_id);
            $invoice->subtractInvoicePayment($request->amount);
        }

        if ($this->invoice_id && (! $request->invoice_id || $this->invoice_id !== $request->invoice_id)) {
            $invoice = Invoice::find($this->invoice_id);
            $invoice->addInvoicePayment($this->amount);
        }

        if ($this->invoice_id && $this->invoice_id === $request->invoice_id && $request->amount !== $this->amount) {
            $invoice = Invoice::find($this->invoice_id);
            $invoice->addInvoicePayment($this->amount);
            $invoice->subtractInvoicePayment($request->amount);
        }

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

        $customFields = $request->customFields;

        if ($customFields) {
            $this->updateCustomFields($customFields);
        }

        $payment = Payment::with([
            'customer',
            'invoice',
            'paymentMethod',
        ])
            ->find($this->id);

        return $payment;
    }

    public static function deletePayments($ids)
    {
        foreach ($ids as $id) {
            $payment = Payment::find($id);

            if ($payment->invoice_id != null) {
                $invoice = Invoice::find($payment->invoice_id);
                $invoice->due_amount = ((int)$invoice->due_amount + (int)$payment->amount);

                if ($invoice->due_amount == $invoice->total) {
                    $invoice->paid_status = Invoice::STATUS_UNPAID;
                } else {
                    $invoice->paid_status = Invoice::STATUS_PARTIALLY_PAID;
                }

                $invoice->status = $invoice->getPreviousStatus();
                $invoice->save();
            }

            $payment->delete();
        }

        return true;
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

    public function scopePaymentNumber($query, $paymentNumber)
    {
        return $query->where('payments.payment_number', 'LIKE', '%'.$paymentNumber.'%');
    }

    public function scopePaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payments.payment_method_id', $paymentMethodId);
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('payment_number')) {
            $query->paymentNumber($filters->get('payment_number'));
        }

        if ($filters->get('payment_id')) {
            $query->wherePayment($filters->get('payment_id'));
        }

        if ($filters->get('payment_method_id')) {
            $query->paymentMethod($filters->get('payment_method_id'));
        }

        if ($filters->get('customer_id')) {
            $query->whereCustomer($filters->get('customer_id'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->paymentsBetween($start, $end);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'sequence_number';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'desc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaymentsBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'payments.payment_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWherePayment($query, $payment_id)
    {
        $query->orWhere('id', $payment_id);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('payments.company_id', request()->header('company'));
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->where('payments.customer_id', $customer_id);
    }

    public function getPDFData()
    {
        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);

        \App::setLocale($locale);

        $logo = $company->logo_path;

        view()->share([
            'payment' => $this,
            'company_address' => $this->getCompanyAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'logo' => $logo ?? null,
        ]);

        if (request()->has('preview')) {
            return view('app.pdf.payment.payment');
        }

        return PDF::loadView('app.pdf.payment.payment');
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('payment_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('payment_from_customer_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getEmailAttachmentSetting()
    {
        $paymentAsAttachment = CompanySetting::getSetting('payment_email_attachment', $this->company_id);

        if ($paymentAsAttachment == 'NO') {
            return false;
        }

        return true;
    }

    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
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
            '{PAYMENT_DATE}' => $this->formattedPaymentDate,
            '{PAYMENT_MODE}' => $this->paymentMethod ? $this->paymentMethod->name : null,
            '{PAYMENT_NUMBER}' => $this->payment_number,
            '{PAYMENT_AMOUNT}' => $this->reference_number,
        ];
    }

    public static function generatePayment($transaction)
    {
        $invoice = Invoice::find($transaction->invoice_id);

        $serial = (new SerialNumberFormatter())
            ->setModel(new Payment())
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $data['payment_number'] = $serial->getNextNumber();
        $data['payment_date'] = Carbon::now()->format('y-m-d');
        $data['amount'] = $invoice->total;
        $data['invoice_id'] = $invoice->id;
        $data['payment_method_id'] = request()->payment_method_id;
        $data['customer_id'] = $invoice->customer_id;
        $data['exchange_rate'] = $invoice->exchange_rate;
        $data['base_amount'] = $data['amount'] * $data['exchange_rate'];
        $data['currency_id'] = $invoice->currency_id;
        $data['company_id'] = $invoice->company_id;
        $data['transaction_id'] = $transaction->id;

        $payment = Payment::create($data);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        $invoice->subtractInvoicePayment($invoice->total);

        return $payment;
    }

    public static function createFromInvoice(Invoice $invoice, $paymentDate = null, $paymentMethodId = null, $userId = null)
    {
        $serial = (new SerialNumberFormatter())
            ->setModel(new Payment())
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $data['payment_number'] = $serial->getNextNumber();
        $data['payment_date'] = $paymentDate ?: Carbon::now()->format('Y-m-d');
        $data['amount'] = $invoice->due_amount;
        $data['base_amount'] = $invoice->due_amount * $invoice->exchange_rate;
        $data['invoice_id'] = $invoice->id;
        $data['payment_method_id'] = $paymentMethodId;
        $data['customer_id'] = $invoice->customer_id;
        $data['currency_id'] = $invoice->currency_id;
        $data['exchange_rate'] = $invoice->exchange_rate;
        $data['creator_id'] = $userId;
        $data['company_id'] = $invoice->company_id;
        $data['notes'] = 'Payment for invoice '.$invoice->invoice_number;

        $payment = Payment::create($data);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        $company_currency = CompanySetting::getSetting('currency', $invoice->company_id);

        if ((string)$payment['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($payment);
        }

        $invoice->subtractInvoicePayment($invoice->due_amount);

        return $payment;
    }
}
