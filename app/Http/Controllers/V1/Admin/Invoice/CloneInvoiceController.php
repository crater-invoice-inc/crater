<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\InvoiceResource;
use Crater\Models\CompanySetting;
use Crater\Models\Invoice;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class CloneInvoiceController extends Controller
{
    /**
     * Mail a specific invoice to the corresponding customer's email address.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        $this->authorize('create', Invoice::class);

        $date = Carbon::now();

        $serial = (new SerialNumberFormatter())
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $due_date = null;
        $dueDateEnabled = CompanySetting::getSetting(
            'invoice_set_due_date_automatically',
            $request->header('company')
        );

        if ($dueDateEnabled === 'YES') {
            $dueDateDays = CompanySetting::getSetting(
                'invoice_due_date_days',
                $request->header('company')
            );
            $due_date = Carbon::now()->addDays($dueDateDays)->format('Y-m-d');
        }

        $exchange_rate = $invoice->exchange_rate;

        $newInvoice = Invoice::create([
            'invoice_date' => $date->format('Y-m-d'),
            'due_date' => $due_date,
            'invoice_number' => $serial->getNextNumber(),
            'sequence_number' => $serial->nextSequenceNumber,
            'customer_sequence_number' => $serial->nextCustomerSequenceNumber,
            'reference_number' => $invoice->reference_number,
            'customer_id' => $invoice->customer_id,
            'company_id' => $request->header('company'),
            'template_name' => $invoice->template_name,
            'status' => Invoice::STATUS_DRAFT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'sub_total' => $invoice->sub_total,
            'discount' => $invoice->discount,
            'discount_type' => $invoice->discount_type,
            'discount_val' => $invoice->discount_val,
            'total' => $invoice->total,
            'due_amount' => $invoice->total,
            'tax_per_item' => $invoice->tax_per_item,
            'discount_per_item' => $invoice->discount_per_item,
            'tax' => $invoice->tax,
            'notes' => $invoice->notes,
            'payment_information' => $invoice->payment_information,
            'exchange_rate' => $exchange_rate,
            'base_total' => $invoice->total * $exchange_rate,
            'base_discount_val' => $invoice->discount_val * $exchange_rate,
            'base_sub_total' => $invoice->sub_total * $exchange_rate,
            'base_tax' => $invoice->tax * $exchange_rate,
            'base_due_amount' => $invoice->total * $exchange_rate,
            'currency_id' => $invoice->currency_id,
            'sales_tax_type' => $invoice->sales_tax_type,
            'sales_tax_address_type' => $invoice->sales_tax_address_type,
        ]);

        $newInvoice->unique_hash = Hashids::connection(Invoice::class)->encode($newInvoice->id);
        $newInvoice->save();
        $invoice->load('items.taxes');

        $invoiceItems = $invoice->items->toArray();

        foreach ($invoiceItems as $invoiceItem) {
            $invoiceItem['company_id'] = $request->header('company');
            $invoiceItem['name'] = $invoiceItem['name'];
            $invoiceItem['exchange_rate'] = $exchange_rate;
            $invoiceItem['base_price'] = $invoiceItem['price'] * $exchange_rate;
            $invoiceItem['base_discount_val'] = $invoiceItem['discount_val'] * $exchange_rate;
            $invoiceItem['base_tax'] = $invoiceItem['tax'] * $exchange_rate;
            $invoiceItem['base_total'] = $invoiceItem['total'] * $exchange_rate;

            $item = $newInvoice->items()->create($invoiceItem);

            if (array_key_exists('taxes', $invoiceItem) && $invoiceItem['taxes']) {
                foreach ($invoiceItem['taxes'] as $tax) {
                    $tax['company_id'] = $request->header('company');

                    if ($tax['amount']) {
                        $item->taxes()->create($tax);
                    }
                }
            }
        }

        if ($invoice->taxes) {
            foreach ($invoice->taxes->toArray() as $tax) {
                $tax['company_id'] = $request->header('company');
                $newInvoice->taxes()->create($tax);
            }
        }

        if ($invoice->fields()->exists()) {
            $customFields = [];

            foreach ($invoice->fields as $data) {
                $customFields[] = [
                    'id' => $data->custom_field_id,
                    'value' => $data->defaultAnswer
                ];
            }

            $newInvoice->addCustomFields($customFields);
        }

        return new InvoiceResource($newInvoice);
    }
}
