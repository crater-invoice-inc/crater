<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\SendInvoiceRequest;
use Crater\Models\CompanySetting;
use Crater\Models\Invoice;
use Illuminate\Mail\Markdown;

class SendInvoicePreviewController extends Controller
{
    /**
     * Mail a specific invoice to the corresponding customer's email address.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(SendInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('send invoice', $invoice);

        $markdown = new Markdown(view(), config('mail.markdown'));

        $data = $invoice->sendInvoiceData($request->all());
        $data['url'] = $invoice->invoicePdfUrl;
        $locale = CompanySetting::getSetting('language', $invoice->company_id);
        $data['view_label'] = __('invoices.view_invoice', [], $locale ?: 'en');

        return $markdown->render('emails.send.invoice', ['data' => $data]);
    }
}
