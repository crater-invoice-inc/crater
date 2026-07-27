<?php

namespace Crater\Http\Controllers\V1\Admin\Expense;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\ExpenseResource;
use Crater\Models\Expense;
use Illuminate\Http\Request;

class MarkExpenseAsPaidController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Crater\Models\Expense  $expense
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $expense->update(['paid_at' => Carbon::now()]);

        return new ExpenseResource($expense);
    }
}
