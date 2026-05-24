<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ecommerce.tax_invoice') }} {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            font-size: 14px;
            color: #111;
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
        }
        .print-toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .print-toolbar button,
        .print-toolbar a {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print { background: #d97706; color: #fff; }
        .btn-download { background: #fff; color: #111; border: 1px solid #d1d5db; }
        .btn-back { background: #374151; color: #fff; }
        .invoice-paper {
            background: #fff;
            max-width: 900px;
            margin: 0 auto;
            padding: 32px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        .invoice-header h1 { margin: 0 0 4px; font-size: 24px; }
        .invoice-header .brand { margin: 0; color: #6b7280; }
        .invoice-meta p { margin: 4px 0; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .invoice-table th, .invoice-table td { border: 1px solid #e5e7eb; padding: 10px; text-align: right; }
        .invoice-table th { background: #f9fafb; }
        .invoice-totals { margin-top: 24px; max-width: 360px; margin-right: auto; }
        .invoice-totals p { display: flex; justify-content: space-between; margin: 8px 0; gap: 16px; }
        .invoice-totals .grand-total { font-weight: 700; font-size: 18px; border-top: 2px solid #111; padding-top: 10px; margin-top: 10px; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .invoice-paper { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="no-print print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">
            {{ __('ecommerce.print_invoice') }}
        </button>
        <a class="btn-download" href="{{ $downloadUrl }}">
            {{ __('ecommerce.download_invoice') }}
        </a>
        <a class="btn-back" href="{{ $backUrl }}">
            {{ __('ecommerce.back_to_order') }}
        </a>
    </div>

    <div class="invoice-paper">
        @include('invoices.partials.content', ['order' => $order])
    </div>
</body>
</html>
