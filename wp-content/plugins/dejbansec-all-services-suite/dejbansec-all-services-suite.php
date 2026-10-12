<?php
/**
 * Plugin Name: دژبان‌سک — بسته کامل خدمات
 * Description: شش صفحه کامل خدمات، اتصال کارت‌های صفحه اصلی، فرم درخواست اختصاصی و طراحی واکنش‌گرا برای خدمات دژبان‌سک.
 * Version: 1.0.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

function dss_services(){
    return [
        'security'=>[
            'title'=>'امنیت سایبری','slug'=>'امنیت-سایبری',
            'lead'=>'امنیت را از حد حدس و نگرانی خارج کنید؛ دارایی‌ها، نقاط ورود و ریسک‌های واقعی سامانه را شناسایی و برای اصلاح اولویت‌بندی کنید.',
            'image'=>'security-audit.jpg',
            'features'=>['ارزیابی سطح حمله و دارایی‌ها','تست امنیتی مجاز و کنترل‌شده','بررسی احراز هویت و دسترسی','تحلیل وردپرس، افزونه‌ها و API','گزارش فنی و مدیریتی قابل اقدام','پیشنهاد مسیر اصلاح و سخت‌سازی'],
            'outputs'=>['گزارش یافته‌ها با سطح ریسک','اولویت‌بندی اقدامات اصلاحی','چک‌لیست سخت‌سازی','جلسه توضیح نتایج و پرسش‌ها'],
        ],
        'web'=>[
            'title'=>'طراحی و توسعه وب','slug'=>'طراحی-و-توسعه-وب',
            'lead'=>'وب‌سایتی بسازید که فقط زیبا نباشد؛ سریع، واکنش‌گرا، قابل نگهداری و متناسب با مسیر واقعی کسب‌وکار شما باشد.',
            'image'=>'web-design.jpg',
            'features'=>['UI/UX و طراحی RTL','WordPress و WooCommerce','سایت شرکتی و فروشگاهی','توسعه اختصاصی و اتصال سرویس‌ها','بهینه‌سازی سرعت و ساختار SEO','استقرار، آموزش و پشتیبانی'],
            'outputs'=>['طراحی رابط و صفحات کلیدی','نسخه واکنش‌گرا','ساختار محتوایی قابل مدیریت','تحویل و آموزش مدیریت سایت'],
        ],
        'software'=>[
            'title'=>'توسعه نرم‌افزار','slug'=>'توسعه-نرم-افزار',
            'lead'=>'فرآیندهای واقعی سازمان را به سامانه‌های اختصاصی، پنل‌های مدیریتی و نرم‌افزارهای تحت وب قابل توسعه تبدیل کنید.',
            'image'=>'software-dev.jpg',
            'features'=>['تحلیل نیاز و معماری','سامانه‌های تحت وب','پنل مدیریتی و داشبورد','API و سرویس‌های داخلی','طراحی دیتابیس و نقش‌های دسترسی','تست، استقرار و مستندسازی'],
            'outputs'=>['مستند نیازمندی و معماری','نسخه عملیاتی سامانه','مستندات فنی و کاربری','برنامه توسعه مرحله بعد'],
        ],
        'api'=>[
            'title'=>'API و اتوماسیون','slug'=>'api-و-اتوماسیون',
            'lead'=>'سیستم‌های پراکنده را به هم متصل کنید و کارهای تکراری را با گردش‌کارهای قابل پایش، API و وب‌هوک کاهش دهید.',
            'image'=>'api-automation.jpg',
            'features'=>['طراحی و توسعه API','Webhook و رویدادها','اتصال CRM و سرویس‌ها','Workflow و اتوماسیون','اعتبارسنجی و کنترل دسترسی','لاگ و پایش فرآیندها'],
            'outputs'=>['نقشه اتصال سرویس‌ها','API یا Workflow عملیاتی','مستندات endpointها','گزارش خطا و پایش'],
        ],
        'infra'=>[
            'title'=>'امنیت زیرساخت','slug'=>'امنیت-زیرساخت',
            'lead'=>'سطح حمله سرورها و سرویس‌ها را با تمرکز بر دسترسی، پیکربندی، پشتیبان‌گیری، لاگ و کاهش نقاط ضعف کنترل کنید.',
            'image'=>'cloud-security.jpg',
            'features'=>['Server Hardening','Firewall و SSH','SSL/TLS و سرویس‌های شبکه','مدیریت دسترسی و حساب‌ها','Backup و بازیابی','لاگ و مانیتورینگ'],
            'outputs'=>['گزارش وضعیت زیرساخت','فهرست ریسک‌های اولویت‌دار','چک‌لیست سخت‌سازی','پیشنهاد پایش و پشتیبان‌گیری'],
        ],
        'performance'=>[
            'title'=>'سرعت و بهینه‌سازی','slug'=>'سرعت-و-بهینه-سازی',
            'lead'=>'قبل از تغییرات پراکنده، عملکرد را اندازه‌گیری کنید و سپس زمان پاسخ، منابع، کش، تصاویر و تجربه کاربری را هدفمند بهبود دهید.',
            'image'=>'seo-performance.jpg',
            'features'=>['WordPress Optimization','Cache و CDN','بهینه‌سازی دیتابیس','Core Web Vitals','فشرده‌سازی و بهینه‌سازی تصاویر','تحلیل پاسخ سرور و منابع'],
            'outputs'=>['گزارش قبل و بعد','فهرست گلوگاه‌های عملکردی','اقدامات اولویت‌دار','راهنمای نگهداری سرعت'],
        ],
    ];
}

function dss_page_url($slug){
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : home_url('/'.$slug.'/');
}

function dss_render_form($service){
    ob_start(); ?>
    <form class="dss-form" method="post">
      <input type="hidden" name="dss_action" value="service_lead">
      <input type="hidden" name="dss_nonce" value="<?php echo esc_attr(wp_create_nonce('dss_service_lead')); ?>">
      <input type="hidden" name="service" value="<?php echo esc_attr($service['title']); ?>">
      <label>نام و نام خانوادگی<input required name="name" type="text"></label>
      <label>ایمیل یا شماره تماس<input required name="contact" type="text"></label>
      <label>شرح نیاز<textarea required name="message" rows="6" placeholder="نیاز، وضعیت فعلی و هدف پروژه را توضیح دهید..."></textarea></label>
      <button class="dsu-btn" type="submit">ارسال درخواست <?php echo esc_html($service['title']); ?> ←</button>
    </form>
    <?php return ob_get_clean();
}

function dss_service_shortcode($atts=[]){
    $key = sanitize_key($atts['service'] ?? 'security');
    $services = dss_services();
    if (!isset($services[$key])) $key='security';
    $s=$services[$key];
    ob_start(); ?>
    <div class="dsu dss-service-page" dir="rtl">
      <section class="dss-hero">
        <div class="dsu-wrap dss-hero-grid">
          <div>
            <div class="dsu-badge">DEJBANSEC · SERVICE</div>
            <h1><?php echo esc_html($s['title']); ?></h1>
            <p><?php echo esc_html($s['lead']); ?></p>
            <div class="dsu-actions">
              <a class="dsu-btn" href="#dss-request">درخواست این خدمت ←</a>
              <a class="dsu-btn ghost" href="<?php echo esc_url(home_url('/')); ?>#services">بازگشت به خدمات</a>
            </div>
          </div>
          <div class="dss-hero-media">
            <img src="<?php echo esc_url(function_exists('dsu_asset') ? dsu_asset($s['image']) : ''); ?>" alt="<?php echo esc_attr($s['title']); ?>">
            <div class="dss-floating"><b>راهکار متناسب با پروژه</b><small>بررسی نیاز → طراحی مسیر → اجرا → تحویل</small></div>
          </div>
        </div>
      </section>

      <section class="dsu-section">
        <div class="dsu-wrap">
          <div class="dsu-title"><small>چه چیزی دریافت می‌کنید؟</small><h2>خدمت را بر اساس نیاز واقعی پروژه اجرا می‌کنیم</h2></div>
          <div class="dss-grid">
          <?php foreach($s['features'] as $i=>$feature): ?>
            <article class="dss-card"><b><?php echo sprintf('%02d',$i+1); ?></b><h3><?php echo esc_html($feature); ?></h3><p>بررسی و اجرای این بخش متناسب با شرایط، فناوری‌ها و سطح دسترسی پروژه انجام می‌شود.</p></article>
          <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="dsu-section dsu-dark">
        <div class="dsu-wrap">
          <div class="dsu-title"><small>فرآیند اجرا</small><h2>مسیر همکاری شفاف و مرحله‌ای</h2></div>
          <div class="dss-process">
            <div><b>01</b><h3>شناخت</h3><p>هدف، وضعیت فعلی، محدودیت‌ها و نیاز اصلی بررسی می‌شود.</p></div>
            <div><b>02</b><h3>طراحی مسیر</h3><p>راهکار، محدوده کار و اولویت‌های فنی مشخص می‌شود.</p></div>
            <div><b>03</b><h3>اجرا</h3><p>پیاده‌سازی با کنترل کیفیت، تست و مستندسازی انجام می‌شود.</p></div>
            <div><b>04</b><h3>تحویل</h3><p>نتایج، مستندات و اقدامات بعدی به شما تحویل داده می‌شود.</p></div>
          </div>
        </div>
      </section>

      <section class="dsu-section">
        <div class="dsu-wrap dss-output-grid">
          <div>
            <div class="dsu-title"><small>خروجی پروژه</small><h2>خروجی قابل استفاده، نه فقط یک گزارش</h2></div>
            <ul class="dss-output"><?php foreach($s['outputs'] as $o): ?><li>✓ <?php echo esc_html($o); ?></li><?php endforeach; ?></ul>
          </div>
          <div class="dss-next">
            <small>گام بعدی</small><h3>برای شروع، وضعیت پروژه را برای ما بفرستید.</h3>
            <p>پس از دریافت اطلاعات، محدوده مناسب همکاری و مسیر فنی پیشنهادی مشخص می‌شود.</p>
            <a class="dsu-btn" href="#dss-request">شروع درخواست</a>
          </div>
        </div>
      </section>

      <section class="dsu-section dsu-dark" id="dss-request">
        <div class="dsu-wrap dss-request-grid">
          <div><small class="dsu-kicker">درخواست <?php echo esc_html($s['title']); ?></small><h2>از همین‌جا شروع کنیم</h2><p>اطلاعات اولیه را ارسال کنید. لازم نیست از ابتدا همه جزئیات فنی را بدانید.</p></div>
          <?php echo dss_render_form($s); ?>
        </div>
      </section>
    </div>
    <?php return ob_get_clean();
}

add_shortcode('dejbansec_service', 'dss_service_shortcode');

add_filter('the_content', function($content){
    if (!is_page()) return $content;
    $services=dss_services();
    foreach($services as $key=>$s){
        if (get_the_ID() === (int) get_page_by_path($s['slug'])?->ID) return dss_service_shortcode(['service'=>$key]);
    }
    return $content;
}, 20);

add_filter('the_content', function($content){
    if (!is_front_page()) return $content;
    foreach(dss_services() as $s){
        $url=esc_url(dss_page_url($s['slug']));
        $pattern='~(<article\b[^>]*>.*?<h3>\s*'.preg_quote($s['title'],'~').'.*?</article>)~su';
        $content=preg_replace_callback($pattern,function($m) use($url){
            $block=$m[1];
            $block=preg_replace('~href=["\']#contact["\']~','href="'.$url.'"',$block,1);
            return $block;
        },$content);
    }
    return $content;
}, 30);

add_action('init', function(){
    if (!isset($_POST['dss_action']) || $_POST['dss_action']!=='service_lead') return;
    if (!isset($_POST['dss_nonce']) || !wp_verify_nonce($_POST['dss_nonce'],'dss_service_lead')) return;
    $name=sanitize_text_field($_POST['name']??'');
    $contact=sanitize_text_field($_POST['contact']??'');
    $service=sanitize_text_field($_POST['service']??'');
    $msg=sanitize_textarea_field($_POST['message']??'');
    if(!$name || !$contact || !$msg) return;
    $id=wp_insert_post([
        'post_type'=>'dsu_lead','post_status'=>'private',
        'post_title'=>'درخواست '.$service.' | '.$name,
        'post_content'=>"خدمت: $service\nنام: $name\nتماس: $contact\n\nشرح:\n$msg"
    ]);
    $to=get_option('admin_email');
    if($to) wp_mail($to,'درخواست خدمت جدید از سایت دژبان‌سک',"خدمت: $service\nنام: $name\nتماس: $contact\n\n$msg");
    wp_safe_redirect(add_query_arg('dss_sent','1',wp_get_referer()?:home_url('/'))); exit;
});

function dss_seed_pages(){
    if(get_option('dss_seeded_v1')) return;
    foreach(dss_services() as $key=>$s){
        if(!get_page_by_path($s['slug'])){
            wp_insert_post([
                'post_type'=>'page','post_title'=>$s['title'],'post_name'=>$s['slug'],
                'post_status'=>'publish','post_content'=>'[dejbansec_service service="'.$key.'"]'
            ]);
        }
    }
    update_option('dss_seeded_v1',1);
}
register_activation_hook(__FILE__,'dss_seed_pages');
add_action('admin_init', function(){
    if(current_user_can('manage_options')) dss_seed_pages();
});
