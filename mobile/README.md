# HomeBlend Store — تطبيق الموبايل

تطبيق Flutter للمتجر يتصل بـ REST API في Laravel (`/api/v1`).

## المتطلبات

- Flutter SDK 3.24+
- Laravel backend يعمل محلياً

## التشغيل

```bash
# 1. شغّل Laravel
cd ..
php artisan serve

# 2. شغّل التطبيق
cd mobile
flutter pub get
flutter run
```

التطبيق يختار عنوان API تلقائياً حسب المنصة. يمكنك تجاوزه عبر `--dart-define=API_BASE_URL=...`.

### عناوين API حسب البيئة

| البيئة | العنوان الافتراضي |
|--------|-------------------|
| Web / Linux / Windows / macOS / iOS Simulator | `http://127.0.0.1:8000/api/v1` |
| Android Emulator | `http://10.0.2.2:8000/api/v1` |
| جهاز حقيقي | `http://<IP-الشبكة>:8000/api/v1` (مع `php artisan serve --host=0.0.0.0`) |

## الميزات

- تصفح المنتجات والتصنيفات والبحث
- سلة تسوق (ضيف + مسجل) عبر `X-Session-Id`
- تسجيل دخول / إنشاء حساب مع دمج السلة
- Checkout كامل: عناوين، شحن، كوبون، دفع
- الطلبات والتتبع
- المفضلة
- واجهة عربية RTL بألوان هوم بلند

## هيكل المشروع

```
lib/
├── core/          # شبكة، تخزين، ثيم، router
├── features/      # auth, home, catalog, cart, checkout, orders, account
└── shared/        # models, widgets
```

## الدفع

- **الدفع عند الاستلام (COD)**: يعمل مباشرة بعد تأكيد الطلب
- **PayPal**: يفتح WebView برابط الموافقة من `payment_action` في استجابة الطلب

## الاختبار

```bash
flutter analyze
flutter test
```
