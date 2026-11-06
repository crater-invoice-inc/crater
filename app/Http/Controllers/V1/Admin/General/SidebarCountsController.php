<?php

namespace Crater\Http\Controllers\V1\Admin\General;

use Crater\Http\Controllers\Controller;
use Crater\Models\Customer;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SidebarCountsController extends Controller
{
    /**
     * Lightweight counts used by the admin sidebar badges.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'total_customer_count' => Customer::whereCompany()->count(),
            'total_invoice_count' => Invoice::whereCompany()->count(),
            'total_estimate_count' => Estimate::whereCompany()->count(),
        ]);
    }
}
