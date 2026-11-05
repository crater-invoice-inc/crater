<?php

namespace Crater\Http\Controllers\V1\Admin\Calculator;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests;
use Crater\Http\Requests\DeleteCalculatorsRequest;
use Crater\Http\Resources\CalculatorResource;
use Crater\Models\Calculator;
use Illuminate\Http\Request;

class CalculatorsController extends Controller
{
    /**
     * Retrieve a list of existing Calculators.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Calculator::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $calculators = Calculator::with(['customer', 'calculatorItems'])
            ->whereCompany()
            ->applyFilters($request->all())
            ->latest()
            ->paginateData($limit);

        return (CalculatorResource::collection($calculators))
            ->additional(['meta' => [
                'calculator_total_count' => Calculator::whereCompany()->count(),
            ]]);
    }

    /**
     * Create Calculator.
     *
     * @param  Crater\Http\Requests\CalculatorsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\CalculatorsRequest $request)
    {
        $this->authorize('create', Calculator::class);

        $calculator = Calculator::createCalculator($request);

        return new CalculatorResource($calculator);
    }

    /**
     * Get an existing Calculator.
     *
     * @param  Calculator $calculator
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Calculator $calculator)
    {
        $this->authorize('view', $calculator);

        return new CalculatorResource($calculator->load(['customer', 'calculatorItems']));
    }

    /**
     * Update an existing Calculator.
     *
     * @param  Crater\Http\Requests\CalculatorsRequest $request
     * @param  \Crater\Models\Calculator $calculator
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\CalculatorsRequest $request, Calculator $calculator)
    {
        $this->authorize('update', $calculator);

        $calculator = $calculator->updateCalculator($request);

        return new CalculatorResource($calculator);
    }

    /**
     * Duplicate an existing Calculator.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Crater\Models\Calculator $calculator
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicate(Request $request, Calculator $calculator)
    {
        $this->authorize('create', Calculator::class);

        $copy = $calculator->replicate(['created_at', 'updated_at']);
        $copy->name = $calculator->name.' (copie)';
        $copy->company_id = $request->header('company');
        $copy->creator_id = \Auth::id();
        $copy->save();

        foreach ($calculator->calculatorItems as $item) {
            $newItem = $item->replicate(['created_at', 'updated_at', 'calculator_id']);
            $newItem->calculator_id = $copy->id;
            $newItem->company_id = $request->header('company');
            $newItem->save();
        }

        return new CalculatorResource($copy->load(['customer', 'calculatorItems']));
    }

    /**
     * Delete a list of existing Calculators.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteCalculatorsRequest $request)
    {
        $this->authorize('deleteMultiple', Calculator::class);

        Calculator::whereCompany()->whereIn('calculators.id', $request->ids)->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
