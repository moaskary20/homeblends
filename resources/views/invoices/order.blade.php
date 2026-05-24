<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('ecommerce.tax_invoice') }} {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #111; }
        .invoice-header h1 { margin: 0 0 4px; font-size: 22px; }
        .invoice-header .brand { margin: 0; color: #666; }
        .invoice-meta p { margin: 4px 0; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        .invoice-table th { background: #f5f5f5; }
        .invoice-totals { margin-top: 20px; max-width: 320px; margin-right: auto; }
        .invoice-totals p { display: flex; justify-content: space-between; margin: 6px 0; }
        .invoice-totals .grand-total { font-weight: bold; font-size: 16px; border-top: 2px solid #111; padding-top: 8px; }
    </style>
</head>
<body>
    @include('invoices.partials.content', ['order' => $order])
</body>
</html>
