<!DOCTYPE html>
<html>
<head>
    <title>Soumission - {{ $estimate->estimate_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style type="text/css">
        @font-face {
            font-family: 'Open Sauce';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/open-sauce-sans-latin-900-normal.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Courier Prime';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/courier-prime-latin-400-normal.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Courier Prime';
            font-style: normal;
            font-weight: bold;
            src: url("{{ storage_path('fonts/courier-prime-latin-700-normal.ttf') }}") format('truetype');
        }

        @page { margin: 60pt 42pt 54pt 42pt; }

        body {
            font-family: 'Courier Prime', 'DejaVu Sans Mono', monospace;
            font-size: 10.5pt;
            line-height: 14pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .heading {
            font-family: 'Open Sauce', 'DejaVu Sans', sans-serif;
            font-weight: normal;
            text-transform: uppercase;
            color: #7f5539;
        }

        .bar { position: fixed; left: -10pt; width: 521pt; background: #7f5539; }
        .bar-top { top: -60pt; height: 18pt; }
        .bar-bottom { top: 763pt; height: 19pt; }

        table { border-collapse: collapse; }
        .w-100 { width: 100%; }
        .right { text-align: right; }
        .right-cell { padding-right: 10pt; }
        .title { font-size: 22.7pt; line-height: 26pt; white-space: nowrap; }
        .small-title { font-size: 11pt; line-height: 15pt; margin: 0 0 4pt 0; }
        .address { font-size: 10.5pt; line-height: 14pt; }
        .client-line { font-size: 10.5pt; line-height: 14pt; margin: 0; padding: 0; }

        .wide { margin-left: -10pt; width: 521pt; }

        .items-head { margin-top: 24pt; background: #7f5539; }
        .items-head table { width: 521pt; }
        .items-head th {
            color: #ffffff;
            font-family: 'Open Sauce', 'DejaVu Sans', sans-serif;
            font-weight: normal;
            font-size: 9.5pt;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            padding-top: 9pt;
            padding-bottom: 9pt;
        }
        .items-head th.c-num,
        .items-head th.c-svc { text-align: left; }
        .items-head th.c-qty,
        .items-head th.c-price,
        .items-head th.c-amount { text-align: right; }

        /* Largeur du contenu + marges internes = 521pt :
           # 24+16 | Services 234+16 | Quantité 58+8 | Prix 72+8 | Montant 73+12 */
        .c-num { width: 24pt; padding-left: 16pt; padding-right: 0; }
        .c-svc { width: 234pt; padding-left: 8pt; padding-right: 8pt; }
        .c-qty { width: 58pt; padding-left: 4pt; padding-right: 4pt; text-align: right; }
        .c-price { width: 72pt; padding-left: 4pt; padding-right: 4pt; text-align: right; }
        .c-amount { width: 73pt; padding-left: 4pt; padding-right: 8pt; text-align: right; }

        .item { padding: 8pt 0 8pt 0; }
        .item.odd { background: #f6efe9; }
        .item-head-row { page-break-inside: avoid; width: 521pt; }
        .item-head-row td { vertical-align: top; padding-top: 0; padding-bottom: 0; font-size: 10pt; line-height: 13pt; }
        .item-name { font-weight: bold; text-align: left; }
        .item-body { width: 234pt; margin-left: 48pt; padding-top: 4pt; font-size: 10pt; line-height: 13pt; }
        .item-label { font-weight: bold; margin-top: 6pt; }
        .item-text { margin: 0; }

        .totals { float: right; width: auto; margin-top: 14pt; margin-right: 0; page-break-inside: avoid; }
        .totals td { padding: 3pt 10pt; font-size: 10.5pt; color: #2c2d2d; white-space: nowrap; }
        .totals td.value { text-align: right; padding-left: 24pt; }
        .totals tr.grand td {
            background: #f6efe9;
            color: #000000;
            font-family: 'Open Sauce', 'DejaVu Sans', sans-serif;
            font-size: 13pt;
            padding-top: 9pt;
            padding-bottom: 9pt;
            text-transform: uppercase;
        }
        .totals tr.grand td.label { letter-spacing: 1.5pt; }
        .clear { clear: both; height: 0; line-height: 0; font-size: 0; }

        .block { margin-top: 24pt; }
        .block .heading.small-title { page-break-after: avoid; }
    </style>
</head>
<body>
    @php
        $companyName = trim((string) optional(optional($estimate->customer)->company)->name);
        $showSubtotal = count($taxes) > 0 || $estimate->discount_val > 0;

        $addressHtml = (string) $company_address;
        if ($companyName !== '') {
            foreach ([e($companyName), $companyName] as $candidate) {
                $q = preg_quote($candidate, '~');
                $withTag = '~<(h[1-6]|p|div|span|strong|b)\b[^>]*>(\s*<(?:strong|b)\b[^>]*>)?\s*' . $q . '\s*(</(?:strong|b)>\s*)?</\1>~iu';
                $replaced = preg_replace($withTag, '', $addressHtml, 1);
                if ($replaced === null || $replaced === $addressHtml) {
                    $replaced = preg_replace('~' . $q . '(\s*<br\s*/?>)?~iu', '', $addressHtml, 1);
                }
                if ($replaced !== null && $replaced !== $addressHtml) {
                    $addressHtml = $replaced;
                    break;
                }
            }
        }

        // Bloc client : le nom du client s'affiche toujours ; l'adresse s'ajoute si elle existe
        $clientLines = [];
        $customerName = trim((string) optional($estimate->customer)->name);
        $billing = optional($estimate->customer)->billingAddress;
        if ($customerName !== '') {
            $clientLines[] = $customerName;
        } elseif ($billing && trim((string) $billing->name) !== '') {
            $clientLines[] = trim((string) $billing->name);
        }
        if ($billing) {
            $street = collect([$billing->address_street_1, $billing->address_street_2])
                ->map(function ($v) { return trim((string) $v); })
                ->filter()
                ->implode(', ');
            $cityState = collect([$billing->city, $billing->state])
                ->map(function ($v) { return trim((string) $v); })
                ->filter()
                ->implode(' ');
            $countryZip = collect([optional($billing->country)->name, $billing->zip])
                ->map(function ($v) { return trim((string) $v); })
                ->filter()
                ->implode(' ');
            foreach ([$street, $cityState, $countryZip] as $line) {
                if ($line !== '') {
                    $clientLines[] = $line;
                }
            }
        }
    @endphp

    <div class="bar bar-top"></div>
    <div class="bar bar-bottom"></div>

    <table class="w-100">
        <tr>
            <td style="vertical-align: top;">
                <div class="heading title">Soumission</div>
            </td>
            <td class="right right-cell" style="vertical-align: top;">
                <div class="heading title">{{ $companyName }}</div>
            </td>
        </tr>
    </table>

    <table class="w-100" style="margin-top: 10pt;">
        <tr>
            <td style="vertical-align: top;">
                Numéro: {{ $estimate->estimate_number }}<br>
                Date: {{ $estimate->formattedEstimateDate }}<br>
                Valide jusqu’au: {{ $estimate->formattedExpiryDate }}
            </td>
            <td class="right right-cell address" style="vertical-align: top;">
                {!! $addressHtml !!}
            </td>
        </tr>
    </table>

    @if (count($clientLines) > 0)
        <div style="margin-top: 36pt;">
            <div class="heading title" style="margin-bottom: 8pt;">Envoyer à</div>
            @foreach ($clientLines as $line)
                <div class="client-line">{{ $line }}</div>
            @endforeach
        </div>
    @endif

    <div class="wide items-head">
        <table>
            <tr>
                <th class="c-num">#</th>
                <th class="c-svc">@lang('pdf_items_label')</th>
                <th class="c-qty">@lang('pdf_quantity_label')</th>
                <th class="c-price">@lang('pdf_price_label')</th>
                <th class="c-amount">@lang('pdf_amount_label')</th>
            </tr>
        </table>
    </div>

    @foreach ($estimate->items as $item)
        <div class="wide item {{ $loop->odd ? 'odd' : '' }}">
            <table class="item-head-row">
                <tr>
                    <td class="c-num">{{ $loop->iteration }}.</td>
                    <td class="c-svc item-name">{{ $item->name }}</td>
                    <td class="c-qty">{{ $item->quantity }}@if ($item->unit_name) {{ $item->unit_name }}@endif</td>
                    <td class="c-price">{!! format_money_pdf($item->price, $estimate->customer->currency) !!}</td>
                    <td class="c-amount">{!! format_money_pdf($item->total, $estimate->customer->currency) !!}</td>
                </tr>
            </table>
            @if ($item->description || $item->deliverables || $item->delivery_delay || $item->terms)
                <div class="item-body">
                    @if ($item->description)
                        <div class="item-text">{!! nl2br(htmlspecialchars($item->description)) !!}</div>
                    @endif
                    @if ($item->deliverables)
                        <div class="item-label">@lang('pdf_deliverables_label')</div>
                        <div class="item-text">{!! nl2br(htmlspecialchars($item->deliverables)) !!}</div>
                    @endif
                    @if ($item->delivery_delay)
                        <div class="item-label">@lang('pdf_delivery_delay_label')</div>
                        <div class="item-text">{!! nl2br(htmlspecialchars($item->delivery_delay)) !!}</div>
                    @endif
                    @if ($item->terms)
                        <div class="item-label">@lang('pdf_terms_label')</div>
                        <div class="item-text">{!! nl2br(htmlspecialchars($item->terms)) !!}</div>
                    @endif
                </div>
            @endif
        </div>
    @endforeach

    <table class="totals">
        @if ($showSubtotal)
            <tr>
                <td>@lang('pdf_subtotal')</td>
                <td class="value">{!! format_money_pdf($estimate->sub_total, $estimate->customer->currency) !!}</td>
            </tr>
            @if ($estimate->discount_val > 0)
                <tr>
                    <td>
                        @lang('pdf_discount_label')@if ($estimate->discount_type === 'percentage') ({{ $estimate->discount }}%)@endif
                    </td>
                    <td class="value">{!! format_money_pdf($estimate->discount_val, $estimate->customer->currency) !!}</td>
                </tr>
            @endif
            @foreach ($taxes as $tax)
                <tr>
                    <td>{{ $tax->name . ' (' . $tax->percent . '%)' }}</td>
                    <td class="value">{!! format_money_pdf($tax->amount, $estimate->customer->currency) !!}</td>
                </tr>
            @endforeach
        @endif
        <tr class="grand">
            <td class="label">Total:</td>
            <td class="value">{!! format_money_pdf($estimate->total, $estimate->customer->currency) !!}</td>
        </tr>
    </table>
    <div class="clear"></div>

    @if ($notes)
        <div class="block">
            {{-- Libellé statique : à remplacer par la clé de traduction « termes et conditions » des devis une fois créée --}}
            <div class="heading small-title">Termes et conditions</div>
            <div>{!! $notes !!}</div>
        </div>
    @endif
</body>
</html>
