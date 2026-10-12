# dejbansec.ir

کد اختصاصی و سفارشی‌سازی‌های سایت [dejbansec.ir](https://dejbansec.ir) (دژبان‌سک: امنیت سایبری، طراحی وب، توسعه نرم‌افزار).

> کد قالب فرزند و ۹ افزونه‌ی اختصاصی در `wp-content/` قرار دارد و در تاریخ ۲۰ مهر ۱۴۰۵ (2026-10-12) از هاست برداشته شده است.
>
> **محرمانه:** آدرس Webhook افزونه‌ی n8n که مستقیم در کد افزونه‌های `dejbansec-live-chat-n8n-connected` و `dejbansec-live-chat-n8n-reply` (فایل `dejbansec-live-chat.php`) نوشته شده بود، در این نسخه حذف و با مقدار خالی جایگزین شده است. روی سایت این آدرس در تنظیمات افزونه ذخیره است. اگر لازم شد، آن را از همان صفحه‌ی تنظیمات وارد کنید و هرگز در کد قرار ندهید.

## پشته‌ی فنی

- وردپرس 7.1، زبان `fa_IR`، راست‌به‌چپ، میزبانی LiteSpeed
- قالب مادر **Impreza 9.0** (تجاری، داخل مخزن نیست) با افزونه‌های US Core و WPBakery
- قالب فرزند **Dejban Child 1.0.0** (`wp-content/themes/dejban-child/`)
- افزونه‌های دیگر (شخص ثالث، داخل مخزن نیستند): WooCommerce، wp-parsidate، RTL-CareUnit، Burst Statistics، Duplicator Pro، Redirection، Disable Emojis
- فونت: Vazirmatn 33.0.3 (متغیر، ۱۰۰ تا ۹۰۰)، از کتابخانه‌ی فونت خود وردپرس: `wp-content/uploads/fonts/Vazirmatn-wght.woff2`
- همه‌ی تصویرها، فونت‌ها و اسکریپت‌ها از خود دامنه بارگذاری می‌شوند و هیچ درخواستی به CDN خارجی نیست، چون ممکن است در ایران فیلتر باشد.

## افزونه‌های اختصاصی (`wp-content/plugins/`)

| افزونه | نسخه | وضعیت | کار |
|---|---|---|---|
| `dejbansec-ultimate` | 3.0.0 | فعال | صفحه‌ی اصلی (`[dejbansec_ultimate_home]`)، صفحه‌های درباره و تماس، فرم درخواست پروژه، تصاویر `assets/media` |
| `dejbansec-all-services-suite` | 1.0.0 | فعال | شش صفحه‌ی خدمات (`[dejbansec_service service="…"]`) و فرم درخواست هر خدمت |
| `dejbansec-article-center` | 1.0.1 | فعال | مرکز مقالات (`[dejbansec_articles]`)، دسته‌بندی، جستجو و فیلتر |
| `dejbansec-pro-site` | 2.0.0 | فعال | نسخه‌ی قبلی سایت (`[dejbansec_pro_home]`)؛ CSS درون‌خطی `ds-*` را هنوز روی همه‌ی صفحه‌ها چاپ می‌کند |
| `dejbansec-content-import` | 1.0.0 | فعال | ساخت برگه‌ها، دسته‌ها و مقاله‌های اولیه |
| `dejbansec-live-chat-n8n-reply` | 1.2.0 | فعال | چت زنده با REST API (`dejbansec/v1/chat`) و پاسخ از n8n |
| `dsu-integration` (`dejbansec-project-to-livechat`) | 1.0.0 | فعال | اتصال فرم «شروع همکاری» به پرونده‌ی Live Chat |
| `dejbansec-live-chat-n8n-connected` | 1.1.0 | غیرفعال | نسخه‌ی قدیمی چت |
| `dejbansec-live-chat-pro-2.1-fixed` | 2.1.0 | غیرفعال | نسخه‌ی Pro چت با پنل اپراتور |

## سفارشی‌سازی‌های پایگاه داده (`customizations/`)

| فایل | روی سایت کجاست |
|---|---|
| `design-patch.css` | اصلاحیه‌ی طراحی: کنتراست، منوی موبایل، صفحه‌های خدمات، فونت، کارت‌های مقاله، آرشیوها. داخل `<style id="dp-patch">` |
| `card-thumbnails.js` | نمایش تصویر شاخص در کارت‌های مقاله‌ی صفحه‌ی اصلی و مرکز مقالات. داخل `<script id="dp-thumbs">`. در این فایل نباید کاراکتر `&` باشد، چون وردپرس آن را به `&#038;` تبدیل می‌کند. |
| `inner-header.html` | هدر و منوی صفحه‌های داخلی |
| `front-page-8492.html` | محتوای فعلی برگه‌ی خانه (ID 8492): استایل + اسکریپت + شورت‌کد |
| `impreza-templates/6568.txt` `6569.txt` `6489.txt` | محتوای فعلی قالب‌های محتوای Impreza (`us_content_template`) برای برگه‌ها، نوشته‌ها و آرشیوها؛ ردیف `dp-head-row` در ابتدای هر کدام |
| `impreza-templates/original/` | نسخه‌ی اصلی همین سه قالب، پیش از تغییر |
| `additional-css.css` | CSS اضافی سفارشی‌ساز (نمایش ← سفارشی‌سازی ← CSS اضافی)، صادر شده از سایت |
| `redirects.json` | ریدایرکت‌های ۳۰۱ افزونه‌ی Redirection |

سایر تنظیمات:
- عنوان سایت «دژبان‌سک» است.
- برگه‌های تکراری قدیمی (8467 تا 8471 و 6635) پیش‌نویس شده‌اند و آدرس‌هایشان ریدایرکت می‌شود.
- هر ۴۴ مقاله تصویر شاخص WebP دارند که از رسانه‌های خود سایت است.

## استقرار و بازگردانی

**کد:** محتوای `wp-content/themes/dejban-child` و `wp-content/plugins/dejbansec-*` را با SFTP یا مدیر فایل cPanel در همین مسیرها روی هاست بارگذاری کنید. قالب Impreza و افزونه‌های شخص ثالث جداگانه نصب می‌شوند.

**سفارشی‌سازی‌های پایگاه داده:** این کار با `tools/wp.py` از راه REST API و XML-RPC انجام می‌شود. نام کاربری و Application Password فقط از متغیرهای محیطی خوانده می‌شوند و هرگز در فایل ذخیره نکنید:

```sh
export WP_USER=<user> WP_PASS='<application password>'
python tools/wp.py apply-home       # برگه‌ی خانه 8492
python tools/wp.py apply-template   # قالب‌های 6568، 6569، 6489
python tools/wp.py undo-home        # برگرداندن برگه‌ی خانه به فقط شورت‌کد
python tools/wp.py undo-template    # برگرداندن سه قالب به نسخه‌ی original/
```

- **CSS اضافی:** محتوای `additional-css.css` را در سفارشی‌ساز وردپرس قرار دهید.
- **ریدایرکت‌ها:** در ابزارها ← Redirection، طبق `redirects.json` بسازید.

## ابزارها (`tools/`)

- `wp.py`: اعمال و برگرداندن سفارشی‌سازی‌ها
- `cdpshot.py`: عکس تمام‌صفحه با Chrome بدون رابط گرافیکی، در اندازه‌ی دسکتاپ و موبایل
- `netcheck.py`: فهرست هر درخواستی که به دامنه‌ای غیر از dejbansec.ir می‌رود
- `towebp.py`: تبدیل تصویر به WebP با حداکثر عرض ۱۲۰۰ پیکسل و حجم کمتر از ۱۵۰ کیلوبایت
