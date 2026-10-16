<?php
/**
 * Plugin Name: دژبان‌سک — محافظ فرم‌ها و ایمیل
 * Description: ضد اسپم برای فرم «شروع همکاری» و فرم‌های خدمات (تله‌ی ربات، زمان‌سنج، محدودیت ارسال، فیلتر لینک)، ذخیره‌ی همه‌ی درخواست‌ها در پیشخوان، ارسال ایمیل از طریق SMTP و ثبت خطای ایمیل.
 * Version: 1.0.1
 * Author: DejbanSec
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) exit;

define('DFG_VERSION', '1.0.1');
define('DFG_MIN_SECONDS', 3);          // faster than this is a bot
define('DFG_MAX_AGE', DAY_IN_SECONDS); // same lifetime as the forms' nonces
define('DFG_RATE_SHORT', 3);           // per IP per 10 minutes
define('DFG_RATE_DAY', 10);            // per IP per day
define('DFG_MAX_LINKS', 2);
define('DFG_MAX_LENGTH', 5000);

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */

function dfg_settings() {
    $defaults = array(
        'notify_email' => '',
        'from_email'   => 'no-reply@' . preg_replace('/^www\./', '', (string) wp_parse_url(home_url(), PHP_URL_HOST)),
        'from_name'    => 'دژبان‌سک',
        'smtp_host'    => '',
        'smtp_port'    => 587,
        'smtp_secure'  => 'tls',
        'smtp_user'    => '',
        'smtp_pass'    => '',
    );
    $s = get_option('dfg_settings', array());
    $s = wp_parse_args(is_array($s) ? $s : array(), $defaults);
    if (defined('DEJBANSEC_SMTP_PASS')) $s['smtp_pass'] = DEJBANSEC_SMTP_PASS;
    return $s;
}

function dfg_notify_email() {
    $s = dfg_settings();
    return is_email($s['notify_email']) ? $s['notify_email'] : get_option('admin_email');
}

function dfg_sanitize_settings($in) {
    $old = dfg_settings();
    $in  = is_array($in) ? $in : array();
    $out = array(
        'notify_email' => sanitize_email($in['notify_email'] ?? $old['notify_email']),
        'from_email'   => sanitize_email($in['from_email'] ?? $old['from_email']),
        'from_name'    => sanitize_text_field($in['from_name'] ?? $old['from_name']),
        'smtp_host'    => sanitize_text_field($in['smtp_host'] ?? $old['smtp_host']),
        'smtp_port'    => absint($in['smtp_port'] ?? $old['smtp_port']) ?: 587,
        'smtp_secure'  => in_array($in['smtp_secure'] ?? $old['smtp_secure'], array('tls', 'ssl', ''), true) ? ($in['smtp_secure'] ?? $old['smtp_secure']) : 'tls',
        'smtp_user'    => sanitize_text_field($in['smtp_user'] ?? $old['smtp_user']),
        // An empty password field keeps the stored one, so the admin form never has to echo it back.
        'smtp_pass'    => ($in['smtp_pass'] ?? '') !== '' ? (string) $in['smtp_pass'] : $old['smtp_pass'],
    );
    return $out;
}

/* ------------------------------------------------------------------ *
 * Spam checks
 * ------------------------------------------------------------------ */

/** Which protected form is being submitted: 'home', 'service' or ''. */
function dfg_form_kind() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return '';
    if (($_POST['dsu_action'] ?? '') === 'lead') return 'home';
    if (($_POST['dss_action'] ?? '') === 'service_lead') return 'service';
    return '';
}

function dfg_token() {
    $ts = time();
    return $ts . '.' . substr(hash_hmac('sha256', (string) $ts, wp_salt('nonce')), 0, 20);
}

/** Age of a token in seconds, or -1 when it is missing or forged. */
function dfg_token_age($token) {
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{20})$/', (string) $token, $m)) return -1;
    $good = substr(hash_hmac('sha256', $m[1], wp_salt('nonce')), 0, 20);
    if (!hash_equals($good, $m[2])) return -1;
    return time() - (int) $m[1];
}

