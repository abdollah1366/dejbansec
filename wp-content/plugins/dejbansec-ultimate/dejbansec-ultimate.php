<?php
/**
 * Plugin Name: دژبان‌سک Ultimate — وب‌سایت سازمانی و سایبری
 * Description: بسته کامل و قابل ویرایش برای دژبان‌سک: صفحه اصلی حرفه‌ای، خدمات، نمونه‌کار، وبلاگ، FAQ، فرم درخواست پروژه، ویدئوی آپارات، گالری تصاویر، آمار، منوی وردپرس و محتوای اولیه.
 * Version: 3.0.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

define('DSU_VER','3.0.0');
define('DSU_DIR',plugin_dir_path(__FILE__));
define('DSU_URL',plugin_dir_url(__FILE__));

function dsu_asset($file){ return DSU_URL.'assets/media/'.ltrim($file,'/'); }
function dsu_option($key,$default=''){ $v=get_option('dsu_'.$key,$default); return is_string($v)?$v:$default; }

add_action('wp_enqueue_scripts', function(){
    if (is_admin()) return;
    wp_enqueue_style('dsu-css',DSU_URL.'assets/css/site.css',[],DSU_VER);
    wp_enqueue_script('dsu-js',DSU_URL.'assets/js/site.js',[],DSU_VER,true);
});

function dsu_register_cpts(){
    register_post_type('dsu_project',[ 'labels'=>['name'=>'نمونه‌کارها','singular_name'=>'نمونه‌کار','add_new_item'=>'افزودن نمونه‌کار'], 'public'=>true,'menu_icon'=>'dashicons-portfolio','supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true,'rewrite'=>['slug'=>'portfolio'] ]);
    register_post_type('dsu_lead',[ 'labels'=>['name'=>'درخواست‌های پروژه','singular_name'=>'درخواست پروژه'], 'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-email-alt','supports'=>['title','editor'],'show_in_rest'=>false ]);
}
add_action('init','dsu_register_cpts');

function dsu_video_embed(){
    $url=dsu_option('aparat_url','');
    if(!$url) return '';
    if(preg_match('~aparat\.com/(?:v|video)/([A-Za-z0-9_-]+)~',$url,$m)) return 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame';
    if(strpos($url,'aparat.com/video/video/embed/')!==false) return $url;
    return '';
}

function dsu_logo(){
    $logo=dsu_option('logo_url','');
    if($logo) return '<img class="dsu-logo-img" src="'.esc_url($logo).'" alt="دژبان‌سک">';
    return '<span class="dsu-logo-mark">د</span><span>دژبان‌سک</span>';
}

function dsu_home(){
ob_start(); ?>
<div class="dsu" dir="rtl">
<header class="dsu-header"><meta charset="utf-8"><div class="dsu-wrap dsu-nav"><a class="dsu-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php echo dsu_logo(); ?></a><nav class="dsu-menu"><a href="#services">خدمات</a><a href="#security">امنیت</a><a href="#portfolio">نمونه‌کار</a><a href="#learning">آموزش و آزمایشگاه</a><a href="#blog">مقالات</a><a href="#faq">FAQ</a><a href="#contact">تماس</a></nav><a class="dsu-btn dsu-btn-sm" href="#contact">درخواست پروژه</a><button class="dsu-mobile-btn" aria-label="منو">☰</button></div></header>
<section class="dsu-hero"><div class="dsu-wrap dsu-hero-grid"><div class="dsu-hero-copy reveal"><div class="dsu-badge">CYBER SECURITY · WEB · SOFTWARE</div><h1>زیرساخت دیجیتال شما،<br><em>امن، سریع و قابل توسعه.</em></h1><p>دژبان‌سک برای کسب‌وکارهایی ساخته شده که می‌خواهند امنیت، طراحی حرفه‌ای و توسعه نرم‌افزار را در یک مسیر فنی یکپارچه پیش ببرند.</p><div class="dsu-actions"><a class="dsu-btn" href="#contact">شروع پروژه <span>←</span></a><a class="dsu-btn ghost" href="#services">کشف خدمات</a></div><div class="dsu-trust"><span>✓ رویکرد فنی</span><span>✓ طراحی RTL</span><span>✓ توسعه قابل نگهداری</span></div></div><div class="dsu-hero-art reveal"><img src="<?php echo esc_url(dsu_asset('hero-cyber.jpg')); ?>" alt="تصویر امنیت سایبری"><div class="dsu-glass g1">● <b>Security by Design</b><small>امنیت از مرحله طراحی</small></div><div class="dsu-glass g2">↯ <b>Performance</b><small>سرعت و تجربه کاربری</small></div></div></div></section>
<section class="dsu-strip"><div class="dsu-wrap dsu-metrics"><div><strong data-count="3">0</strong><span>حوزه تخصصی</span></div><div><strong data-count="12">0</strong><span>مقاله اولیه</span></div><div><strong data-count="6">0</strong><span>نمونه پروژه</span></div><div><strong>24/7</strong><span>نگاه امنیتی</span></div></div></section>
<section class="dsu-section dsu-services-section" id="services">
<div class="dsu-wrap">
<div class="dsu-title reveal">
<small>خدمات دژبان‌سک</small>
<h2>امنیت، توسعه و زیرساخت؛ یک مسیر یکپارچه برای رشد دیجیتال</h2>
<p>از ارزیابی امنیتی و سخت‌سازی زیرساخت تا طراحی وب، توسعه نرم‌افزار و اتوماسیون؛ راهکارها را متناسب با نیاز واقعی کسب‌وکار طراحی و اجرا می‌کنیم.</p>
</div>
<div class="dsu-services dsu-services-pro">
<?php
$services=[
 ['🛡️','امنیت سایبری','شناسایی آسیب‌پذیری‌ها، ارزیابی ریسک و تقویت امنیت سامانه‌ها پیش از تبدیل شدن نقاط ضعف به حادثه.',['ارزیابی امنیتی','تست نفوذ مجاز','سخت‌سازی','گزارش و اولویت‌بندی ریسک'],'درخواست ارزیابی امنیتی'],
 ['🌐','طراحی و توسعه وب','ساخت وب‌سایت‌های سریع، امن، واکنش‌گرا و قابل توسعه؛ از تجربه کاربری تا استقرار و پشتیبانی.',['UI/UX','WordPress','فروشگاه و سایت شرکتی','توسعه اختصاصی'],'شروع پروژه وب'],
 ['⚙️','توسعه نرم‌افزار','تبدیل فرآیندهای واقعی سازمان به سامانه‌های اختصاصی، پنل‌های مدیریتی و نرم‌افزارهای تحت وب.',['سامانه اختصاصی','پنل مدیریتی','API','داشبورد و سیستم داخلی'],'مشاوره توسعه'],
 ['🔗','API و اتوماسیون','اتصال سرویس‌ها و حذف کارهای تکراری با گردش‌کارهای خودکار و یکپارچه‌سازی داده‌ها.',['طراحی API','Webhook','اتصال CRM و سرویس‌ها','Workflow'],'بررسی اتوماسیون'],
 ['🖥️','امنیت زیرساخت','امن‌سازی سرورها و سرویس‌ها با تمرکز بر دسترسی، پیکربندی، پشتیبان‌گیری، لاگ و کاهش سطح حمله.',['Server Hardening','Firewall و SSH','SSL/TLS','Backup و مانیتورینگ'],'بررسی زیرساخت'],
 ['🚀','سرعت و بهینه‌سازی','کاهش زمان پاسخ، بهبود عملکرد و تجربه کاربری و استفاده بهینه‌تر از منابع سرور.',['WordPress Optimization','Cache و CDN','بهینه‌سازی دیتابیس','Core Web Vitals'],'بررسی عملکرد']
];
foreach($services as $i=>$s):
?>
<article class="dsu-card dsu-service-pro reveal">
 <div class="dsu-service-top"><span class="dsu-service-icon"><?php echo $s[0]; ?></span><span class="dsu-num"><?php echo sprintf('%02d',$i+1); ?></span></div>
 <h3><?php echo esc_html($s[1]); ?></h3>
 <p><?php echo esc_html($s[2]); ?></p>
 <ul class="dsu-service-list"><?php foreach($s[3] as $item): ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
 <a href="#contact" class="dsu-link dsu-service-link"><?php echo esc_html($s[4]); ?> <span>←</span></a>
</article>
<?php endforeach; ?>
</div>
<div class="dsu-services-cta reveal">
 <div><small class="dsu-kicker">مسیر مناسب را پیدا کنیم</small><h3>نمی‌دانید دقیقاً به کدام خدمت نیاز دارید؟</h3><p>وضعیت پروژه، زیرساخت یا فرآیندهای شما را بررسی می‌کنیم و مسیر مناسب را مشخص می‌کنیم.</p></div>
 <div class="dsu-services-actions"><a class="dsu-btn" href="#contact">درخواست مشاوره</a><a class="dsu-btn ghost" href="#contact">شروع پروژه</a></div>
</div>
</div>
</section>
<section class="dsu-section dsu-dark" id="security"><div class="dsu-wrap dsu-split"><div class="dsu-image reveal"><img src="<?php echo esc_url(dsu_asset('penetration-testing.jpg')); ?>" alt="ارزیابی امنیتی"><span class="dsu-image-tag">SECURITY LAB</span></div><div class="reveal"><small class="dsu-kicker">امنیت پیشگیرانه</small><h2>قبل از مهاجم، خودتان نقاط ضعف را پیدا کنید.</h2><p>ارزیابی امنیتی زمانی ارزشمند است که خروجی آن قابل اقدام باشد. در این بسته، یافته‌ها به زبان فنی و مدیریتی دسته‌بندی می‌شوند و برای هر مورد مسیر اصلاح پیشنهاد می‌شود.</p><div class="dsu-checkgrid"><div>✓ سطح حمله و دارایی‌ها</div><div>✓ احراز هویت و دسترسی</div><div>✓ ورودی‌ها و API</div><div>✓ وردپرس و افزونه‌ها</div><div>✓ پیکربندی سرور</div><div>✓ گزارش و اولویت‌بندی</div></div><a class="dsu-btn" href="#contact">درخواست ارزیابی</a></div></div></section>
<section class="dsu-section"><div class="dsu-wrap"><div class="dsu-title reveal"><small>رویکرد</small><h2>پروژه را مثل یک محصول واقعی می‌سازیم</h2></div><div class="dsu-process"><div class="reveal"><b>01</b><h3>شناخت</h3><p>هدف، مخاطب، محدودیت و مسئله اصلی.</p></div><div class="reveal"><b>02</b><h3>معماری</h3><p>انتخاب فناوری و طراحی ساختار قابل توسعه.</p></div><div class="reveal"><b>03</b><h3>طراحی</h3><p>تجربه کاربری، UI و مسیرهای اصلی.</p></div><div class="reveal"><b>04</b><h3>توسعه</h3><p>پیاده‌سازی، تست و بهینه‌سازی.</p></div><div class="reveal"><b>05</b><h3>استقرار</h3><p>تحویل، آموزش، نگهداری و توسعه بعدی.</p></div></div></div></section>
<section class="dsu-section dsu-dark" id="portfolio"><div class="dsu-wrap"><div class="dsu-title reveal"><small>نمونه‌کار</small><h2>نمونه پروژه‌های قابل ویرایش</h2><p>این موارد محتوای نمونه هستند؛ قبل از انتشار عمومی، نام و اطلاعات پروژه‌های واقعی خودتان را جایگزین کنید.</p></div><div class="dsu-portfolio"><?php $q=new WP_Query(['post_type'=>'dsu_project','posts_per_page'=>6,'post_status'=>'publish']); if($q->have_posts()): while($q->have_posts()):$q->the_post(); $thumb=get_the_post_thumbnail_url(get_the_ID(),'large'); ?><article class="dsu-project reveal"><div class="dsu-project-img"><img src="<?php echo esc_url($thumb ?: dsu_asset('dashboard.jpg')); ?>" alt="<?php the_title_attribute(); ?>"></div><div><span>نمونه پروژه</span><h3><?php the_title(); ?></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: strip_tags(get_the_content()),20)); ?></p></div></article><?php endwhile; wp_reset_postdata(); endif; ?></div></div></section>
<section class="dsu-section"><div class="dsu-wrap dsu-video-grid"><div class="reveal"><small class="dsu-kicker">ویدیو</small><h2>معرفی و آموزش‌های دژبان‌سک</h2><p>لینک ویدیوی آپارات را در تنظیمات افزونه وارد کنید. بخش ویدیو به‌صورت خودکار در همین طراحی نمایش داده می‌شود.</p><ul class="dsu-bullets"><li>آموزش امنیت وردپرس</li><li>نکات توسعه و معماری</li><li>راهنمای نگهداری وب‌سایت</li></ul></div><div class="dsu-video reveal"><?php $vu=dsu_video_embed(); if($vu): ?><iframe src="<?php echo esc_url($vu); ?>" allowfullscreen loading="lazy"></iframe><?php else: ?><div><strong>ویدیوی آپارات هنوز تنظیم نشده</strong><p>از مسیر «تنظیمات ← دژبان‌سک Ultimate» لینک ویدیو را وارد کنید.</p></div><?php endif; ?></div></div></section>
<?php echo dsu_learning_section(); ?><section class="dsu-section dsu-dark" id="blog"><div class="dsu-wrap"><div class="dsu-title reveal"><small>دانش فنی</small><h2>آخرین مطالب وبلاگ</h2><p>محتوای اولیه برای شروع سایت ایجاد شده و می‌توانید آن‌ها را از بخش نوشته‌ها ویرایش یا تکمیل کنید.</p></div><div class="dsu-postgrid"><?php $q=new WP_Query(['post_type'=>'post','posts_per_page'=>6,'post_status'=>'publish']); if($q->have_posts()): while($q->have_posts()):$q->the_post(); ?><article class="dsu-post reveal"><div class="dsu-post-meta">DEJBANSEC · <?php echo esc_html(get_the_date('Y/m/d')); ?></div><h3><?php the_title(); ?></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: strip_tags(get_the_content()),25)); ?></p><a href="<?php the_permalink(); ?>" class="dsu-link">ادامه مطلب ←</a></article><?php endwhile; wp_reset_postdata(); endif; ?></div></div></section>
<section class="dsu-section" id="faq"><div class="dsu-wrap dsu-narrow"><div class="dsu-title reveal"><small>FAQ</small><h2>سؤالات متداول</h2></div><div class="dsu-faq"><details class="reveal"><summary>آیا برای تست نفوذ مجوز لازم است؟</summary><p>بله. ارزیابی تهاجمی فقط روی سامانه‌ای انجام می‌شود که مالک آن هستید یا مجوز صریح برای ارزیابی دارید.</p></details><details class="reveal"><summary>وردپرس برای کسب‌وکار من مناسب است؟</summary><p>به نیاز و سطح سفارشی‌سازی بستگی دارد. برای بسیاری از سایت‌های محتوایی و شرکتی انتخاب مناسبی است؛ سامانه‌های پیچیده ممکن است به توسعه اختصاصی نیاز داشته باشند.</p></details><details class="reveal"><summary>آیا بعد از تحویل، پشتیبانی و توسعه ادامه دارد؟</summary><p>بله؛ نگهداری، رفع خطا، به‌روزرسانی، بهینه‌سازی و توسعه امکانات جدید می‌تواند به‌صورت جداگانه برنامه‌ریزی شود.</p></details><details class="reveal"><summary>چطور برای پروژه قیمت دریافت کنم؟</summary><p>فرم درخواست پروژه را تکمیل کنید. پس از بررسی نیازها، محدوده و مسیر فنی مشخص می‌شود و سپس پیشنهاد پروژه ارائه خواهد شد.</p></details></div></div></section>
<section class="dsu-section dsu-contact" id="contact"><div class="dsu-wrap dsu-contact-grid"><div class="reveal"><small class="dsu-kicker">شروع همکاری</small><h2>ایده یا مشکل فنی دارید؟</h2><p>جزئیات پروژه را بفرستید تا بتوانیم دقیق‌تر درباره راهکار، زمان‌بندی و مسیر اجرا صحبت کنیم.</p><div class="dsu-contact-cards"><div>✉ <span>ایمیل</span><b><?php echo esc_html(dsu_option('email','info@dejbansec.ir')); ?></b></div><div>⌁ <span>ساعات پاسخگویی</span><b><?php echo esc_html(dsu_option('hours','شنبه تا پنجشنبه')); ?></b></div></div></div><form class="dsu-form reveal" method="post"><input type="hidden" name="dsu_action" value="lead"><input type="hidden" name="dsu_nonce" value="<?php echo esc_attr(wp_create_nonce('dsu_lead')); ?>"><label>نام و نام خانوادگی<input required name="name" type="text"></label><label>ایمیل یا شماره تماس<input required name="contact" type="text"></label><label>نوع پروژه<select name="project_type"><option>امنیت سایبری</option><option>طراحی سایت</option><option>توسعه نرم‌افزار</option><option>API و اتوماسیون</option><option>مشاوره فنی</option></select></label><label>شرح نیاز<textarea required name="message" rows="5"></textarea></label><button class="dsu-btn" type="submit">ارسال درخواست پروژه ←</button></form></div></section>
<footer class="dsu-footer"><div class="dsu-wrap dsu-footer-grid"><div><a class="dsu-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php echo dsu_logo(); ?></a><p>امنیت سایبری، طراحی وب و توسعه نرم‌افزار.</p></div><div><h4>دسترسی سریع</h4><a href="#services">خدمات</a><a href="#portfolio">نمونه‌کارها</a><a href="#blog">وبلاگ</a></div><div><h4>تماس</h4><a href="#contact">درخواست پروژه</a><span><?php echo esc_html(dsu_option('email','info@dejbansec.ir')); ?></span></div></div><div class="dsu-wrap dsu-copy">© <?php echo date('Y'); ?> دژبان‌سک · تمامی حقوق محفوظ است.</div></footer>
</div>
<?php return ob_get_clean(); }
add_shortcode('dejbansec_ultimate_home','dsu_home');

function dsu_learning_section(){
ob_start(); ?>
<section class="dsu-section dsu-learning" id="learning"><div class="dsu-wrap"><div class="dsu-title reveal"><small>آکادمی دژبان‌سک</small><h2>یادگیری را از مسیر درست شروع کنید</h2><p>آموزش مرحله‌ای امنیت، لینوکس، شبکه، برنامه‌نویسی و توسعه وب با تمرکز روی تمرین و پروژه واقعی.</p></div><div class="dsu-learning-grid">
<article class="dsu-learning-card reveal"><span>مسیر ۰۱</span><h3>مبانی امنیت سایبری</h3><p>شبکه، تهدیدها، احراز هویت، وب و اصول دفاع.</p><div class="dsu-progress"><i style="width:72%"></i></div><small>۱۲ درس · مقدماتی</small><a href="#contact">شروع مسیر ←</a></article>
<article class="dsu-learning-card reveal"><span>مسیر ۰۲</span><h3>امنیت وب و OWASP</h3><p>شناخت آسیب‌پذیری‌های رایج و روش‌های پیشگیری و تست مجاز.</p><div class="dsu-progress"><i style="width:48%"></i></div><small>۱۸ درس · متوسط</small><a href="#contact">مشاهده مسیر ←</a></article>
<article class="dsu-learning-card reveal"><span>مسیر ۰۳</span><h3>برنامه‌نویسی وب</h3><p>HTML، CSS، JavaScript، PHP و ساخت پروژه‌های واقعی.</p><div class="dsu-progress"><i style="width:35%"></i></div><small>۲۴ درس · مقدماتی تا متوسط</small><a href="#contact">شروع یادگیری ←</a></article>
<article class="dsu-lab reveal"><b>LAB</b><h3>آزمایشگاه عملی</h3><p>محیط تمرینی برای تحلیل سناریوهای امنیتی و توسعه امن.</p><ul><li>سناریوهای وب</li><li>تمرین API</li><li>تحلیل لاگ</li></ul><a href="#contact">ورود به آزمایشگاه ←</a></article>
<article class="dsu-lab challenge reveal"><b>CHALLENGE</b><h3>چالش‌های فنی</h3><p>تمرین‌های کوتاه برای سنجش مهارت و ساخت رزومه فنی.</p><ul><li>امنیت</li><li>کدنویسی</li><li>شبکه</li></ul><a href="#contact">مشاهده چالش‌ها ←</a></article>
</div></div></section>
<?php return ob_get_clean(); }

function dsu_basic_page($type){
    $map=[
    'security'=>['امنیت سایبری','ارزیابی امنیت، سخت‌سازی، تحلیل ریسک و تست‌های مجاز.','security-audit.jpg'],
    'web'=>['طراحی سایت','طراحی و توسعه سایت‌های شرکتی، فروشگاهی و اختصاصی.','web-design.jpg'],
    'software'=>['توسعه نرم‌افزار','ساخت سامانه، API، پنل مدیریت و اتوماسیون.','software-dev.jpg'],
    'about'=>['درباره دژبان‌سک','دژبان‌سک یک برند فنی برای ارائه خدمات امنیت، وب و نرم‌افزار است. این صفحه قابل ویرایش است.','about-banner.jpg'],
    'contact'=>['تماس با ما','برای شروع همکاری فرم زیر را تکمیل کنید.','contact-banner.jpg']];
    $x=$map[$type] ?? $map['about'];
    ob_start(); ?>
<div class="dsu" dir="rtl"><section class="dsu-page-hero"><div class="dsu-wrap"><div class="dsu-badge">DEJBANSEC</div><h1><?php echo esc_html($x[0]); ?></h1><p><?php echo esc_html($x[1]); ?></p></div></section><section class="dsu-section"><div class="dsu-wrap dsu-split"><div class="dsu-image"><img src="<?php echo esc_url(dsu_asset($x[2])); ?>" alt=""></div><div><h2><?php echo esc_html($x[0]); ?></h2><p><?php echo esc_html($x[1]); ?> در این صفحه می‌توانید جزئیات، نمونه‌ها، فرآیند، سوالات متداول و محتوای واقعی کسب‌وکار خود را اضافه کنید.</p><div class="dsu-checkgrid"><div>✓ ساختار حرفه‌ای</div><div>✓ طراحی واکنش‌گرا</div><div>✓ محتوای قابل ویرایش</div><div>✓ آماده توسعه</div></div></div></div></section></div><?php return ob_get_clean(); }

add_shortcode('dejbansec_security',fn()=>dsu_basic_page('security'));
add_shortcode('dejbansec_web',fn()=>dsu_basic_page('web'));
add_shortcode('dejbansec_software',fn()=>dsu_basic_page('software'));
add_shortcode('dejbansec_about',fn()=>dsu_basic_page('about'));
add_shortcode('dejbansec_contact',fn()=>dsu_basic_page('contact'));

add_action('init',function(){
 if(!isset($_POST['dsu_action']) || $_POST['dsu_action']!=='lead') return;
 if(!isset($_POST['dsu_nonce']) || !wp_verify_nonce($_POST['dsu_nonce'],'dsu_lead')) return;
 $name=sanitize_text_field($_POST['name']??''); $contact=sanitize_text_field($_POST['contact']??''); $type=sanitize_text_field($_POST['project_type']??''); $msg=sanitize_textarea_field($_POST['message']??'');
 if(!$name||!$contact||!$msg) return;
 $id=wp_insert_post(['post_type'=>'dsu_lead','post_status'=>'private','post_title'=>'درخواست: '.$name.' | '.$type,'post_content'=>"نام: $name\nتماس: $contact\nنوع: $type\n\nشرح:\n$msg"]);
 $to=get_option('admin_email'); if($to) wp_mail($to,'درخواست پروژه جدید از سایت دژبان‌سک',"نام: $name\nتماس: $contact\nنوع: $type\n\n$msg");
 wp_safe_redirect(add_query_arg('dsu_sent','1',wp_get_referer()?:home_url('/')).'#contact'); exit;
});

add_action('admin_menu',function(){ add_options_page('دژبان‌سک Ultimate','دژبان‌سک Ultimate','manage_options','dsu-settings','dsu_settings_page'); });
add_action('admin_init',function(){
 foreach(['aparat_url'=>'','email'=>'info@dejbansec.ir','hours'=>'شنبه تا پنجشنبه','logo_url'=>''] as $k=>$v) register_setting('dsu_settings','dsu_'.$k,['sanitize_callback'=>'sanitize_text_field']);
});
function dsu_settings_page(){ if(!current_user_can('manage_options')) return; ?>
<div class="wrap" dir="rtl"><h1>دژبان‌سک Ultimate</h1><p>تنظیمات اصلی سایت و ویدیوی آپارات.</p><form method="post" action="options.php"><?php settings_fields('dsu_settings'); ?><table class="form-table"><tr><th>لینک ویدیوی آپارات</th><td><input class="regular-text" name="dsu_aparat_url" value="<?php echo esc_attr(dsu_option('aparat_url')); ?>"><p class="description">مثال: https://www.aparat.com/v/XXXX</p></td></tr><tr><th>ایمیل</th><td><input class="regular-text" name="dsu_email" value="<?php echo esc_attr(dsu_option('email','info@dejbansec.ir')); ?>"></td></tr><tr><th>ساعات پاسخگویی</th><td><input class="regular-text" name="dsu_hours" value="<?php echo esc_attr(dsu_option('hours','شنبه تا پنجشنبه')); ?>"></td></tr><tr><th>URL لوگو</th><td><input class="regular-text" name="dsu_logo_url" value="<?php echo esc_attr(dsu_option('logo_url')); ?>"></td></tr></table><?php submit_button('ذخیره تنظیمات'); ?></form></div><?php }

function dsu_seed_content(){
    $pages=[
    ['خانه','خانه','[dejbansec_ultimate_home]'],['امنیت سایبری','امنیت-سایبری','[dejbansec_security]'],['طراحی سایت','طراحی-سایت','[dejbansec_web]'],['توسعه نرم افزار','توسعه-نرم-افزار','[dejbansec_software]'],['درباره ما','درباره-ما','[dejbansec_about]'],['تماس با ما','تماس-با-ما','[dejbansec_contact]']];
    $ids=[]; foreach($pages as $p){ $old=get_page_by_path($p[1]); if($old){$ids[$p[1]]=$old->ID; continue;} $id=wp_insert_post(['post_type'=>'page','post_title'=>$p[0],'post_name'=>$p[1],'post_status'=>'publish','post_content'=>$p[2]]); if($id) $ids[$p[1]]=$id; }
    if(isset($ids['خانه'])) { update_option('show_on_front','page'); update_option('page_on_front',$ids['خانه']); }
    $posts=[
    ['امنیت وردپرس را از کجا شروع کنیم؟','یک چک‌لیست عملی برای بررسی کاربران، افزونه‌ها، نسخه‌ها، دسترسی‌ها، پشتیبان‌گیری و تنظیمات پایه وردپرس.'],
    ['تفاوت اسکن امنیتی و تست نفوذ چیست؟','اسکن خودکار می‌تواند سرنخ‌های زیادی بدهد، اما تست نفوذ مجاز با تحلیل انسانی و سناریوهای کنترل‌شده انجام می‌شود.'],
    ['چرا MFA برای حساب‌های مهم ضروری است؟','رمز عبور به‌تنهایی یک لایه دفاعی کامل نیست. احراز هویت چندمرحله‌ای می‌تواند ریسک سرقت حساب را کاهش دهد.'],
    ['اصول انتخاب هاست برای سایت شرکتی','منابع، پشتیبان‌گیری، دسترسی، مانیتورینگ و کیفیت پشتیبانی از معیارهای مهم انتخاب زیرساخت هستند.'],
    ['API امن چه ویژگی‌هایی دارد؟','احراز هویت، مجوزدهی، اعتبارسنجی ورودی، محدودسازی نرخ، ثبت رخداد و مدیریت خطا از اجزای مهم هستند.'],
    ['بهینه‌سازی سرعت سایت از کجا شروع می‌شود؟','قبل از تغییرات متعدد، وضعیت فعلی را اندازه‌گیری کنید و سپس تصاویر، کش، اسکریپت‌ها و پاسخ سرور را بررسی کنید.'],
    ['امنیت افزونه‌های وردپرس','تعداد افزونه مهم نیست؛ منبع، به‌روزرسانی، کیفیت نگهداری و سطح دسترسی آن‌ها مهم است.'],
    ['پشتیبان‌گیری حرفه‌ای چه تفاوتی دارد؟','پشتیبان خوب فقط فایل نیست؛ باید قابل بازیابی، دوره‌ای، جدا از سرور اصلی و تست‌شده باشد.'],
    ['امنیت پنل مدیریت','URL مخفی به‌تنهایی امنیت نیست؛ MFA، محدودیت دسترسی، رمز قوی، ثبت لاگ و سیاست نشست‌ها اهمیت بیشتری دارند.'],
    ['طراحی سایت امن یعنی چه؟','امنیت باید در فرم‌ها، مدیریت نشست، آپلود فایل، دسترسی‌ها و معماری از ابتدا دیده شود.'],
    ['چرا مستندسازی پروژه مهم است؟','مستندات باعث می‌شود نگهداری، توسعه و تحویل پروژه وابسته به یک نفر نباشد.'],
    ['اتوماسیون چه زمانی ارزشمند است؟','وقتی یک فرآیند تکراری، قابل تعریف و قابل اندازه‌گیری باشد، اتوماسیون می‌تواند زمان و خطا را کاهش دهد.']];
    foreach($posts as $p){ $exists=get_page_by_title($p[0],OBJECT, 'post'); if($exists) continue; wp_insert_post(['post_type'=>'post','post_title'=>$p[0],'post_status'=>'publish','post_excerpt'=>$p[1],'post_content'=>'<p>'.$p[1].'</p><h2>نکات مهم</h2><p>این متن محتوای اولیه قابل ویرایش است. جزئیات واقعی، تجربه‌های پروژه و تصاویر اختصاصی خودتان را به این مقاله اضافه کنید.</p><h2>جمع‌بندی</h2><p>پیش از اجرا، شرایط واقعی سامانه را بررسی و تغییرات را مرحله‌ای اعمال کنید.</p>']); }
    $projects=[['پروژه نمونه: داشبورد مدیریتی','طراحی رابط مدیریتی RTL و ساختار ماژولار برای نمایش داده‌های سازمانی.','dashboard.jpg'],['پروژه نمونه: فروشگاه آنلاین','نمونه طراحی فروشگاه با تمرکز بر تجربه خرید، سرعت و ساختار قابل توسعه.','ecommerce.jpg'],['پروژه نمونه: ارزیابی امنیتی وب','نمونه صفحه معرفی یک ارزیابی امنیتی و گزارش یافته‌ها.','security-audit.jpg'],['پروژه نمونه: API و اتوماسیون','اتصال چند سرویس و ساخت گردش‌کار خودکار برای یک فرآیند سازمانی.','api-automation.jpg'],['پروژه نمونه: زیرساخت امن','نمونه سناریوی بازبینی دسترسی‌ها و سخت‌سازی سرویس‌ها.','cloud-security.jpg'],['پروژه نمونه: طراحی محصول دیجیتال','نمونه رابط کاربری و تجربه کاربری برای یک محصول SaaS.','ui-ux.jpg']];
    require_once ABSPATH.'wp-admin/includes/image.php'; require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php';
    foreach($projects as $p){ $existing=get_page_by_title($p[0],OBJECT,'dsu_project'); if($existing) continue; $id=wp_insert_post(['post_type'=>'dsu_project','post_title'=>$p[0],'post_status'=>'publish','post_excerpt'=>$p[1],'post_content'=>'<p>'.$p[1].'</p><p>این نمونه‌کار محتوای نمونه است و برای نمایش ساختار سایت ایجاد شده است.</p>']); if($id && file_exists(DSU_DIR.'assets/media/'.$p[2])){ $file=['name'=>$p[2],'tmp_name'=>DSU_DIR.'assets/media/'.$p[2]]; $att=media_handle_sideload($file,$id); if(!is_wp_error($att)) set_post_thumbnail($id,$att); }}
}
register_activation_hook(__FILE__,function(){ dsu_register_cpts(); dsu_seed_content(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__,function(){ flush_rewrite_rules(); });
