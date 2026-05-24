<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index()
    {
        return view('shop.affiliate.index');
    }

    public function apply(Request $request, AffiliateService $affiliates)
    {
        if (! $request->user()) {
            return redirect()->route('shop.affiliate.index')
                ->with('error', __('ecommerce.affiliate_login_required'));
        }

        if ($request->user()->affiliate) {
            return redirect('/affiliate')
                ->with('success', __('ecommerce.affiliate_already_registered'));
        }

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_account' => ['required', 'string', 'max:255'],
        ]);

        $affiliates->apply($request->user(), [
            'display_name' => $data['display_name'],
            'website' => $data['website'] ?? null,
            'bio' => $data['bio'] ?? null,
            'payment_details' => [
                'method' => $data['payment_method'],
                'account' => $data['payment_account'],
            ],
        ]);

        $message = config('affiliate.auto_approve_applications')
            ? __('ecommerce.affiliate_approved_welcome')
            : __('ecommerce.affiliate_application_submitted');

        if (config('affiliate.auto_approve_applications')) {
            return redirect('/affiliate')->with('success', $message);
        }

        return redirect()->route('shop.affiliate.index')->with('success', $message);
    }
}