function dfg_ip_key() {
    return md5((string) ($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt('auth'));
}

function dfg_count($reason) {
    $stats = get_option('dfg_stats', array());
    $stats = is_array($stats) ? $stats : array();
    $stats[$reason] = (int) ($stats[$reason] ?? 0) + 1;
    $stats['last_' . $reason] = current_time('mysql');
    update_option('dfg_stats', $stats, false);
}

/** Return '' when the submission looks human, else the reason it was blocked. */
function dfg_check() {
    if (trim((string) ($_POST['dfg_hp'] ?? '')) !== '') return 'honeypot';

    $age = dfg_token_age(wp_unslash($_POST['dfg_t'] ?? ''));
    if ($age < 0) return 'no_js';
    if ($age < DFG_MIN_SECONDS) return 'too_fast';
    if ($age > DFG_MAX_AGE) return 'expired';

    $name = (string) wp_unslash($_POST['name'] ?? '');
    $msg  = (string) wp_unslash($_POST['message'] ?? '');
    if (preg_match('~https?://|www\.~i', $name)) return 'link_in_name';
    if (mb_strlen($msg) > DFG_MAX_LENGTH) return 'too_long';
    if (preg_match_all('~https?://|www\.~i', $msg) > DFG_MAX_LINKS) return 'too_many_links';

    $key   = dfg_ip_key();
    $short = (int) get_transient('dfg_rs_' . $key);
    $day   = (int) get_transient('dfg_rd_' . $key);
    if ($short >= DFG_RATE_SHORT || $day >= DFG_RATE_DAY) return 'rate_limit';
    set_transient('dfg_rs_' . $key, $short + 1, 10 * MINUTE_IN_SECONDS);
    set_transient('dfg_rd_' . $key, $day + 1, DAY_IN_SECONDS);
    return '';
}

/** Reasons that only a bot triggers: pretend it worked so the bot learns nothing. */
function dfg_is_silent($reason) {
    return in_array($reason, array('honeypot', 'too_fast', 'link_in_name'), true);
}

/**
 * The page the form was posted from. The forms post to their own URL, so
 * wp_get_referer() returns false there (it equals the request URI) and the
 * original handlers fell back to the home page.
 */
function dfg_current_url() {
    $uri   = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/');
    $path  = '/' . ltrim((string) wp_parse_url($uri, PHP_URL_PATH), '/');
    $query = (string) wp_parse_url($uri, PHP_URL_QUERY);
    $home  = wp_parse_url(home_url());
    $origin = $home['scheme'] . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '');
    return $origin . $path . ($query === '' ? '' : '?' . $query);
}

function dfg_back_url($kind, $args) {
    $back = remove_query_arg(array('dsu_sent', 'dss_sent', 'dfg_err'), dfg_current_url());
    return add_query_arg($args, $back) . ($kind === 'home' ? '#contact' : '');
}

/** Send visitors back to the service page they used, where the success notice is shown. */
add_filter('wp_redirect', function ($location) {
    if (dfg_form_kind() !== 'service') return $location;
    $query = (string) wp_parse_url($location, PHP_URL_QUERY);
    parse_str($query, $q);
    if (empty($q['dss_sent'])) return $location;
    return dfg_back_url('service', array('dss_sent' => '1'));
});

// Priority 1: before the live-chat bridge (5) and the original handlers (10).
add_action('init', function () {
    $kind = dfg_form_kind();
    if (!$kind) return;

    $reason = dfg_check();
    if ($reason === '') return;

    dfg_count($reason);
    $args = dfg_is_silent($reason)
        ? array($kind === 'home' ? 'dsu_sent' : 'dss_sent' => '1')
        : array('dfg_err' => $reason);
    wp_safe_redirect(dfg_back_url($kind, $args));
    exit;
}, 1);

/* ------------------------------------------------------------------ *
 * Keep every request: the home form goes to Live Chat, so also save a
 * copy in «درخواست‌ها» and email it, in case the n8n notice never lands.
 * ------------------------------------------------------------------ */

add_action('init', function () {
    if (dfg_form_kind() !== 'home') return;
    if (!isset($_POST['dsu_nonce']) || !wp_verify_nonce($_POST['dsu_nonce'], 'dsu_lead')) return;

    global $wpdb;
    $vis = $wpdb->prefix . 'dslc_visitors';
    // Without Live Chat the original dsu_lead handler stores and emails it already.
    if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $vis))) return;

    $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    $type    = sanitize_text_field(wp_unslash($_POST['project_type'] ?? ''));
    $msg     = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!$name || !$contact || !$msg) return;

    $body = "نام: $name\nتماس: $contact\nنوع: $type\n\nشرح:\n$msg";
    wp_insert_post(array(
        'post_type'    => 'dsu_lead',
        'post_status'  => 'private',
        'post_title'   => 'درخواست: ' . $name . ' | ' . $type,
        'post_content' => $body,
    ));
    wp_mail(dfg_notify_email(), 'درخواست پروژه جدید از سایت دژبان‌سک', $body, dfg_reply_to($contact));
}, 4);

