<?php

use Crater\Models\Calculator;
use Crater\Models\Customer;
use Crater\Models\CustomField;
use Crater\Models\Estimate;
use Crater\Models\ExchangeRateProvider;
use Crater\Models\Expense;
use Crater\Models\Invoice;
use Crater\Models\Item;
use Crater\Models\Note;
use Crater\Models\Payment;
use Crater\Models\RecurringInvoice;
use Crater\Models\TaxType;

return [
    'abilities' => [

        // Customer
        [
            "name" => "view customer",
            "ability" => "view-customer",
            "model" => Customer::class,
        ],
        [
            "name" => "create customer",
            "ability" => "create-customer",
            "model" => Customer::class,
            "depends_on" => [
                'view-customer',
                'view-custom-field',
            ]
        ],
        [
            "name" => "edit customer",
            "ability" => "edit-customer",
            "model" => Customer::class,
            "depends_on" => [
                'view-customer',
                'view-custom-field',
            ]
        ],
        [
            "name" => "delete customer",
            "ability" => "delete-customer",
            "model" => Customer::class,
            "depends_on" => [
                'view-customer',
            ]
        ],

        // Item
        [
            "name" => "view item",
            "ability" => "view-item",
            "model" => Item::class,
        ],
        [
            "name" => "create item",
            "ability" => "create-item",
            "model" => Item::class,
            "depends_on" => [
                'view-item',
                'view-tax-type'
            ]
        ],
        [
            "name" => "edit item",
            "ability" => "edit-item",
            "model" => Item::class,
            "depends_on" => [
                'view-item',
            ]
        ],
        [
            "name" => "delete item",
            "ability" => "delete-item",
            "model" => Item::class,
            "depends_on" => [
                'view-item',
            ]
        ],

        // Tax Type
        [
            "name" => "view tax type",
            "ability" => "view-tax-type",
            "model" => TaxType::class,
        ],
        [
            "name" => "create tax type",
            "ability" => "create-tax-type",
            "model" => TaxType::class,
            "depends_on" => [
                'view-tax-type',
            ]
        ],
        [
            "name" => "edit tax type",
            "ability" => "edit-tax-type",
            "model" => TaxType::class,
            "depends_on" => [
                'view-tax-type',
            ]
        ],
        [
            "name" => "delete tax type",
            "ability" => "delete-tax-type",
            "model" => TaxType::class,
            "depends_on" => [
                'view-tax-type',
            ]
        ],

        // Estimate
        [
            "name" => "view estimate",
            "ability" => "view-estimate",
            "model" => Estimate::class,
        ],
        [
            "name" => "create estimate",
            "ability" => "create-estimate",
            "model" => Estimate::class,
            "depends_on" => [
                'view-estimate',
                'view-item',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "edit estimate",
            "ability" => "edit-estimate",
            "model" => Estimate::class,
            "depends_on" => [
                'view-item',
                'view-estimate',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "delete estimate",
            "ability" => "delete-estimate",
            "model" => Estimate::class,
            "depends_on" => [
                'view-estimate',
            ]
        ],
        [
            "name" => "send estimate",
            "ability" => "send-estimate",
            "model" => Estimate::class,
        ],

        // Invoice
        [
            "name" => "view invoice",
            "ability" => "view-invoice",
            "model" => Invoice::class,
        ],
        [
            "name" => "create invoice",
            "ability" => "create-invoice",
            "model" => Invoice::class,
            'owner_only' => false,
            "depends_on" => [
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "edit invoice",
            "ability" => "edit-invoice",
            "model" => Invoice::class,
            "depends_on" => [
                'view-item',
                'view-invoice',
                'view-tax-type',
                'view-customer',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "delete invoice",
            "ability" => "delete-invoice",
            "model" => Invoice::class,
            "depends_on" => [
                'view-invoice'
            ]
        ],
        [
            "name" => "send invoice",
            "ability" => "send-invoice",
            "model" => Invoice::class,
        ],

        // Recurring Invoice
        [
            "name" => "view recurring invoice",
            "ability" => "view-recurring-invoice",
            "model" => RecurringInvoice::class,
        ],
        [
            "name" => "create recurring invoice",
            "ability" => "create-recurring-invoice",
            "model" => RecurringInvoice::class,
            "depends_on" => [
                'view-item',
                'view-recurring-invoice',
                'view-tax-type',
                'view-customer',
                'view-all-notes',
                'send-invoice'
            ]
        ],
        [
            "name" => "edit recurring invoice",
            "ability" => "edit-recurring-invoice",
            "model" => RecurringInvoice::class,
            "depends_on" => [
                'view-item',
                'view-recurring-invoice',
                'view-tax-type',
                'view-customer',
                'view-all-notes',
                'send-invoice'
            ]
        ],
        [
            "name" => "delete recurring invoice",
            "ability" => "delete-recurring-invoice",
            "model" => RecurringInvoice::class,
            "depends_on" => [
                'view-recurring-invoice',
            ]
        ],

        // Payment
        [
            "name" => "view payment",
            "ability" => "view-payment",
            "model" => Payment::class,
        ],
        [
            "name" => "create payment",
            "ability" => "create-payment",
            "model" => Payment::class,
            "depends_on" => [
                'view-customer',
                'view-payment',
                'view-invoice',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "edit payment",
            "ability" => "edit-payment",
            "model" => Payment::class,
            "depends_on" => [
                'view-customer',
                'view-payment',
                'view-invoice',
                'view-custom-field',
                'view-all-notes'
            ]
        ],
        [
            "name" => "delete payment",
            "ability" => "delete-payment",
            "model" => Payment::class,
            "depends_on" => [
                'view-payment',
            ]
        ],
        [
            "name" => "send payment",
            "ability" => "send-payment",
            "model" => Payment::class,
        ],

        // Expense
        [
            "name" => "view expense",
            "ability" => "view-expense",
            "model" => Expense::class,
        ],
        [
            "name" => "create expense",
            "ability" => "create-expense",
            "model" => Expense::class,
            "depends_on" => [
                'view-customer',
                'view-expense',
                'view-custom-field',
            ]
        ],
        [
            "name" => "edit expense",
            "ability" => "edit-expense",
            "model" => Expense::class,
            "depends_on" => [
                'view-customer',
                'view-expense',
                'view-custom-field',
            ]
        ],
        [
            "name" => "delete expense",
            "ability" => "delete-expense",
            "model" => Expense::class,
            "depends_on" => [
                'view-expense',
            ]
        ],

        // Custom Field
        [
            "name" => "view custom field",
            "ability" => "view-custom-field",
            "model" => CustomField::class,
        ],
        [
            "name" => "create custom field",
            "ability" => "create-custom-field",
            "model" => CustomField::class,
            "depends_on" => [
                'view-custom-field',
            ]
        ],
        [
            "name" => "edit custom field",
            "ability" => "edit-custom-field",
            "model" => CustomField::class,
            "depends_on" => [
                'view-custom-field',
            ]
        ],
        [
            "name" => "delete custom field",
            "ability" => "delete-custom-field",
            "model" => CustomField::class,
            "depends_on" => [
                'view-custom-field',
            ]
        ],

        // Financial Reports
        [
            "name" => "view financial reports",
            "ability" => "view-financial-reports",
            "model" => null,
        ],

        // Exchange Rate Provider
        [
            "name" => "view exchange rate provider",
            "ability" => "view-exchange-rate-provider",
            "model" => ExchangeRateProvider::class,
            'owner_only' => false,
        ],
        [
            "name" => "create exchange rate provider",
            "ability" => "create-exchange-rate-provider",
            "model" => ExchangeRateProvider::class,
            'owner_only' => false,
            "depends_on" => [
                'view-exchange-rate-provider',
            ]
        ],
        [
            "name" => "edit exchange rate provider",
            "ability" => "edit-exchange-rate-provider",
            "model" => ExchangeRateProvider::class,
            'owner_only' => false,
            "depends_on" => [
                'view-exchange-rate-provider',
            ]
        ],
        [
            "name" => "delete exchange rate provider",
            "ability" => "delete-exchange-rate-provider",
            "model" => ExchangeRateProvider::class,
            'owner_only' => false,
            "depends_on" => [
                'view-exchange-rate-provider',
            ]
        ],

        // Settings
        [
            "name" => "view company dashboard",
            "ability" => "dashboard",
            "model" => null,
        ],
        [
            "name" => "view all notes",
            "ability" => "view-all-notes",
            "model" => Note::class,
        ],
        [
            "name" => "manage notes",
            "ability" => "manage-all-notes",
            "model" => Note::class,
            "depends_on" => [
                'view-all-notes'
            ]
        ],

        // Calculator
        [
            "name" => "view calculator",
            "ability" => "view-calculator",
            "model" => Calculator::class,
        ],
        [
            "name" => "create calculator",
            "ability" => "create-calculator",
            "model" => Calculator::class,
            "depends_on" => [
                'view-calculator',
            ]
        ],
        [
            "name" => "edit calculator",
            "ability" => "edit-calculator",
            "model" => Calculator::class,
            "depends_on" => [
                'view-calculator',
            ]
        ],
        [
            "name" => "delete calculator",
            "ability" => "delete-calculator",
            "model" => Calculator::class,
            "depends_on" => [
                'view-calculator',
            ]
        ]
    ]
];
