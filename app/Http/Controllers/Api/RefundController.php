<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\StoreRefundRequest;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $refunds = RefundRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('order')
            ->latest()
            ->paginate(15);

        return response()->json($refunds);
    }

    public function store(StoreRefundRequest $request)
    {
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'amount' => $request->amount ?? $order->total,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        $this->notifications->refundRequested($refund->load(['order', 'user']));

        return response()->json([
            'message' => __('ecommerce.refund_submitted'),
            'refund' => $refund,
        ], 201);
    }
}
