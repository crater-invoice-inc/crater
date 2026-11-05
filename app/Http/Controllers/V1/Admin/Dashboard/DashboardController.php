<?php

namespace Crater\Http\Controllers\V1\Admin\Dashboard;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Customer;
use Crater\Models\Estimate;
use Crater\Models\Expense;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('view dashboard', $company);

        $invoice_totals = [];
        $expense_totals = [];
        $receipt_totals = [];
        $net_income_totals = [];

        $i = 0;
        $months = [];
        $monthCounter = 0;
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
        $startDate = $request->has('year')
            ? Carbon::createFromDate((int)$request->year, 1, 1)
            : Carbon::now();
        $start = clone $startDate;
        $end = clone $startDate;
        $terms = explode('-', $fiscalYear);

        if ($terms[0] <= $start->month) {
            $startDate->month($terms[0])->startOfMonth();
            $start->month($terms[0])->startOfMonth();
            $end->month($terms[0])->endOfMonth();
        } else {
            $startDate->subYear()->month($terms[0])->startOfMonth();
            $start->subYear()->month($terms[0])->startOfMonth();
            $end->subYear()->month($terms[0])->endOfMonth();
        }

        while ($monthCounter < 12) {
            array_push(
                $invoice_totals,
                Invoice::whereBetween(
                    'invoice_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                ->whereCompany()
                ->sum('base_total')
            );
            array_push(
                $expense_totals,
                Expense::whereBetween(
                    'expense_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                ->whereCompany()
                ->sum('base_amount')
            );
            array_push(
                $receipt_totals,
                Payment::whereBetween(
                    'payment_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                ->whereCompany()
                ->sum('base_amount')
            );
            array_push(
                $net_income_totals,
                ($receipt_totals[$i] - $expense_totals[$i])
            );
            $i++;
            array_push($months, $start->format('M'));
            $monthCounter++;
            $end->startOfMonth();
            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $start->subMonth()->endOfMonth();

        $periodStart = $startDate->format('Y-m-d');
        $periodEnd = $start->format('Y-m-d');

        $total_sales = Invoice::whereBetween(
            'invoice_date',
            [$periodStart, $periodEnd]
        )
            ->whereCompany()
            ->sum('base_total');

        $total_receipts = Payment::whereBetween(
            'payment_date',
            [$periodStart, $periodEnd]
        )
            ->whereCompany()
            ->sum('base_amount');

        $total_expenses = Expense::whereBetween(
            'expense_date',
            [$periodStart, $periodEnd]
        )
            ->whereCompany()
            ->sum('base_amount');

        $total_net_income = (int)$total_receipts - (int)$total_expenses;

        $previousStart = $startDate->copy()->subYear();
        $previousEnd = $start->copy()->subYear();

        $previous_year_sales = Invoice::whereBetween(
            'invoice_date',
            [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_total');

        $previous_year_receipts = Payment::whereBetween(
            'payment_date',
            [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_amount');

        $previous_year_expenses = Expense::whereBetween(
            'expense_date',
            [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_amount');

        $previous_year_net_income = (int)$previous_year_receipts - (int)$previous_year_expenses;

        $invoice_years = Invoice::whereCompany()
            ->selectRaw('DISTINCT YEAR(invoice_date) as year')
            ->pluck('year');
        $expense_years = Expense::whereCompany()
            ->selectRaw('DISTINCT YEAR(expense_date) as year')
            ->pluck('year');
        $payment_years = Payment::whereCompany()
            ->selectRaw('DISTINCT YEAR(payment_date) as year')
            ->pluck('year');

        $available_years = collect([$invoice_years, $expense_years, $payment_years])
            ->flatten()
            ->unique()
            ->sortDesc()
            ->values();

        $chart_data = [
            'months' => $months,
            'invoice_totals' => $invoice_totals,
            'expense_totals' => $expense_totals,
            'receipt_totals' => $receipt_totals,
            'net_income_totals' => $net_income_totals,
        ];

        $total_customer_count = Customer::whereCompany()->count();
        $total_invoice_count = Invoice::whereCompany()
            ->count();
        $total_estimate_count = Estimate::whereCompany()->count();
        $total_amount_due = Invoice::whereCompany()
            ->sum('base_due_amount');

        $recent_due_invoices = Invoice::with('customer')
            ->whereCompany()
            ->where('base_due_amount', '>', 0)
            ->take(5)
            ->latest()
            ->get();
        $recent_estimates = Estimate::with('customer')->whereCompany()->take(5)->latest()->get();

        return response()->json([
            'total_amount_due' => $total_amount_due,
            'total_customer_count' => $total_customer_count,
            'total_invoice_count' => $total_invoice_count,
            'total_estimate_count' => $total_estimate_count,

            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recent_due_invoices : [],
            'recent_estimates' => BouncerFacade::can('view-estimate', Estimate::class) ? $recent_estimates : [],

            'chart_data' => $chart_data,

            'total_sales' => $total_sales,
            'total_receipts' => $total_receipts,
            'total_expenses' => $total_expenses,
            'total_net_income' => $total_net_income,

            'available_years' => $available_years,

            'previous_year_sales' => $previous_year_sales,
            'previous_year_receipts' => $previous_year_receipts,
            'previous_year_expenses' => $previous_year_expenses,
            'previous_year_net_income' => $previous_year_net_income,
        ]);
    }
}
