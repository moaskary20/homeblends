@extends('layouts.shop')

@section('content')
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-4">{{ __('ecommerce.affiliate_program') }}</h1>
        <p class="text-gray-600 mb-8">{{ __('ecommerce.affiliate_program_intro') }}</p>

        @auth
            @if(auth()->user()->affiliate?->isActive())
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-6">
                    <p class="font-semibold text-emerald-800">{{ __('ecommerce.affiliate_active_account') }}</p>
                    <a href="/affiliate" class="inline-block mt-4 bg-emerald-600 text-white px-6 py-2 rounded-lg">
                        {{ __('ecommerce.affiliate_go_dashboard') }}
                    </a>
                </div>
            @elseif(auth()->user()->affiliate)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
                    <p>{{ __('ecommerce.affiliate_pending_review') }}</p>
                </div>
            @else
                <form method="post" action="{{ route('shop.affiliate.apply') }}" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('ecommerce.name') }}</label>
                        <input type="text" name="display_name" value="{{ auth()->user()->name }}" required
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('ecommerce.website') }}</label>
                        <input type="url" name="website" placeholder="https://"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('ecommerce.bio') }}</label>
                        <textarea name="bio" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('ecommerce.payment_method') }}</label>
                        <select name="payment_method" required class="w-full border rounded-lg px-3 py-2">
                            <option value="vodafone_cash">{{ __('ecommerce.payment_vodafone_cash') }}</option>
                            <option value="instapay">{{ __('ecommerce.payment_instapay') }}</option>
                            <option value="bank_transfer">{{ __('ecommerce.payment_bank_transfer') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('ecommerce.payment_account') }}</label>
                        <input type="text" name="payment_account" required
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-semibold">
                        {{ __('ecommerce.affiliate_apply_now') }}
                    </button>
                </form>
            @endif
        @else
            <p class="text-gray-600 mb-4">{{ __('ecommerce.affiliate_login_required') }}</p>
            <a href="{{ route('shop.home') }}" class="text-amber-600 font-medium">{{ __('ecommerce.login') }} →</a>
        @endauth
    </div>
@endsection