function dfg_reply_to($contact) {
    return is_email($contact) ? array('Reply-To: ' . $contact) : array();
}

/** Route the services form's admin_email notice to the chosen address and add Reply-To. */
add_filter('wp_mail', function ($atts) {
    if (dfg_form_kind() !== 'service') return $atts;
    if (strpos((string) $atts['subject'], 'درخواست خدمت جدید') !== 0) return $atts;
    $atts['to'] = dfg_notify_email();
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    $headers = (array) ($atts['headers'] ?: array());
    $atts['headers'] = array_merge($headers, dfg_reply_to($contact));
    return $atts;
});

/* ------------------------------------------------------------------ *
 * Email delivery
 * ------------------------------------------------------------------ */

add_filter('wp_mail_from', function ($from) {
    $s = dfg_settings();
    return is_email($s['from_email']) ? $s['from_email'] : $from;
});
add_filter('wp_mail_from_name', function ($name) {
    $s = dfg_settings();
    return $s['from_name'] !== '' ? $s['from_name'] : $name;
});

add_action('phpmailer_init', function ($mailer) {
    $s = dfg_settings();
    if ($s['smtp_host'] === '') return;
    $mailer->isSMTP();
    $mailer->Host       = $s['smtp_host'];
    $mailer->Port       = (int) $s['smtp_port'];
    $mailer->SMTPSecure = $s['smtp_secure'];
    $mailer->SMTPAuth   = $s['smtp_user'] !== '';
    $mailer->Username   = $s['smtp_user'];
    $mailer->Password   = $s['smtp_pass'];
    $mailer->Timeout    = 15;
    $mailer->CharSet    = 'UTF-8';
    // Envelope sender must match the From domain for SPF to pass.
    $mailer->Sender     = $mailer->From;
});

add_action('wp_mail_failed', function ($error) {
    update_option('dfg_mail_error', array(
        'time'    => current_time('mysql'),
        'message' => $error->get_error_message(),
    ), false);
});

add_action('wp_mail_succeeded', function () {
    if (get_option('dfg_mail_error')) delete_option('dfg_mail_error');
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    $err = get_option('dfg_mail_error');
    if (!$err) return;
    printf(
        '<div class="notice notice-error"><p><strong>ارسال ایمیل سایت ناموفق بود</strong> (%s): %s — <a href="%s">بررسی تنظیمات ایمیل</a></p></div>',
        esc_html($err['time']),
        esc_html($err['message']),
        esc_url(admin_url('options-general.php?page=dfg-settings'))
    );
});

/* ------------------------------------------------------------------ *
 * Front end: token, honeypot and error notice
 * ------------------------------------------------------------------ */

