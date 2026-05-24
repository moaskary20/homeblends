<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentGatewayListResource;
use App\Services\Payment\PaymentGatewayService;

class PaymentGatewayController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateways) {}

    public function index()
    {
        return PaymentGatewayListResource::collection($this->gateways->getActive());
    }
}
