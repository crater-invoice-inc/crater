<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\UpdateInvoicesStatusRequest;
use Crater\Models\Invoice;
use Illuminate\Http\Request;

class ChangeInvoicesStatusController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Crater\Http\Requests\UpdateInvoicesStatusRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(UpdateInvoicesStatusRequest $request)
    {
        $this->authorize('send multiple invoices');

        Invoice::whereIn('id', $request->ids)
            ->where('paid_status', '!=', Invoice::STATUS_PAID)
            ->update([
                'status' => $request->status,
                'sent' => $request->status === Invoice::STATUS_SENT,
            ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
