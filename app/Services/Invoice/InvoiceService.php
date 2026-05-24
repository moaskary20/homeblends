<?php

namespace App\Services\Invoice;

use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;

class InvoiceService
{
    public function loadOrder(Order $order): Order
    {
        return $order->load(['items', 'user']);
    }

    public function download(Order $order)
    {
        $order = $this->loadOrder($order);

        $pdf = Pdf::loadView('invoices.order', ['order' => $order])
            ->setPaper('a4');

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    public function stream(Order $order)
    {
        $order = $this->loadOrder($order);

        return Pdf::loadView('invoices.order', ['order' => $order])->stream();
    }

    public function printPage(Order $order): View
    {
        $order = $this->loadOrder($order);

        return view('invoices.print', [
            'order' => $order,
            'downloadUrl' => OrderInvoiceController::downloadUrl($order),
            'backUrl' => OrderInvoiceController::backUrl($order),
        ]);
    }
}
