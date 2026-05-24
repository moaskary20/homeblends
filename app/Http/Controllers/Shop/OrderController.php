<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = collect();

        if ($request->user()) {
            $orders = Order::query()
                ->where('user_id', $request->user()->id)
                ->with('items')
                ->latest()
                ->paginate(10);
        }

        return view('shop.orders.index', compact('orders'));
    }

    public function show(Request $request, string $orderNumber)
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->with(['items', 'statusHistory' => fn ($q) => $q->orderBy('created_at')])
            ->firstOrFail();

        if (! $request->user() || $order->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('shop.orders.show', [
            'order' => $order,
            'seo' => app(SeoService::class)->forPrivatePage($order->order_number),
        ]);
    }
}
