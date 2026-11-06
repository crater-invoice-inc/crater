<?php

namespace Crater\Http\Controllers\V1\Admin\Calculator;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests;
use Crater\Http\Requests\DeleteCalculatorTemplatesRequest;
use Crater\Http\Resources\CalculatorTemplateResource;
use Crater\Models\Calculator;
use Crater\Models\CalculatorTemplate;
use Illuminate\Http\Request;

class CalculatorTemplatesController extends Controller
{
    /**
     * Retrieve a list of existing Calculator Templates.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Calculator::class);

        $templates = CalculatorTemplate::whereCompany()
            ->applyFilters($request->all())
            ->latest()
            ->get();

        return (CalculatorTemplateResource::collection($templates))
            ->additional(['meta' => [
                'calculator_template_total_count' => CalculatorTemplate::whereCompany()->count(),
            ]]);
    }

    /**
     * Create Calculator Template.
     *
     * @param  Crater\Http\Requests\CalculatorTemplatesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\CalculatorTemplatesRequest $request)
    {
        $this->authorize('create', Calculator::class);

        $template = CalculatorTemplate::createTemplate($request);

        return new CalculatorTemplateResource($template);
    }

    /**
     * Update an existing Calculator Template.
     *
     * @param  Crater\Http\Requests\CalculatorTemplatesRequest $request
     * @param  \Crater\Models\CalculatorTemplate $calculatorTemplate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\CalculatorTemplatesRequest $request, CalculatorTemplate $calculatorTemplate)
    {
        $this->authorize('update', Calculator::class);

        $template = $calculatorTemplate->updateTemplate($request);

        return new CalculatorTemplateResource($template);
    }

    /**
     * Delete a list of existing Calculator Templates.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteCalculatorTemplatesRequest $request)
    {
        $this->authorize('deleteMultiple', Calculator::class);

        CalculatorTemplate::whereCompany()->whereIn('calculator_templates.id', $request->ids)->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
