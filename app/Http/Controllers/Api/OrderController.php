<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusHistoryResource;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepositoryInterface $orders,
        protected InvoiceService $invoices,
    ) {}

    public function index(Request $request)
    {
        $orders = $this->orders->forUser($request->user()->id);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = $this->orders->find($id, ['items.product', 'statusHistory']);

        if (! $order || $order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return new OrderResource($order);
    }

    public function tracking(Request $request, int $id)
    {
        $order = $this->orders->find($id, ['statusHistory']);

        if (! $order || $order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'tracking_number' => $order->tracking_number,
            'shipping_method' => $order->shipping_method,
            'history' => OrderStatusHistoryResource::collection($order->statusHistory),
        ]);
    }

    public function invoice(Request $request, int $id)
    {
        $order = $this->orders->find($id, ['items', 'user']);

        if (! $order || $order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return $this->invoices->download($order);
    }
}
