<?php

namespace Crater\Http\Controllers\V1\Admin\Payment;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\BulkStorePaymentsRequest;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Illuminate\Http\Request;

class BulkStorePaymentsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Crater\Http\Requests\BulkStorePaymentsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(BulkStorePaymentsRequest $request)
    {
        $this->authorize('create', Payment::class);

        $invoices = Invoice::whereCompany()
            ->whereIn('id', $request->ids)
            ->where('due_amount', '>', 0)
            ->get();

        foreach ($invoices as $invoice) {
            Payment::createFromInvoice(
                $invoice,
                $request->payment_date,
                $request->payment_method_id,
                $request->user()->id
            );
        }

        return response()->json([
            'success' => true,
            'count' => $invoices->count(),
        ]);
    }
}
