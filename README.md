# Bestsheet

سامانه مدیریت فرایند سرمایه‌گذاری، پایش پورتفو، صورت‌های مالی، پرداخت‌ها، مجامع و اسناد.

Laravel 12.61، PHP 8.2+، MySQL؛ رابط Blade و نمودارهای Chart.js.
نسخه برنامه در `VERSION` و تغییرات در `CHANGELOG.md` ثبت می‌شود.

## نصب

راهنمای محیط و تنظیمات: [docs/INSTALL.fa.md](docs/INSTALL.fa.md).

```sh
composer install --prefer-dist --no-interaction
cp .env.example .env
php artisan key:generate
# تنظیم اتصال دیتابیس و سرویس‌ها در .env، سپس:
php artisan migrate --force
```

برای ارتقای محیط موجود، `.env` و APP_KEY را حفظ کنید. پیش از migration نسخه پشتیبان قابل بازیابی تهیه شود. migrate:fresh فقط برای دیتابیس آزمایشی است.

## اعتبارسنجی

```sh
php vendor/bin/phpunit
# فقط روی دیتابیس اختصاصی bestsheet_test؛ جداول آن بازسازی می‌شوند:
php vendor/bin/phpunit --configuration phpunit.mysql.xml
```

اتصال MySQL آزمون از DB_HOST، DB_PORT، DB_USERNAME و DB_PASSWORD دریافت می‌شود؛ نام دیتابیس در تنظیم تست روی bestsheet_test قفل شده است. CI مجموعه کامل را روی SQLite/PHP 8.2 و 8.3 و آزمون‌های یکپارچه منتخب را روی MySQL 8 اجرا می‌کند.

## معماری و انتشار

- [تصمیم معماری مالی v3.3.0](docs/architecture/ADR-001-financial-boundaries.fa.md)
- [گزارش اعتبارسنجی نسخه](docs/RELEASE-v3.3.0.fa.md)
- [معماری قبلی](docs/ARCHITECTURE.fa.md)
- [چک‌لیست پذیرش کاربر](docs/UAT_CHECKLIST.fa.md)

راهنمای docs/evidence و TEST_REPORT قدیمی متعلق به اجرای تاریخی است؛ برای نتیجه نسخه جاری، گزارش انتشار همان نسخه را بخوانید.
