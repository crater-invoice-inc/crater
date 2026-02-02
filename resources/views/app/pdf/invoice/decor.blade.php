<!DOCTYPE html>
<html lang="uk">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рахунок на оплату №{{ $invoice->invoice_number }} від {{ $invoice->invoice_date->locale('uk')->isoFormat('D MMMM YYYY р.') }} - {{ $invoice->customer->name }}</title>
    <meta name="author" content="Decor Vibes">
    <meta name="description" content="Instagram: @decor_vibes_if">
    <style>
        /* Скидання полів для PDF генератора */
        @page {
            margin: 10mm; /* Мінімальний безпечний відступ для друку (близько 1 см) */
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.3; /* Трохи компактніше */
            color: #000;
            background-color: #fff; /* Білий фон для PDF */
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            width: 100%;
            max-width: 100%;
            background-color: #fff;
            /* Внутрішній відступ контейнера (якщо @page не спрацює, це буде єдиний відступ) */
            padding: 0; 
            box-shadow: none;
            box-sizing: border-box;
        }

        header {
            margin-bottom: 0;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            border-bottom: none; /* Риску прибрано */
            padding-bottom: 0;
            margin-top: 30px;    /* Додано значний відступ зверху */
            margin-bottom: 30px; /* Додано значний відступ знизу */
            text-align: center;
        }

        .section {
            margin-bottom: 10px;
        }

        /* Таблична структура інфо-блоків */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .info-table td {
            vertical-align: top;
            padding-bottom: 5px;
        }

        .label-cell {
            font-weight: bold;
            width: 160px; /* Збільшено ширину для відступу */
        }

        /* Основна таблиця */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px; /* Зменшено, оскільки додано нотатки */
        }

        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 4px; /* Мінімальний padding в комірках */
            vertical-align: top;
            font-size: 13px; /* Трохи менший шрифт в таблиці для компактності */
        }

        .items-table th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }

        .col-no { width: 30px; text-align: center; }
        .col-qty { width: 50px; text-align: right; }
        .col-unit { width: 50px; text-align: center; }
        .col-price { width: 90px; text-align: right; }
        .col-sum { width: 90px; text-align: right; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row td {
            font-weight: bold;
            border: none;
            padding-top: 8px;
        }
        
        .total-label {
            text-align: right;
            padding-right: 10px;
        }

        .total-value {
            border: 1px solid #000 !important;
        }

        /* Стилі для нотаток */
        .notes-section {
            margin-top: 20px;
            margin-bottom: 40px;
            font-size: 13px;
        }
        
        .notes-section strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .disclaimer-text {
            margin-bottom: 25px;
            line-height: 1.4;
            /* Стилізація дисклеймера */
            border-left: 3px solid #000;
            padding-left: 10px;
            font-style: italic;
            color: #444;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        
        .footer-table td {
            vertical-align: bottom;
        }

        .sign-label {
            width: 90px;
            font-weight: bold;
            text-align: right;
            padding-right: 5px;
        }

        /* Оновлені стилі для блоку підпису з картинкою */
        .sign-line-box {
            border-bottom: 1px solid #000;
            width: 150px; /* Ширина відповідає картинці */
            position: relative;
            display: block;
            height: 20px; /* Висота контейнера, щоб лінія була на своєму місці */
        }

        .signature-img {
            width: 100px;
            height: auto; 
            position: absolute;
            bottom: -28px; /* Зсув униз на 15px відносно лінії, щоб перетнути її */
            left: 30px;
            z-index: 10;
        }

        .sign-name {
            padding-left: 10px;
            white-space: nowrap;
        }

        /* Клас для суми прописом */
        .total-in-words {
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* Клас для терміну оплати */
        .due-date-note {
            margin-top: 20px;
            margin-bottom: 60px;
            font-size: 13px;
            font-style: italic;
            color: #333;
        }

        /* Клас для комірки з підписом */
        .sign-cell {
            width: 150px;
        }
    </style>
</head>
<body>

    <div class="invoice-container">
        
        <div class="section">
            <table class="info-table">
                <tr>
                    <td class="label-cell">Постачальник:</td>
                    <td>
                        <strong>{{ $invoice->company->name }}</strong><br>
                        ІПН: 3253115148<br>
                        {{ $invoice->company->address->zip }}, {{ $invoice->company->address->city }}, {{ $invoice->company->address->address_street_1 }}<br>
                        тел. {{ $invoice->company->address->phone }}<br>
                        IBAN <u>{{ $invoice->company->address->address_street_2 }}</u><br>
                        Є платником єдиного податку
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="info-table">
                <tr>
                    <td class="label-cell">Платник:</td>
                    <td>
                        <strong>{{ $invoice->customer->name }}</strong><br>
                        @if ($invoice->customer->fields && $invoice->customer->fields->count() > 0)
                            @foreach ($invoice->customer->fields as $field)
                                @if ($field->defaultAnswer)
                                    {{ $field->customField->label }}: {{ $field->defaultAnswer }}<br>
                                @endif
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <header>
            <div class="invoice-title">
                РАХУНОК НА ОПЛАТУ №{{ $invoice->invoice_number }}<br>
                від {{ $invoice->invoice_date->locale('uk')->isoFormat('D MMMM YYYY р.') }}
            </div>
        </header>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-no">№</th>
                    <th>Найменування</th>
                    <th class="col-qty">К-сть</th>
                    <th class="col-price">Ціна, грн.</th>
                    <th class="col-sum">Сума, грн.</th>
                </tr>
            </thead>
            <tbody>
            @php
                $index = 1
            @endphp
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="text-center">{{ $index }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-right">{{ $item->quantity }} {{ $item->unit_name }}</td>
                    <td class="text-right">{{ number_format($item->price / 100, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->total / 100, 2, ',', ' ') }}</td>
                </tr>
                @php
                    $index++
                @endphp
            @endforeach
            
            @php
                // Розрахунок правильних сум без помилок округлення
                if ($invoice->discount > 0 && $invoice->discount_type === 'percentage') {
                    $correctDiscountVal = round(($invoice->sub_total * $invoice->discount / 100), 0);
                } else {
                    $correctDiscountVal = $invoice->discount_val;
                }
                $correctTotal = $invoice->sub_total - $correctDiscountVal;
            @endphp
            
            @if($invoice->items->count() > 1)
                <tr class="total-row">
                    <td colspan="4" class="total-label">Підсумок:</td>
                    <td class="total-value text-right">{{ number_format($invoice->sub_total / 100, 2, ',', ' ') }}</td>
                </tr>
            @endif
            
            @if($invoice->discount > 0)
                <tr class="total-row">
                    <td colspan="4" class="total-label">
                        @if($invoice->discount_type === 'fixed')
                            Знижка:
                        @endif
                        @if($invoice->discount_type === 'percentage')
                            Знижка ({{$invoice->discount}}%):
                        @endif
                    </td>
                    <td class="total-value text-right">
                        -{{ number_format($correctDiscountVal / 100, 2, ',', ' ') }}
                    </td>
                </tr>
            @endif
            
            <tr class="total-row">
                <td colspan="4" class="total-label">Всього, Без ПДВ:</td>
                <td class="total-value text-right" id="total-amount">{{ number_format($correctTotal / 100, 2, ',', ' ') }}</td>
            </tr>
            </tbody>
        </table>

        <div class="total-in-words">
            @php
                $correctTotalInWords = \Crater\Helpers\NumberToWordsUkrainian::convert($correctTotal / 100);
            @endphp
            Загальна сума, що підлягає оплаті складає {{ $correctTotalInWords }}
        </div>

        <div class="notes-section">
            @if ($notes)
                <div class="disclaimer-text">
                    {!! $notes !!}
                </div>
            @endif

            <strong>Призначення платежу:</strong>
            <!-- Передоплата за послуги з дизайну та декорування згідно рахунок на оплату №34 від «27» січня 2026 р., без ПДВ. -->
            {{ $invoice->items->first()->name }} згідно рахунку на оплату №{{ $invoice->invoice_number }}
            від {{ $invoice->invoice_date->locale('uk')->isoFormat('D MMMM YYYY р.') }}; Без ПДВ.
        </div>

        @if($invoice->due_date)
            <div class="due-date-note">
                <strong>Рахунок дійсний до сплати до <u>{{ $invoice->due_date->locale('uk')->isoFormat('D MMMM YYYY р.') }}</u></strong>
            </div>
        @endif

        <table class="footer-table">
            <tr>
                <td width="50%"></td> 
                <td class="sign-label">Виписав(ла):</td>
                <td class="sign-cell">
                    <div class="sign-line-box">
                        <img src="https://cdn.incode.icu/decor/signature.png" alt="" class="signature-img">
                    </div>
                </td>
                <td class="sign-name">Н.В. Кучерявець</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>