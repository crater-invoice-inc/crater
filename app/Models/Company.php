<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use InteractsWithMedia;

    use HasFactory;

    protected $guarded = [
        'id'
    ];

    public const COMPANY_LEVEL = 'company_level';
    public const CUSTOMER_LEVEL = 'customer_level';

    protected $appends = ['logo', 'logo_path'];

    public function getRolesAttribute()
    {
        return Role::where('scope', $this->id)
            ->get();
    }

    public function getLogoPathAttribute()
    {
        $logo = $this->getMedia('logo')->first();

        $isSystem = FileDisk::whereSetAsDefault(true)->first()->isSystem();

        if ($logo) {
            if ($isSystem) {
                return $logo->getPath();
            } else {
                return $logo->getFullUrl();
            }
        }

        return null;
    }

    public function getLogoAttribute()
    {
        $logo = $this->getMedia('logo')->first();

        if ($logo) {
            return $logo->getFullUrl();
        }

        return null;
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function settings()
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function recurringInvoices()
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class);
    }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function exchangeRateLogs()
    {
        return $this->hasMany(ExchangeRateLog::class);
    }

    public function exchangeRateProviders()
    {
        return $this->hasMany(ExchangeRateProvider::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function taxTypes()
    {
        return $this->hasMany(TaxType::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_company', 'company_id', 'user_id');
    }

    public function setupRoles()
    {
        BouncerFacade::scope()->to($this->id);

        $super_admin = BouncerFacade::role()->firstOrCreate([
            'name' => 'super admin',
            'title' => 'Super Admin',
            'scope' => $this->id
        ]);

        foreach (config('abilities.abilities') as $ability) {
            BouncerFacade::allow($super_admin)->to($ability['ability'], $ability['model']);
        }
    }

    public function setupDefaultPaymentMethods()
    {
        PaymentMethod::create(['name' => 'Cash', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Check', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Credit Card', 'company_id' => $this->id]);
        PaymentMethod::create(['name' => 'Bank Transfer', 'company_id' => $this->id]);
    }

    public function setupDefaultUnits()
    {
        Unit::create(['name' => 'box', 'company_id' => $this->id]);
        Unit::create(['name' => 'cm', 'company_id' => $this->id]);
        Unit::create(['name' => 'dz', 'company_id' => $this->id]);
        Unit::create(['name' => 'ft', 'company_id' => $this->id]);
        Unit::create(['name' => 'g', 'company_id' => $this->id]);
        Unit::create(['name' => 'in', 'company_id' => $this->id]);
        Unit::create(['name' => 'kg', 'company_id' => $this->id]);
        Unit::create(['name' => 'km', 'company_id' => $this->id]);
        Unit::create(['name' => 'lb', 'company_id' => $this->id]);
        Unit::create(['name' => 'mg', 'company_id' => $this->id]);
        Unit::create(['name' => 'pc', 'company_id' => $this->id]);
    }

    public function setupDefaultSettings()
    {
        $defaultInvoiceEmailBody = 'You have received a new invoice from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultEstimateEmailBody = 'You have received a new estimate from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultPaymentEmailBody = 'Thank you for the payment.</b></br> Please download your payment receipt using the button below:';
        $billingAddressFormat = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY}  {BILLING_STATE}</p><p>{BILLING_COUNTRY}  {BILLING_ZIP_CODE}</p><p>{BILLING_PHONE}</p>';
        $shippingAddressFormat = '<h3>{SHIPPING_ADDRESS_NAME}</h3><p>{SHIPPING_ADDRESS_STREET_1}</p><p>{SHIPPING_ADDRESS_STREET_2}</p><p>{SHIPPING_CITY}  {SHIPPING_STATE}</p><p>{SHIPPING_COUNTRY}  {SHIPPING_ZIP_CODE}</p><p>{SHIPPING_PHONE}</p>';
        $companyAddressFormat = '<h3><strong>{COMPANY_NAME}</strong></h3><p>{COMPANY_ADDRESS_STREET_1}</p><p>{COMPANY_ADDRESS_STREET_2}</p><p>{COMPANY_CITY} {COMPANY_STATE}</p><p>{COMPANY_COUNTRY}  {COMPANY_ZIP_CODE}</p><p>{COMPANY_PHONE}</p>';
        $paymentFromCustomerAddress = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY} {BILLING_STATE} {BILLING_ZIP_CODE}</p><p>{BILLING_COUNTRY}</p><p>{BILLING_PHONE}</p>';

        $settings = [
            'invoice_auto_generate' => 'YES',
            'payment_auto_generate' => 'YES',
            'estimate_auto_generate' => 'YES',
            'save_pdf_to_disk' => 'NO',
            'invoice_mail_body' => $defaultInvoiceEmailBody,
            'estimate_mail_body' => $defaultEstimateEmailBody,
            'payment_mail_body' => $defaultPaymentEmailBody,
            'invoice_company_address_format' => $companyAddressFormat,
            'invoice_shipping_address_format' => $shippingAddressFormat,
            'invoice_billing_address_format' => $billingAddressFormat,
            'invoice_default_payment_information' => '',
            'invoice_default_terms_and_conditions' => '',
            'estimate_company_address_format' => $companyAddressFormat,
            'estimate_shipping_address_format' => $shippingAddressFormat,
            'estimate_billing_address_format' => $billingAddressFormat,
            'payment_company_address_format' => $companyAddressFormat,
            'payment_from_customer_address_format' => $paymentFromCustomerAddress,
            'currency' => request()->currency ?? 13,
            'time_zone' => 'Asia/Kolkata',
            'language' => 'en',
            'fiscal_year' => '1-12',
            'carbon_date_format' => 'Y/m/d',
            'moment_date_format' => 'YYYY/MM/DD',
            'notification_email' => 'noreply@crater.in',
            'notify_invoice_viewed' => 'NO',
            'notify_estimate_viewed' => 'NO',
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'invoice_auto_generate' => 'YES',
            'invoice_email_attachment' => 'NO',
            'estimate_auto_generate' => 'YES',
            'estimate_email_attachment' => 'NO',
            'payment_auto_generate' => 'YES',
            'payment_email_attachment' => 'NO',
            'save_pdf_to_disk' => 'NO',
            'retrospective_edits' => 'allow',
            'invoice_number_format' => '{{SERIES:INV}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_number_format' => '{{SERIES:EST}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'payment_number_format' => '{{SERIES:PAY}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_set_expiry_date_automatically' => 'YES',
            'estimate_expiry_date_days' => 7,
            'invoice_set_due_date_automatically' => 'YES',
            'invoice_due_date_days' => 7,
            'bulk_exchange_rate_configured' => 'YES',
            'estimate_convert_action' => 'no_action',
            'automatically_expire_public_links' => 'YES',
            'link_expiry_days' => 7,
            'module_estimates' => 'YES',
            'module_invoices' => 'YES',
            'module_recurring_invoices' => 'YES',
            'module_payments' => 'YES',
            'module_expenses' => 'YES',
            'module_modules' => 'YES',
        ];

        CompanySetting::setSettings($settings, $this->id);
    }

    public function disabledModules()
    {
        return collect(config('crater.company_modules'))
            ->filter(function ($module) {
                return CompanySetting::getSetting('module_'.$module['key'], $this->id) === 'NO';
            })
            ->pluck('menu');
    }

    public function setupDefaultData()
    {
        $this->setupRoles();
        $this->setupDefaultPaymentMethods();
        $this->setupDefaultUnits();
        $this->setupDefaultSettings();

        return true;
    }

    public function deleteCompany($user)
    {
        if ($this->exchangeRateLogs()->exists()) {
            $this->exchangeRateLogs()->delete();
        }

        if ($this->exchangeRateProviders()->exists()) {
            $this->exchangeRateProviders()->delete();
        }

        if ($this->expenses()->exists()) {
            $this->expenses()->delete();
        }

        if ($this->expenseCategories()->exists()) {
            $this->expenseCategories()->delete();
        }

        if ($this->payments()->exists()) {
            $this->payments()->delete();
        }

        if ($this->paymentMethods()->exists()) {
            $this->paymentMethods()->delete();
        }

        if ($this->customFieldValues()->exists()) {
            $this->customFieldValues()->delete();
        }


        if ($this->customFields()->exists()) {
            $this->customFields()->delete();
        }

        if ($this->invoices()->exists()) {
            $this->invoices->map(function ($invoice) {
                $this->checkModelData($invoice);

                if ($invoice->transactions()->exists()) {
                    $invoice->transactions()->delete();
                }
            });

            $this->invoices()->delete();
        }

        if ($this->recurringInvoices()->exists()) {
            $this->recurringInvoices->map(function ($recurringInvoice) {
                $this->checkModelData($recurringInvoice);
            });

            $this->recurringInvoices()->delete();
        }

        if ($this->estimates()->exists()) {
            $this->estimates->map(function ($estimate) {
                $this->checkModelData($estimate);
            });

            $this->estimates()->delete();
        }

        if ($this->items()->exists()) {
            $this->items()->delete();
        }

        if ($this->taxTypes()->exists()) {
            $this->taxTypes()->delete();
        }

        if ($this->customers()->exists()) {
            $this->customers->map(function ($customer) {
                if ($customer->addresses()->exists()) {
                    $customer->addresses()->delete();
                }

                $customer->delete();
            });
        }

        $roles = Role::when($this->id, function ($query) {
            return $query->where('scope', $this->id);
        })->get();

        if ($roles) {
            $roles->map(function ($role) {
                $role->delete();
            });
        }

        if ($this->users()->exists()) {
            $user->companies()->detach($this->id);
        }

        $this->settings()->delete();

        $this->address()->delete();

        $this->delete();

        return true;
    }

    public function checkModelData($model)
    {
        $model->items->map(function ($item) {
            if ($item->taxes()->exists()) {
                $item->taxes()->delete();
            }

            $item->delete();
        });

        if ($model->taxes()->exists()) {
            $model->taxes()->delete();
        }
    }

    public function hasTransactions()
    {
        if (
            $this->customers()->exists() ||
            $this->items()->exists() ||
            $this->invoices()->exists() ||
            $this->estimates()->exists() ||
            $this->expenses()->exists() ||
            $this->payments()->exists() ||
            $this->recurringInvoices()->exists()
        ) {
            return true;
        }

        return false;
    }
}
