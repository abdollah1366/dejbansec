<?php
/**
 * Plugin Name: دژبان‌سک — سایت حرفه‌ای
 * Description: ساخت و نمایش سایت حرفه‌ای دژبان‌سک با صفحه اصلی پویا، صفحات خدمات، وبلاگ، FAQ، آمار، فرم تماس، ویدئوی آپارات و تنظیمات اختصاصی.
 * Version: 2.0.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

define('DEJBANSEC_PRO_VERSION','2.0.0');
define('DEJBANSEC_PRO_URL', plugin_dir_url(__FILE__));

function dejbansec_pro_css() {
return <<<'CSS'
:root{--ds-bg:#07111f;--ds-bg2:#0b1728;--ds-card:#0d1d31;--ds-text:#eaf7f5;--ds-muted:#9ab0c4;--ds-accent:#22d3c5;--ds-accent2:#6c7cff;--ds-line:rgba(255,255,255,.09);--ds-shadow:0 24px 70px rgba(0,0,0,.25)}
.ds *{box-sizing:border-box}.ds{direction:rtl;font-family:Tahoma,"Segoe UI",Arial,sans-serif;color:var(--ds-text);background:var(--ds-bg);line-height:1.9;overflow:hidden}.ds a{text-decoration:none}.ds-container{max-width:1180px;margin:auto;padding:0 24px}.ds-top{background:#050c16;border-bottom:1px solid var(--ds-line);font-size:13px;color:#9fb2c4}.ds-top .ds-container{display:flex;justify-content:space-between;gap:20px;padding-top:8px;padding-bottom:8px}.ds-nav{position:sticky;top:0;z-index:50;background:rgba(7,17,31,.86);backdrop-filter:blur(16px);border-bottom:1px solid var(--ds-line)}.ds-navin{display:flex;align-items:center;justify-content:space-between;min-height:76px;gap:22px}.ds-logo{font-weight:900;font-size:23px;color:#fff;display:flex;align-items:center;gap:10px}.ds-logo i{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--ds-accent),var(--ds-accent2));box-shadow:0 10px 30px rgba(34,211,197,.22);font-style:normal;color:#06111d}.ds-menu{display:flex;gap:24px;align-items:center}.ds-menu a{color:#c9d6e2;font-size:14px}.ds-menu a:hover{color:var(--ds-accent)}.ds-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:12px 20px;border-radius:13px;background:linear-gradient(135deg,var(--ds-accent),#62e8dc);color:#03131c!important;font-weight:800;box-shadow:0 12px 30px rgba(34,211,197,.18);transition:.25s}.ds-btn:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(34,211,197,.25)}.ds-btn.ghost{background:transparent;border:1px solid rgba(255,255,255,.18);color:#fff!important;box-shadow:none}.ds-hero{position:relative;min-height:720px;display:grid;align-items:center;background:radial-gradient(circle at 15% 20%,rgba(34,211,197,.13),transparent 30%),radial-gradient(circle at 80% 40%,rgba(108,124,255,.14),transparent 30%),#07111f}.ds-hero:before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:55px 55px;mask-image:linear-gradient(to bottom,#000,transparent)}.ds-hero-grid{position:relative;display:grid;grid-template-columns:1.08fr .92fr;gap:50px;align-items:center}.ds-kicker{display:inline-flex;padding:7px 12px;border:1px solid rgba(34,211,197,.25);background:rgba(34,211,197,.07);color:#7af1e4;border-radius:999px;font-size:13px}.ds-hero h1{font-size:clamp(38px,5vw,70px);line-height:1.2;margin:18px 0;font-weight:950;letter-spacing:-2px}.ds-hero h1 span{color:var(--ds-accent)}.ds-hero p{font-size:18px;color:#a9bdcf;max-width:650px}.ds-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.ds-hero-art{border:1px solid var(--ds-line);border-radius:28px;overflow:hidden;box-shadow:var(--ds-shadow);transform:perspective(900px) rotateY(-4deg)}.ds-hero-art img{display:block;width:100%}.ds-float{display:flex;gap:10px;position:absolute;background:rgba(13,29,49,.86);border:1px solid var(--ds-line);padding:10px 14px;border-radius:14px;backdrop-filter:blur(12px);box-shadow:var(--ds-shadow);font-size:12px}.ds-f1{right:-20px;top:18%}.ds-f2{left:-20px;bottom:16%}.ds-dot{width:9px;height:9px;border-radius:50%;background:var(--ds-accent);margin-top:8px;box-shadow:0 0 18px var(--ds-accent)}.ds-section{padding:95px 0;background:var(--ds-bg)}.ds-section.alt{background:var(--ds-bg2)}.ds-head{text-align:center;max-width:760px;margin:0 auto 46px}.ds-head .eyebrow{color:var(--ds-accent);font-weight:800;font-size:13px}.ds-head h2{font-size:clamp(30px,4vw,48px);margin:7px 0 12px;line-height:1.25}.ds-head p{color:var(--ds-muted);margin:0}.ds-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.ds-card{position:relative;background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,.015));border:1px solid var(--ds-line);border-radius:24px;padding:28px;overflow:hidden;transition:.3s}.ds-card:hover{transform:translateY(-7px);border-color:rgba(34,211,197,.28);box-shadow:0 22px 60px rgba(0,0,0,.18)}.ds-card img{width:100%;border-radius:18px;margin-bottom:20px}.ds-icon{width:52px;height:52px;display:grid;place-items:center;border-radius:16px;background:rgba(34,211,197,.1);color:var(--ds-accent);font-size:23px}.ds-card h3{font-size:22px;margin:14px 0 8px}.ds-card p{color:var(--ds-muted);font-size:14px}.ds-list{padding:0;margin:18px 0 0;list-style:none}.ds-list li{position:relative;padding-right:25px;color:#c7d7e3;font-size:14px;margin:8px 0}.ds-list li:before{content:"✓";position:absolute;right:0;color:var(--ds-accent);font-weight:900}.ds-band{padding:22px 0;background:linear-gradient(90deg,#0b3a45,#10285a);border-block:1px solid rgba(255,255,255,.08)}.ds-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.ds-stat{text-align:center;padding:10px}.ds-stat strong{display:block;font-size:34px;color:#fff}.ds-stat span{color:#9fb2c4;font-size:13px}.ds-split{display:grid;grid-template-columns:1fr 1fr;gap:55px;align-items:center}.ds-img{border-radius:28px;overflow:hidden;border:1px solid var(--ds-line);box-shadow:var(--ds-shadow)}.ds-img img{width:100%;display:block}.ds-checks{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:25px}.ds-check{background:rgba(255,255,255,.035);border:1px solid var(--ds-line);padding:13px 15px;border-radius:14px;color:#d7e4ed;font-size:13px}.ds-check b{color:var(--ds-accent);margin-left:7px}.ds-video{position:relative;aspect-ratio:16/9;border-radius:25px;overflow:hidden;border:1px solid var(--ds-line);background:linear-gradient(135deg,#0b1728,#102f43);display:grid;place-items:center}.ds-video iframe{width:100%;height:100%;border:0}.ds-video-empty{padding:30px;text-align:center}.ds-video-empty strong{font-size:20px;display:block;margin-bottom:8px}.ds-video-empty span{color:var(--ds-muted);font-size:13px}.ds-process{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}.ds-step{padding:22px;border:1px solid var(--ds-line);border-radius:20px;background:rgba(255,255,255,.025);position:relative}.ds-step b{width:36px;height:36px;border-radius:12px;background:rgba(34,211,197,.12);color:var(--ds-accent);display:grid;place-items:center}.ds-step h4{margin:15px 0 4px}.ds-step p{margin:0;color:var(--ds-muted);font-size:12px}.ds-posts{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.ds-post{padding:25px;border:1px solid var(--ds-line);border-radius:22px;background:#0b192b}.ds-post .tag{color:var(--ds-accent);font-size:12px}.ds-post h3{font-size:19px;line-height:1.6}.ds-post p{color:var(--ds-muted);font-size:13px}.ds-faq{max-width:850px;margin:auto}.ds-faq details{background:rgba(255,255,255,.025);border:1px solid var(--ds-line);border-radius:16px;padding:16px 20px;margin:10px 0}.ds-faq summary{cursor:pointer;font-weight:800}.ds-faq p{color:var(--ds-muted);font-size:14px;margin-bottom:0}.ds-cta{padding:70px 0}.ds-cta-box{border:1px solid rgba(34,211,197,.22);background:radial-gradient(circle at 15% 20%,rgba(34,211,197,.13),transparent 40%),linear-gradient(135deg,#0c1e32,#0b2c40);border-radius:30px;padding:45px;display:flex;justify-content:space-between;align-items:center;gap:25px}.ds-cta h2{margin:0 0 8px;font-size:32px}.ds-cta p{margin:0;color:var(--ds-muted)}.ds-footer{background:#040b14;border-top:1px solid var(--ds-line);padding:55px 0 25px}.ds-footergrid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:40px}.ds-footer h3{font-size:17px}.ds-footer p,.ds-footer a{color:#8ea3b5;font-size:13px}.ds-copy{border-top:1px solid var(--ds-line);margin-top:35px;padding-top:18px;color:#71869a;font-size:12px;display:flex;justify-content:space-between}.ds-reveal{opacity:0;transform:translateY(20px);transition:.7s ease}.ds-reveal.on{opacity:1;transform:none}.ds-mobile{display:none}
@media(max-width:900px){.ds-menu{display:none}.ds-mobile{display:block}.ds-hero-grid,.ds-split{grid-template-columns:1fr}.ds-hero{min-height:auto;padding:80px 0}.ds-hero-art{transform:none}.ds-grid3,.ds-posts{grid-template-columns:1fr 1fr}.ds-process{grid-template-columns:1fr 1fr}.ds-stats{grid-template-columns:1fr 1fr}.ds-cta-box{display:block}.ds-cta .ds-btn{margin-top:20px}.ds-footergrid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.ds-top{display:none}.ds-grid3,.ds-posts,.ds-process,.ds-footergrid{grid-template-columns:1fr}.ds-checks{grid-template-columns:1fr}.ds-hero h1{font-size:38px}.ds-section{padding:65px 0}.ds-container{padding:0 17px}.ds-f1,.ds-f2{display:none}.ds-copy{display:block}.ds-copy span{display:block;margin-top:8px}}
CSS;
}
add_action('wp_head', function(){ if (is_page() || is_front_page()) echo '<style id="dejbansec-pro-css">'.dejbansec_pro_css().'</style>'; });

function dejbansec_pro_asset($name){ return DEJBANSEC_PRO_URL.'assets/'.$name; }

function dejbansec_pro_video_url(){
    $url = get_option('dejbansec_aparat_url','');
    if (!$url) return '';
    if (preg_match('~aparat\.com/(?:v|video)/([A-Za-z0-9_-]+)~', $url, $m)) {
        return 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame';
    }
    if (strpos($url,'aparat.com/video/video/embed/')!==false) return $url;
    return '';
}

function dejbansec_pro_home(){
    ob_start(); ?>
<div class="ds">
<div class="ds-top"><div class="ds-container"><span>دژبان‌سک | امنیت سایبری، طراحی وب و توسعه نرم‌افزار</span><span>راهکارهای دیجیتال امن و قابل توسعه</span></div></div>
<nav class="ds-nav"><div class="ds-container ds-navin"><a class="ds-logo" href="<?php echo esc_url(home_url('/')); ?>"><i>د</i> دژبان‌سک</a><div class="ds-menu">
<a href="#services">خدمات</a><a href="#security">امنیت</a><a href="#process">فرآیند همکاری</a><a href="#blog">مقالات</a><a href="#faq">سوالات متداول</a></div><a class="ds-btn" href="<?php echo esc_url(home_url('/تماس-با-ما/')); ?>">درخواست مشاوره</a><div class="ds-mobile">☰</div></div></nav>

<section class="ds-hero"><div class="ds-container ds-hero-grid"><div class="ds-reveal"><span class="ds-kicker">CYBER SECURITY • WEB • SOFTWARE</span><h1>امن‌تر بسازید،<br><span>حرفه‌ای‌تر توسعه دهید.</span></h1><p>دژبان‌سک راهکارهای امنیت سایبری، طراحی وب و توسعه نرم‌افزار را برای کسب‌وکارهایی ارائه می‌کند که کیفیت، امنیت و قابلیت توسعه برایشان مهم است.</p><div class="ds-actions"><a class="ds-btn" href="<?php echo esc_url(home_url('/تماس-با-ما/')); ?>">شروع یک پروژه</a><a class="ds-btn ghost" href="#services">مشاهده خدمات</a></div></div>
<div class="ds-hero-art ds-reveal"><img src="<?php echo esc_url(dejbansec_pro_asset('hero.svg')); ?>" alt="امنیت سایبری دژبان‌سک"><div class="ds-float ds-f1"><span class="ds-dot"></span> معماری امن و قابل توسعه</div><div class="ds-float ds-f2"><span class="ds-dot"></span> بررسی فنی و امنیتی</div></div></div></section>

<section class="ds-band"><div class="ds-container ds-stats"><div class="ds-stat"><strong data-count="3">0</strong><span>حوزه تخصصی</span></div><div class="ds-stat"><strong data-count="10">0</strong><span>مقاله آموزشی اولیه</span></div><div class="ds-stat"><strong data-count="24">0</strong><span>نگاه امنیتی و فنی</span></div><div class="ds-stat"><strong>∞</strong><span>قابلیت توسعه</span></div></div></section>

<section class="ds-section" id="services"><div class="ds-container"><div class="ds-head ds-reveal"><span class="eyebrow">خدمات ما</span><h2>یک تیم فنی برای سه نیاز مهم</h2><p>از ارزیابی امنیت تا طراحی تجربه دیجیتال و ساخت نرم‌افزار اختصاصی، مسیر پروژه را یکپارچه می‌بینیم.</p></div>
<div class="ds-grid3">
<article class="ds-card ds-reveal"><img src="<?php echo esc_url(dejbansec_pro_asset('security.svg')); ?>" alt="خدمات امنیت سایبری"><div class="ds-icon">⌁</div><h3>امنیت سایبری</h3><p>شناسایی ریسک‌ها و آسیب‌پذیری‌های وب‌سایت، وردپرس، API و زیرساخت با رویکرد کنترل‌شده و مجاز.</p><ul class="ds-list"><li>ارزیابی امنیت</li><li>تست نفوذ مجاز</li><li>سخت‌سازی وردپرس و سرور</li><li>گزارش فنی و راهکار اصلاح</li></ul><a class="ds-btn" href="<?php echo esc_url(home_url('/امنیت-سایبری/')); ?>">جزئیات امنیت</a></article>
<article class="ds-card ds-reveal"><img src="<?php echo esc_url(dejbansec_pro_asset('web.svg')); ?>" alt="طراحی سایت"><div class="ds-icon">◈</div><h3>طراحی سایت</h3><p>طراحی وب‌سایت‌های شرکتی، فروشگاهی و اختصاصی با تمرکز بر سرعت، تجربه کاربری و ساختار فنی.</p><ul class="ds-list"><li>وردپرس و امکانات اختصاصی</li><li>طراحی واکنش‌گرا</li><li>بهینه‌سازی سرعت</li><li>ساختار مناسب SEO</li></ul><a class="ds-btn" href="<?php echo esc_url(home_url('/طراحی-سایت/')); ?>">جزئیات طراحی</a></article>
<article class="ds-card ds-reveal"><img src="<?php echo esc_url(dejbansec_pro_asset('software.svg')); ?>" alt="توسعه نرم افزار"><div class="ds-icon">{ }</div><h3>توسعه نرم‌افزار</h3><p>ساخت سامانه‌های تحت وب، پنل مدیریت، API و اتوماسیون بر اساس فرآیندهای واقعی کسب‌وکار.</p><ul class="ds-list"><li>سامانه اختصاصی</li><li>API و وب‌سرویس</li><li>اتوماسیون</li><li>اتصال سیستم‌ها</li></ul><a class="ds-btn" href="<?php echo esc_url(home_url('/توسعه-نرم-افزار/')); ?>">جزئیات توسعه</a></article>
</div></div></section>

<section class="ds-section alt" id="security"><div class="ds-container ds-split"><div class="ds-img ds-reveal"><img src="<?php echo esc_url(dejbansec_pro_asset('about.svg')); ?>" alt="رویکرد امنیتی دژبان‌سک"></div><div class="ds-reveal"><span class="eyebrow">امنیت از ابتدا</span><h2>امنیت را بعد از حادثه شروع نکنید.</h2><p>یک سایت یا سامانه امن، نتیجه مجموعه‌ای از تصمیم‌های درست در طراحی، توسعه، پیکربندی، دسترسی و نگهداری است.</p><div class="ds-checks"><div class="ds-check"><b>✓</b> بررسی سطح حمله</div><div class="ds-check"><b>✓</b> کنترل دسترسی‌ها</div><div class="ds-check"><b>✓</b> بررسی ورودی و خروجی</div><div class="ds-check"><b>✓</b> پشتیبان‌گیری</div><div class="ds-check"><b>✓</b> پایش و لاگ</div><div class="ds-check"><b>✓</b> گزارش و اصلاح</div></div></div></div></section>

<section class="ds-section"><div class="ds-container"><div class="ds-head ds-reveal"><span class="eyebrow">ویدئو</span><h2>آموزش و محتوای ویدیویی</h2><p>ویدئوهای آموزشی امنیت و فناوری را در این بخش نمایش دهید.</p></div>
<div class="ds-video ds-reveal"><?php $vu=dejbansec_pro_video_url(); if($vu): ?><iframe src="<?php echo esc_url($vu); ?>" allowfullscreen loading="lazy"></iframe><?php else: ?><div class="ds-video-empty"><strong>ویدیوی معرفی دژبان‌سک</strong><span>لینک ویدیوی آپارات را از «تنظیمات ← دژبان‌سک» وارد کنید تا این بخش خودکار فعال شود.</span></div><?php endif; ?></div></div></section>

<section class="ds-section alt" id="process"><div class="ds-container"><div class="ds-head ds-reveal"><span class="eyebrow">فرآیند همکاری</span><h2>از ایده تا اجرا، مرحله‌به‌مرحله</h2></div><div class="ds-process">
<div class="ds-step ds-reveal"><b>۱</b><h4>شناخت نیاز</h4><p>اهداف و مسئله اصلی پروژه مشخص می‌شود.</p></div>
<div class="ds-step ds-reveal"><b>۲</b><h4>تحلیل</h4><p>راهکار فنی و محدوده پروژه تعیین می‌شود.</p></div>
<div class="ds-step ds-reveal"><b>۳</b><h4>طراحی</h4><p>معماری، تجربه کاربری و مسیر اجرا طراحی می‌شود.</p></div>
<div class="ds-step ds-reveal"><b>۴</b><h4>توسعه</h4><p>پیاده‌سازی، تست و بهینه‌سازی انجام می‌شود.</p></div>
<div class="ds-step ds-reveal"><b>۵</b><h4>تحویل</h4><p>استقرار، آموزش و پشتیبانی ادامه پیدا می‌کند.</p></div>
</div></div></section>

<section class="ds-section" id="blog"><div class="ds-container"><div class="ds-head ds-reveal"><span class="eyebrow">وبلاگ</span><h2>دانش فنی برای تصمیم‌های بهتر</h2><p>مطالب آموزشی دژبان‌سک درباره امنیت، وردپرس، وب و نرم‌افزار.</p></div><div class="ds-posts"><?php $q=new WP_Query(['post_type'=>'post','posts_per_page'=>6,'post_status'=>'publish']); if($q->have_posts()): while($q->have_posts()):$q->the_post(); ?><article class="ds-post ds-reveal"><span class="tag">مقاله تخصصی</span><h3><?php the_title(); ?></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: strip_tags(get_the_content()),24)); ?></p><a href="<?php the_permalink(); ?>" class="ds-btn">مطالعه مقاله</a></article><?php endwhile; wp_reset_postdata(); else: ?><article class="ds-post"><span class="tag">به‌زودی</span><h3>مقالات تخصصی دژبان‌سک</h3><p>محتوای آموزشی به‌زودی در این بخش منتشر می‌شود.</p></article><?php endif; ?></div></div></section>

<section class="ds-section alt" id="faq"><div class="ds-container"><div class="ds-head ds-reveal"><span class="eyebrow">FAQ</span><h2>سوالات متداول</h2></div><div class="ds-faq">
<details class="ds-reveal"><summary>آیا امنیت سایت وردپرسی را بررسی می‌کنید؟</summary><p>بله. بررسی می‌تواند شامل تنظیمات، افزونه‌ها، کاربران، ورودی‌ها، پیکربندی و سایر بخش‌های مرتبط باشد.</p></details>
<details class="ds-reveal"><summary>آیا تست نفوذ انجام می‌دهید؟</summary><p>بله، برای سامانه‌هایی که مالک آن هستید یا مجوز صریح برای ارزیابی آن‌ها دارید.</p></details>
<details class="ds-reveal"><summary>سایت اختصاصی بهتر است یا وردپرس؟</summary><p>انتخاب به نیاز، بودجه، زمان و میزان سفارشی‌سازی مورد نیاز بستگی دارد.</p></details>
<details class="ds-reveal"><summary>آیا بعد از طراحی سایت پشتیبانی دارید؟</summary><p>بله، امکان نگهداری، رفع مشکل، به‌روزرسانی و توسعه امکانات جدید وجود دارد.</p></details>
</div></div></section>

<section class="ds-cta"><div class="ds-container"><div class="ds-cta-box ds-reveal"><div><h2>برای پروژه بعدی آماده‌اید؟</h2><p>نیاز خود را بگویید؛ درباره راهکار فنی مناسب با شما صحبت می‌کنیم.</p></div><a class="ds-btn" href="<?php echo esc_url(home_url('/تماس-با-ما/')); ?>">درخواست مشاوره</a></div></div></section>

<footer class="ds-footer"><div class="ds-container"><div class="ds-footergrid"><div><a class="ds-logo" href="<?php echo esc_url(home_url('/')); ?>"><i>د</i> دژبان‌سک</a><p>امنیت سایبری، طراحی وب و توسعه نرم‌افزار برای کسب‌وکارهای دیجیتال.</p></div><div><h3>خدمات</h3><p><a href="<?php echo esc_url(home_url('/امنیت-سایبری/')); ?>">امنیت سایبری</a></p><p><a href="<?php echo esc_url(home_url('/طراحی-سایت/')); ?>">طراحی سایت</a></p><p><a href="<?php echo esc_url(home_url('/توسعه-نرم-افزار/')); ?>">توسعه نرم‌افزار</a></p></div><div><h3>ارتباط</h3><p><a href="<?php echo esc_url(home_url('/تماس-با-ما/')); ?>">تماس با ما</a></p><p><a href="<?php echo esc_url(home_url('/وبلاگ/')); ?>">وبلاگ</a></p></div></div><div class="ds-copy"><span>© <?php echo date('Y'); ?> دژبان‌سک</span><span>امنیت | طراحی | توسعه</span></div></div></footer>
</div>
<?php return ob_get_clean();
}
add_shortcode('dejbansec_pro_home','dejbansec_pro_home');

function dejbansec_pro_enqueue(){ wp_enqueue_script('dejbansec-pro-js', DEJBANSEC_PRO_URL.'assets/site.js', [], DEJBANSEC_PRO_VERSION, true); }
add_action('wp_enqueue_scripts','dejbansec_pro_enqueue');

function dejbansec_pro_admin_menu(){
 add_options_page('تنظیمات دژبان‌سک','دژبان‌سک','manage_options','dejbansec-pro','dejbansec_pro_settings');
}
add_action('admin_menu','dejbansec_pro_admin_menu');

function dejbansec_pro_settings(){
 if(!current_user_can('manage_options')) return;
 if(isset($_POST['dejbansec_save']) && check_admin_referer('dejbansec_save')){
   update_option('dejbansec_aparat_url', esc_url_raw($_POST['dejbansec_aparat_url'] ?? ''));
   echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
 }
 $url=get_option('dejbansec_aparat_url','');
 ?>
 <div class="wrap" dir="rtl">
 <h1>تنظیمات دژبان‌سک</h1>
 <p>لینک ویدیوی آپارات را وارد کنید. نمونه: <code>https://www.aparat.com/v/XXXX</code></p>
 <form method="post"><?php wp_nonce_field('dejbansec_save'); ?>
 <table class="form-table"><tr><th>لینک ویدئوی آپارات</th><td><input type="url" name="dejbansec_aparat_url" value="<?php echo esc_attr($url); ?>" class="regular-text" placeholder="https://www.aparat.com/v/..."></td></tr></table>
 <p><button class="button button-primary" name="dejbansec_save" value="1">ذخیره</button></p></form>
 <hr><h2>نکته</h2><p>این افزونه برای صفحه اصلی یک طراحی مستقل و واکنش‌گرا دارد و محتوای دمو را حذف نمی‌کند.</p>
 </div>
 <?php
}

function dejbansec_pro_create_page($title,$slug,$content){
 $p=get_page_by_path($slug,OBJECT,'page');
 $data=['post_title'=>$title,'post_name'=>$slug,'post_content'=>$content,'post_status'=>'publish','post_type'=>'page','post_author'=>get_current_user_id()];
 if($p){$data['ID']=$p->ID;return wp_update_post(wp_slash($data),true);}
 return wp_insert_post(wp_slash($data),true);
}
function dejbansec_pro_activate(){
 $home=dejbansec_pro_create_page('خانه','home','[dejbansec_pro_home]');
 $pages=[
 ['امنیت سایبری','cyber-security','<h1>خدمات امنیت سایبری</h1><p>ارزیابی امنیت وب‌سایت، وردپرس، API و زیرساخت با محدوده مشخص و مجاز.</p><h2>خدمات</h2><ul><li>ارزیابی امنیت وب</li><li>تست نفوذ مجاز</li><li>بررسی امنیت وردپرس</li><li>بررسی API و سرور</li><li>گزارش فنی و راهکار اصلاح</li></ul>'],
 ['طراحی سایت','web-design','<h1>طراحی و توسعه وب‌سایت</h1><p>طراحی سایت شرکتی، فروشگاهی و اختصاصی با تمرکز بر تجربه کاربری، سرعت، ساختار فنی و امنیت.</p><h2>خدمات</h2><ul><li>وردپرس</li><li>امکانات اختصاصی</li><li>طراحی واکنش‌گرا</li><li>بهینه‌سازی سرعت و SEO</li></ul>'],
 ['توسعه نرم‌افزار','software-development','<h1>توسعه نرم‌افزار</h1><p>ساخت سامانه‌های تحت وب، پنل‌های مدیریتی، API و اتوماسیون متناسب با نیاز کسب‌وکار.</p><h2>حوزه‌ها</h2><ul><li>سامانه تحت وب</li><li>API</li><li>اتوماسیون</li><li>اتصال سیستم‌ها</li></ul>'],
 ['درباره ما','about','<h1>درباره دژبان‌سک</h1><p>دژبان‌سک با تمرکز بر امنیت سایبری، طراحی وب و توسعه نرم‌افزار فعالیت می‌کند و راهکارهای فنی متناسب با نیاز پروژه ارائه می‌دهد.</p>'],
 ['تماس با ما','contact','<h1>تماس با دژبان‌سک</h1><p>برای دریافت مشاوره درباره امنیت، طراحی سایت یا توسعه نرم‌افزار با ما در ارتباط باشید.</p><p>اطلاعات تماس و فرم تماس خود را در این صفحه قرار دهید.</p>'],
 ['وبلاگ','blog','<h1>وبلاگ دژبان‌سک</h1><p>مقالات آموزشی امنیت، وردپرس، طراحی وب و توسعه نرم‌افزار.</p>']
 ];
 foreach($pages as $p) dejbansec_pro_create_page($p[0],$p[1],$p[2]);
 update_option('show_on_front','page'); if($home && !is_wp_error($home)) update_option('page_on_front',$home);
 $blog=get_page_by_path('blog',OBJECT,'page'); if($blog) update_option('page_for_posts',$blog->ID);
}
register_activation_hook(__FILE__,'dejbansec_pro_activate');
