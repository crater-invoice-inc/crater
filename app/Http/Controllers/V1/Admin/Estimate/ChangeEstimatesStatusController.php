<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\UpdateEstimatesStatusRequest;
use Crater\Models\Estimate;
use Illuminate\Http\Request;

class ChangeEstimatesStatusController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Crater\Http\Requests\UpdateEstimatesStatusRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(UpdateEstimatesStatusRequest $request)
    {
        $this->authorize('update multiple estimates');

        Estimate::whereIn('id', $request->ids)->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
        ]);
    }
}
