@extends('shop.account._layout', ['current' => 'profile'])

@section('account_content')
    <h1 class="text-2xl font-bold text-[#3d3830] mb-6">{{ __('ecommerce.my_account') }}</h1>

    <section class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-[#3d3830] mb-4">{{ __('ecommerce.avatar') }}</h2>
        <div class="hb-account-avatar-row">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="hb-account-avatar-preview" width="96" height="96">
            <div class="flex-1 space-y-3">
                <form method="post" action="{{ route('shop.account.avatar.update') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-800 file:font-medium">
                        <p class="text-xs text-gray-500 mt-1">{{ __('ecommerce.avatar_hint') }}</p>
                        @error('avatar')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="hb-account-btn-primary">{{ __('ecommerce.upload_avatar') }}</button>
                </form>
                @if($user->hasCustomAvatar())
                    <form method="post" action="{{ route('shop.account.avatar.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('ecommerce.remove_avatar') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <div class="hb-account-profile-grid">
        <section class="bg-white rounded-xl shadow-sm p-6 hb-account-card">
            <h2 class="text-lg font-semibold text-[#3d3830] mb-4">{{ __('ecommerce.user_profile') }}</h2>
            <form method="post" action="{{ route('shop.account.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2">
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.email') }}</label>
                    <input type="email" value="{{ $user->email }}" disabled
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2">
                    @error('phone')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="hb-account-btn-primary">{{ __('ecommerce.save_changes') }}</button>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow-sm p-6 hb-account-card">
            <h2 class="text-lg font-semibold text-[#3d3830] mb-4">{{ __('ecommerce.change_password') }}</h2>
            <form method="post" action="{{ route('shop.account.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.current_password') }}</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2">
                    @error('current_password')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.new_password') }}</label>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2">
                    @error('password')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('ecommerce.password_confirmation') }}</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="hb-account-btn-primary">{{ __('ecommerce.change_password') }}</button>
            </form>
        </section>
    </div>
@endsection
