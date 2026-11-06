<table width="100%" class="items-table" cellspacing="0" border="0">
    <tr class="item-table-heading-row">
        <th width="2%" class="pr-20 text-right item-table-heading">#</th>
        <th width="40%" class="pl-0 text-left item-table-heading">@lang('pdf_items_label')</th>
        @foreach($customFields as $field)
            <th class="text-right item-table-heading">{{ $field->label }}</th>
        @endforeach
        <th class="pr-20 text-right item-table-heading">@lang('pdf_quantity_label')</th>
        <th class="pr-20 text-right item-table-heading">@lang('pdf_price_label')</th>
        @if($invoice->discount_per_item === 'YES')
        <th class="pl-10 text-right item-table-heading">@lang('pdf_discount_label')</th>
        @endif
        @if($invoice->tax_per_item === 'YES')
        <th class="pl-10 text-right item-table-heading">@lang('pdf_tax_label')</th>
        @endif
        <th class="text-right item-table-heading">@lang('pdf_amount_label')</th>
    </tr>
    @php
        $index = 1
    @endphp
    @foreach ($invoice->items as $item)
        <tr class="item-row">
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {{$index}}
            </td>
            <td
                class="pl-0 text-left item-cell"
                style="vertical-align: top;"
            >
                <span><strong>{{ $item->name }}</strong></span><br>
                @if ($item->description)
                    <span class="item-description">{!! nl2br(htmlspecialchars($item->description)) !!}</span>
                @endif
                @if ($item->deliverables)
                    <div style="margin-top: 4px;">
                        <span style="display: block; font-size: 9px; line-height: 12px; font-weight: bold; color: #595959;">@lang('pdf_deliverables_label')</span>
                        <span style="display: block; color: #595959; font-size: 9px; line-height: 12px; word-wrap: break-word;">{!! nl2br(htmlspecialchars($item->deliverables)) !!}</span>
                    </div>
                @endif
                @if ($item->delivery_delay)
                    <div style="margin-top: 4px;">
                        <span style="display: block; font-size: 9px; line-height: 12px; font-weight: bold; color: #595959;">@lang('pdf_delivery_delay_label')</span>
                        <span style="display: block; color: #595959; font-size: 9px; line-height: 12px; word-wrap: break-word;">{!! nl2br(htmlspecialchars($item->delivery_delay)) !!}</span>
                    </div>
                @endif
                @if ($item->terms)
                    <div style="margin-top: 4px;">
                        <span style="display: block; font-size: 9px; line-height: 12px; font-weight: bold; color: #595959;">@lang('pdf_terms_label')</span>
                        <span style="display: block; color: #595959; font-size: 9px; line-height: 12px; word-wrap: break-word;">{!! nl2br(htmlspecialchars($item->terms)) !!}</span>
                    </div>
                @endif
            </td>
            @foreach($customFields as $field)
                <td class="text-right item-cell" style="vertical-align: top;">
                    {{ $item->getCustomFieldValueBySlug($field->slug) }}
                </td>
            @endforeach
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {{$item->quantity}} @if($item->unit_name) {{$item->unit_name}} @endif
            </td>
            <td
                class="pr-20 text-right item-cell"
                style="vertical-align: top;"
            >
                {!! format_money_pdf($item->price, $invoice->customer->currency) !!}
            </td>

            @if($invoice->discount_per_item === 'YES')
                <td
                    class="pl-10 text-right item-cell"
                    style="vertical-align: top;"
                >
                    @if($item->discount_type === 'fixed')
                            {!! format_money_pdf($item->discount_val, $invoice->customer->currency) !!}
                        @endif
                        @if($item->discount_type === 'percentage')
                            {{$item->discount}}%
                        @endif
                </td>
            @endif

            @if($invoice->tax_per_item === 'YES')
                <td
                    class="pl-10 text-right item-cell"
                    style="vertical-align: top;"
                >
                    {!! format_money_pdf($item->tax, $invoice->customer->currency) !!}
                </td>
            @endif

            <td
                class="text-right item-cell"
                style="vertical-align: top;"
            >
                {!! format_money_pdf($item->total, $invoice->customer->currency) !!}
            </td>
        </tr>
        @php
            $index += 1
        @endphp
    @endforeach
</table>

<hr class="item-cell-table-hr">

<div class="total-display-container">
    <table width="100%" cellspacing="0px" border="0" class="total-display-table @if(count($invoice->items) > 12) page-break @endif">
        <tr>
            <td class="border-0 total-table-attribute-label">@lang('pdf_subtotal')</td>
            <td class="py-2 border-0 item-cell total-table-attribute-value">
                {!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}
            </td>
        </tr>

        @if($invoice->discount > 0)
            @if ($invoice->discount_per_item === 'NO')
                <tr>
                    <td class="border-0 total-table-attribute-label">
                        @if($invoice->discount_type === 'fixed')
                            @lang('pdf_discount_label')
                        @endif
                        @if($invoice->discount_type === 'percentage')
                            @lang('pdf_discount_label') ({{$invoice->discount}}%)
                        @endif
                    </td>
                    <td class="py-2 border-0 item-cell total-table-attribute-value" >
                        @if($invoice->discount_type === 'fixed')
                            {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}
                        @endif
                        @if($invoice->discount_type === 'percentage')
                            {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}
                        @endif
                    </td>
                </tr>
            @endif
        @endif

        @if ($invoice->tax_per_item === 'YES')
            @foreach ($taxes as $tax)
                <tr>
                    <td class="border-0 total-table-attribute-label">
                        {{$tax->name.' ('.$tax->percent.'%)'}}
                    </td>
                    <td class="py-2 border-0 item-cell total-table-attribute-value">
                        {!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}
                    </td>
                </tr>
            @endforeach
        @else
            @foreach ($invoice->taxes as $tax)
                <tr>
                    <td class="border-0 total-table-attribute-label">
                        {{$tax->name.' ('.$tax->percent.'%)'}}
                    </td>
                    <td class="py-2 border-0 item-cell total-table-attribute-value">
                        {!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}
                    </td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td class="py-3"></td>
            <td class="py-3"></td>
        </tr>
        <tr>
            <td class="border-0 total-border-left total-table-attribute-label">
                @lang('pdf_total')
            </td>
            <td
                class="py-8 border-0 total-border-right item-cell total-table-attribute-value"
                style="color: #5851D8"
            >
                {!! format_money_pdf($invoice->total, $invoice->customer->currency)!!}
            </td>
        </tr>
    </table>
</div>
