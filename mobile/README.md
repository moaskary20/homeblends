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

التطبيق يتصل افتراضياً بـ **https://homeblendstore.com/api/v1**. يمكنك تجاوزه عبر `--dart-define=API_BASE_URL=...` أو من «إعدادات الاتصال» داخل التطبيق.

### عناوين API

| البيئة | العنوان |
|--------|---------|
| الإنتاج (الافتراضي) | `https://homeblendstore.com/api/v1` |
| تطوير محلي | `http://127.0.0.1:8000/api/v1` |
| Android Emulator | `http://10.0.2.2:8000/api/v1` |

```bash
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1
```

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

## النشر على Google Play

لا ترفع ملف **debug** — يجب بناء نسخة **release** موقّعة.

### 1) مفاتيح التوقيع (مرة واحدة)

تم إعداد التوقيع في `android/app/build.gradle.kts`. الملفات الحساسة (غير مرفوعة على Git):

| الملف | الغرض |
|-------|--------|
| `android/upload-keystore.jks` | شهادة التوقيع |
| `android/key.properties` | كلمات مرور الـ keystore |
| `android/signing-credentials.local.txt` | نسخة احتياطية من بيانات التوقيع |

احفظ `upload-keystore.jks` وكلمات المرور في مكان آمن — تحتاجها لكل تحديث على المتجر.

إذا أعدت المشروع على جهاز جديد، انسخ `key.properties.example` إلى `key.properties` واملأ القيم.

### 2) بناء App Bundle للرفع

```bash
cd mobile
flutter build appbundle --release
```

الملف الجاهز للرفع:

`build/app/outputs/bundle/release/app-release.aab`

### 3) (اختياري) APK موقّع

```bash
flutter build apk --release
```

الملف: `build/app/outputs/flutter-apk/app-release.apk`