add_action('wp_footer', function () {
    if (is_admin()) return;
    $messages = array(
        'no_js'          => 'برای ارسال فرم، جاوااسکریپت مرورگر باید فعال باشد. اگر مشکل ادامه داشت، درخواستتان را به ایمیل ما بفرستید.',
        'expired'        => 'صفحه مدت زیادی باز مانده بود. لطفاً صفحه را تازه کنید و دوباره ارسال کنید.',
        'too_long'       => 'متن درخواست طولانی است. لطفاً آن را کوتاه‌تر کنید (حداکثر ۵۰۰۰ نویسه).',
        'too_many_links' => 'لطفاً حداکثر دو لینک در متن درخواست بگذارید.',
        'rate_limit'     => 'تعداد درخواست‌های ارسالی از این اتصال زیاد است. لطفاً چند دقیقه‌ی دیگر دوباره تلاش کنید.',
    );
    $err = sanitize_key($_GET['dfg_err'] ?? '');
    $note = $messages[$err] ?? '';
    ?>
<script id="dfg-guard">
(function(){
  var t=<?php echo wp_json_encode(dfg_token()); ?>,note=<?php echo wp_json_encode($note); ?>;
  function arm(){
    var forms=document.querySelectorAll('form.dsu-form,form.dss-form');
    for(var i=0;i<forms.length;i++){
      var f=forms[i];
      if(f.querySelector('input[name="dfg_t"]'))continue;
      var tk=document.createElement('input');tk.type='hidden';tk.name='dfg_t';tk.value=t;f.appendChild(tk);
      var w=document.createElement('div');w.setAttribute('aria-hidden','true');
      w.style.cssText='position:absolute!important;left:-10000px!important;top:auto;width:1px;height:1px;overflow:hidden';
      w.innerHTML='<label>وب‌سایت<input type="text" name="dfg_hp" tabindex="-1" autocomplete="off"></label>';
      f.appendChild(w);
    }
    if(note&&forms.length){
      var n=document.createElement('div');n.setAttribute('role','alert');
      n.style.cssText='margin:0 0 16px;padding:12px 15px;border:1px solid #f5b5a8;border-radius:12px;background:#fff4f1;color:#7a2a17;line-height:1.8';
      n.textContent=note;forms[0].parentNode.insertBefore(n,forms[0]);
    }
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',arm);else arm();
})();
</script>
<?php
}, 999);

/** Success notice for the services form, which had none. */
add_action('wp_footer', function () {
    if (is_admin() || !isset($_GET['dss_sent'])) return;
    ?>
<script>
(function(){
  function show(){
    var f=document.querySelector('form.dss-form');if(!f)return;
    var n=document.createElement('div');n.setAttribute('role','status');
    n.style.cssText='margin:0 0 16px;padding:12px 15px;border:1px solid #9fd3de;border-radius:12px;background:#e0f2f1;color:#0f3d46;line-height:1.8';
    n.textContent='درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.';
    f.parentNode.insertBefore(n,f);f.scrollIntoView({block:'center'});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',show);else show();
})();
</script>
<?php
}, 1002);

/* ------------------------------------------------------------------ *
 * Admin: request inbox and settings
 * ------------------------------------------------------------------ */

add_action('admin_menu', function () {
    add_menu_page('درخواست‌های سایت', 'درخواست‌ها', 'edit_posts', 'dfg-leads', 'dfg_leads_page', 'dashicons-email-alt', 26);
    add_options_page('ایمیل و فرم‌ها', 'ایمیل و فرم‌ها', 'manage_options', 'dfg-settings', 'dfg_settings_page');
});

add_action('admin_init', function () {
    register_setting('dfg_settings', 'dfg_settings', array('sanitize_callback' => 'dfg_sanitize_settings'));
});

function dfg_leads_page() {
    if (!current_user_can('edit_posts')) return;
    $leads = get_posts(array('post_type' => 'dsu_lead', 'post_status' => 'any', 'numberposts' => 100));
    echo '<div class="wrap" dir="rtl"><h1>درخواست‌های سایت</h1>';
    echo '<p>همه‌ی درخواست‌های فرم «شروع همکاری» و فرم‌های خدمات، جدیدترین در بالا. درخواست‌های صفحه‌ی اصلی در «گفتگوی آنلاین» هم ثبت می‌شوند.</p>';
    if (!$leads) { echo '<p>هنوز درخواستی ثبت نشده.</p></div>'; return; }
    echo '<table class="widefat striped"><thead><tr><th style="width:150px">تاریخ</th><th style="width:260px">عنوان</th><th>متن</th></tr></thead><tbody>';
    foreach ($leads as $p) {
        printf(
            '<tr><td>%s</td><td><strong>%s</strong></td><td style="white-space:pre-wrap">%s</td></tr>',
            esc_html(get_the_date('Y/m/d H:i', $p)),
            esc_html($p->post_title),
            esc_html($p->post_content)
        );
    }
    echo '</tbody></table></div>';
}

function dfg_settings_page() {
    if (!current_user_can('manage_options')) return;
    $s = dfg_settings();
    $test = null;
    if (isset($_POST['dfg_test']) && check_admin_referer('dfg_test')) $test = dfg_send_test();
    ?>
<div class="wrap" dir="rtl"><h1>ایمیل و فرم‌ها</h1>
<?php if ($test) printf('<div class="notice notice-%s"><p>%s</p></div>', $test['ok'] ? 'success' : 'error', esc_html($test['message'])); ?>
<form method="post" action="options.php"><?php settings_fields('dfg_settings'); ?>
<h2>اعلان درخواست‌ها</h2>
<table class="form-table">
<tr><th>ایمیل دریافت درخواست‌ها</th><td><input class="regular-text" dir="ltr" name="dfg_settings[notify_email]" value="<?php echo esc_attr($s['notify_email']); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>"><p class="description">خالی = ایمیل مدیر سایت</p></td></tr>
<tr><th>فرستنده (ایمیل)</th><td><input class="regular-text" dir="ltr" name="dfg_settings[from_email]" value="<?php echo esc_attr($s['from_email']); ?>"><p class="description">باید از دامنه‌ی خود سایت باشد تا ایمیل به اسپم نرود.</p></td></tr>
<tr><th>فرستنده (نام)</th><td><input class="regular-text" name="dfg_settings[from_name]" value="<?php echo esc_attr($s['from_name']); ?>"></td></tr>
</table>
<h2>SMTP</h2>
<p>اگر «سرور» خالی باشد، ایمیل با تابع mail هاست فرستاده می‌شود.</p>
<table class="form-table">
<tr><th>سرور</th><td><input class="regular-text" dir="ltr" name="dfg_settings[smtp_host]" value="<?php echo esc_attr($s['smtp_host']); ?>" placeholder="mail.dejbansec.ir"></td></tr>
<tr><th>پورت</th><td><input class="small-text" dir="ltr" name="dfg_settings[smtp_port]" value="<?php echo esc_attr($s['smtp_port']); ?>"></td></tr>
<tr><th>رمزنگاری</th><td><select name="dfg_settings[smtp_secure]"><?php foreach (array('tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', '' => 'بدون رمزنگاری') as $k => $label) printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($s['smtp_secure'], $k, false), esc_html($label)); ?></select></td></tr>
<tr><th>نام کاربری</th><td><input class="regular-text" dir="ltr" name="dfg_settings[smtp_user]" value="<?php echo esc_attr($s['smtp_user']); ?>" autocomplete="off"></td></tr>
<tr><th>رمز</th><td><input class="regular-text" dir="ltr" type="password" name="dfg_settings[smtp_pass]" value="" autocomplete="new-password" placeholder="<?php echo $s['smtp_pass'] !== '' ? 'ذخیره شده؛ برای تغییر وارد کنید' : ''; ?>"></td></tr>
</table>
<?php submit_button('ذخیره'); ?></form>
<form method="post"><?php wp_nonce_field('dfg_test'); ?><input type="hidden" name="dfg_test" value="1"><?php submit_button('ارسال ایمیل آزمایشی به ' . dfg_notify_email(), 'secondary'); ?></form>
<h2>فرم‌های مسدودشده</h2>
<?php $stats = get_option('dfg_stats', array()); ?>
<?php if (!$stats) echo '<p>هنوز چیزی مسدود نشده.</p>'; else { echo '<ul>'; foreach ($stats as $k => $v) if (strpos($k, 'last_') !== 0) printf('<li><code>%s</code>: %d (آخرین: %s)</li>', esc_html($k), (int) $v, esc_html($stats['last_' . $k] ?? '')); echo '</ul>'; } ?>
</div>
<?php
}

function dfg_send_test() {
    delete_option('dfg_mail_error');
    $to = dfg_notify_email();
    $ok = wp_mail($to, 'ایمیل آزمایشی دژبان‌سک', "این ایمیل برای آزمایش تنظیمات ایمیل سایت فرستاده شده است.\n" . home_url('/') . "\n" . current_time('mysql'));
    $err = get_option('dfg_mail_error');
    return array(
        'ok'      => (bool) $ok,
        'to'      => $to,
        'message' => $ok ? 'ایمیل آزمایشی به ' . $to . ' فرستاده شد. صندوق ورودی و پوشه‌ی اسپم را ببینید.' : 'ارسال ناموفق: ' . ($err['message'] ?? 'خطای نامشخص'),
    );
}

/* ------------------------------------------------------------------ *
 * REST (administrators only): status, settings and a test email
 * ------------------------------------------------------------------ */

add_action('rest_api_init', function () {
    $admin = function () { return current_user_can('manage_options'); };
    register_rest_route('dejbansec/v1', '/form-guard/status', array(
        'methods' => WP_REST_Server::READABLE, 'permission_callback' => $admin,
        'callback' => function () {
            $s = dfg_settings();
            $s['smtp_pass'] = $s['smtp_pass'] !== '' ? 'set' : '';
            $counts = wp_count_posts('dsu_lead');
            return array(
                'version'      => DFG_VERSION,
                'settings'     => $s,
                'notify_email' => dfg_notify_email(),
                'mail_error'   => get_option('dfg_mail_error') ?: null,
                'blocked'      => get_option('dfg_stats') ?: new stdClass(),
                'leads'        => (int) ($counts->private ?? 0),
            );
        },
    ));
    register_rest_route('dejbansec/v1', '/form-guard/settings', array(
        'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => $admin,
        'callback' => function (WP_REST_Request $r) {
            update_option('dfg_settings', dfg_sanitize_settings($r->get_json_params()));
            $s = dfg_settings();
            $s['smtp_pass'] = $s['smtp_pass'] !== '' ? 'set' : '';
            return $s;
        },
    ));
    register_rest_route('dejbansec/v1', '/form-guard/test-mail', array(
        'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => $admin,
        'callback' => 'dfg_send_test',
    ));
});
