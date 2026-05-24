<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Notifications\ContactMessageNotification;
use App\Services\Seo\SeoService;
use App\Services\Settings\SettingsService;
use App\Services\Shop\ContactPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(ContactPageService $contact, SeoService $seo): View|RedirectResponse
    {
        $content = $contact->resolve();

        if (! $content['is_active']) {
            abort(404);
        }

        return view('shop.contact', [
            'contactPage' => $content,
            'seo' => $seo->forContact(
                $content['seo_title'] ?: $content['page_title'],
                $content['seo_description'] ?: null,
            ),
        ]);
    }

    public function store(Request $request, ContactPageService $contact, SettingsService $settings): RedirectResponse
    {
        $content = $contact->resolve();

        if (! $content['is_active']) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $recipient = $content['form']['recipient_email']
            ?: $settings->get('admin_notification_email')
            ?: config('mail.from.address');

        if (filled($recipient)) {
            $settings->applyMailConfig();
            Notification::route('mail', $recipient)->notify(new ContactMessageNotification($data));
        }

        return redirect()
            ->route('shop.contact')
            ->with('success', __('ecommerce.contact_form_sent'));
    }
}
