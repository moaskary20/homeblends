<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\OrderResource;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Invoice\InvoiceService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceController extends Controller
{
    public function print(Order $order): View
    {
        $this->authorizeOrder($order);

        return app(InvoiceService::class)->printPage($order);
    }

    public function download(Order $order): Response
    {
        $this->authorizeOrder($order);

        return app(InvoiceService::class)->download($order);
    }

    protected function authorizeOrder(Order $order): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->is_admin || $user->can('orders.manage') || $user->can('orders.view')),
            403
        );
    }

    public static function printUrl(Order $order): string
    {
        return route('filament.admin.orders.invoice.print', ['order' => $order]);
    }

    public static function downloadUrl(Order $order): string
    {
        return route('filament.admin.orders.invoice.download', ['order' => $order]);
    }

    public static function backUrl(Order $order): string
    {
        return OrderResource::getUrl('view', ['record' => $order]);
    }
}
