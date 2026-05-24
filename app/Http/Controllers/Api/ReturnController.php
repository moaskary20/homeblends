<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Return\StoreReturnRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(protected NotificationDispatcher $notifications) {}

    public function index(Request $request)
    {
        $returns = ReturnRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('order')
            ->latest()
            ->paginate(15);

        return ReturnRequestResource::collection($returns);
    }

    public function store(StoreReturnRequest $request)
    {
        $order = Order::query()
            ->where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        $return = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'items' => $request->items,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        $this->notifications->returnRequested($return->load(['order', 'user']));

        return response()->json([
            'message' => __('ecommerce.return_submitted'),
            'return' => new ReturnRequestResource($return),
        ], 201);
    }
}
