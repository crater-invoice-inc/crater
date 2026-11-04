<?php

namespace Crater\Providers;

use Crater\Policies\CompanyPolicy;
use Crater\Policies\CustomerPolicy;
use Crater\Policies\DashboardPolicy;
use Crater\Policies\EstimatePolicy;
use Crater\Policies\ExpensePolicy;
use Crater\Policies\InvoicePolicy;
use Crater\Policies\ItemPolicy;
use Crater\Policies\ModulesPolicy;
use Crater\Policies\NotePolicy;
use Crater\Policies\OwnerPolicy;
use Crater\Policies\PaymentPolicy;
use Crater\Policies\RecurringInvoicePolicy;
use Crater\Policies\ReportPolicy;
use Crater\Policies\SettingsPolicy;
use Crater\Policies\UserPolicy;
use Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \Crater\Models\Customer::class => \Crater\Policies\CustomerPolicy::class,
        \Crater\Models\Invoice::class => \Crater\Policies\InvoicePolicy::class,
        \Crater\Models\Estimate::class => \Crater\Policies\EstimatePolicy::class,
        \Crater\Models\Payment::class => \Crater\Policies\PaymentPolicy::class,
        \Crater\Models\Expense::class => \Crater\Policies\ExpensePolicy::class,
        \Crater\Models\ExpenseCategory::class => \Crater\Policies\ExpenseCategoryPolicy::class,
        \Crater\Models\PaymentMethod::class => \Crater\Policies\PaymentMethodPolicy::class,
        \Crater\Models\TaxType::class => \Crater\Policies\TaxTypePolicy::class,
        \Crater\Models\CustomField::class => \Crater\Policies\CustomFieldPolicy::class,
        \Crater\Models\User::class => \Crater\Policies\UserPolicy::class,
        \Crater\Models\Item::class => \Crater\Policies\ItemPolicy::class,
        \Silber\Bouncer\Database\Role::class => \Crater\Policies\RolePolicy::class,
        \Crater\Models\Unit::class => \Crater\Policies\UnitPolicy::class,
        \Crater\Models\RecurringInvoice::class => \Crater\Policies\RecurringInvoicePolicy::class,
        \Crater\Models\ExchangeRateProvider::class => \Crater\Policies\ExchangeRateProviderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('create company', [CompanyPolicy::class, 'create']);
        Gate::define('transfer company ownership', [CompanyPolicy::class, 'transferOwnership']);
        Gate::define('delete company', [CompanyPolicy::class, 'delete']);

        Gate::define('manage modules', [ModulesPolicy::class, 'manageModules']);

        Gate::define('manage settings', [SettingsPolicy::class, 'manageSettings']);
        Gate::define('manage company', [SettingsPolicy::class, 'manageCompany']);
        Gate::define('manage backups', [SettingsPolicy::class, 'manageBackups']);
        Gate::define('manage file disk', [SettingsPolicy::class, 'manageFileDisk']);
        Gate::define('manage email config', [SettingsPolicy::class, 'manageEmailConfig']);
        Gate::define('manage notes', [NotePolicy::class, 'manageNotes']);
        Gate::define('view notes', [NotePolicy::class, 'viewNotes']);

        Gate::define('send invoice', [InvoicePolicy::class, 'send']);
        Gate::define('send estimate', [EstimatePolicy::class, 'send']);
        Gate::define('send payment', [PaymentPolicy::class, 'send']);

        Gate::define('delete multiple items', [ItemPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple customers', [CustomerPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple users', [UserPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple invoices', [InvoicePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple estimates', [EstimatePolicy::class, 'deleteMultiple']);
        Gate::define('update multiple estimates', [EstimatePolicy::class, 'updateMultiple']);
        Gate::define('send multiple invoices', [InvoicePolicy::class, 'sendMultiple']);
        Gate::define('delete multiple expenses', [ExpensePolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple payments', [PaymentPolicy::class, 'deleteMultiple']);
        Gate::define('delete multiple recurring invoices', [RecurringInvoicePolicy::class, 'deleteMultiple']);

        Gate::define('view dashboard', [DashboardPolicy::class, 'view']);

        Gate::define('view report', [ReportPolicy::class, 'viewReport']);

        Gate::define('owner only', [OwnerPolicy::class, 'managedByOwner']);
    }
}
