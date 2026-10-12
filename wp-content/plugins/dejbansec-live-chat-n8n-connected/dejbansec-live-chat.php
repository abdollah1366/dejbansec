<?php
/**
 * Plugin Name: دژبان‌سک Live Chat
 * Description: ماژول چت زنده مشتری برای دژبان‌سک با REST API، ذخیره گفتگو و آماده اتصال به n8n.
 * Version: 1.1.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

define('DSLC_VER','1.1.0');
define('DSLC_URL',plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, function(){
    global $wpdb;
    $table = $wpdb->prefix.'dslc_messages';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(80) NOT NULL,
        visitor_name VARCHAR(190) DEFAULT '',
        visitor_contact VARCHAR(190) DEFAULT '',
        role VARCHAR(20) NOT NULL DEFAULT 'customer',
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY created_at (created_at)
    ) $charset;";
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    if (get_option('dslc_n8n_webhook','') === '') {
        update_option('dslc_n8n_webhook',''); /* n8n webhook URL removed from the repo copy; set it in the plugin settings page */
    }
});

add_action('wp_enqueue_scripts', function(){
    if (is_admin()) return;
    wp_enqueue_style('dslc', DSLC_URL.'assets/chat.css', [], DSLC_VER);
    wp_enqueue_script('dslc', DSLC_URL.'assets/chat.js', [], DSLC_VER, true);
    wp_localize_script('dslc','DSLC',[
        'rest'=>esc_url_raw(rest_url('dejbansec/v1/chat')),
        'nonce'=>wp_create_nonce('wp_rest')
    ]);
});

add_action('wp_footer', function(){
    if (is_admin()) return;
    if (did_action('dslc_widget_rendered')) return;
    do_action('dslc_widget_rendered');
    echo do_shortcode('[dejbansec_live_chat]');
}, 99);

add_shortcode('dejbansec_live_chat', function(){
    do_action('dslc_widget_rendered');
    ob_start(); ?>
    <div id="dslc-widget" dir="rtl">
      <button class="dslc-launch" type="button" aria-label="گفتگوی آنلاین">💬</button>
      <section class="dslc-panel" aria-label="گفتگوی آنلاین">
        <header class="dslc-head"><meta charset="utf-8">
          <div><strong>ارتباط با دژبان‌سک</strong><small>پاسخگویی آنلاین</small></div>
          <button class="dslc-close" type="button">×</button>
        </header>
        <div class="dslc-messages" aria-live="polite">
          <div class="dslc-msg bot">سلام 👋<br>چطور می‌توانیم کمکتان کنیم؟</div>
        </div>
        <form class="dslc-form">
          <div class="dslc-fields">
            <input name="name" placeholder="نام شما" autocomplete="name">
            <input name="contact" placeholder="شماره تماس یا ایمیل" autocomplete="email">
          </div>
          <textarea name="message" rows="2" required placeholder="پیام خود را بنویسید..."></textarea>
          <button type="submit">ارسال پیام <span>←</span></button>
        </form>
      </section>
    </div>
    <?php return ob_get_clean();
});

add_action('rest_api_init', function(){
    register_rest_route('dejbansec/v1','/chat',[
        [
            'methods'=>WP_REST_Server::CREATABLE,
            'permission_callback'=>'__return_true',
            'callback'=>'dslc_receive'
        ],
        [
            'methods'=>WP_REST_Server::READABLE,
            'permission_callback'=>'__return_true',
            'callback'=>'dslc_history'
        ]
    ]);
});

function dslc_receive(WP_REST_Request $r){
    global $wpdb;
    if (!is_string($r->get_body()) || strlen($r->get_body()) > 10000) {
        return new WP_Error('too_large','پیام بیش از حد مجاز است',['status'=>413]);
    }
    $table=$wpdb->prefix.'dslc_messages';
    $p=$r->get_json_params();
    $sid=sanitize_text_field($p['session_id']??'');
    $name=sanitize_text_field($p['name']??'');
    $contact=sanitize_text_field($p['contact']??'');
    $msg=sanitize_textarea_field($p['message']??'');
    if(!$sid || !$msg) return new WP_Error('invalid','اطلاعات پیام ناقص است',['status'=>400]);
    $wpdb->insert($table,[
        'session_id'=>$sid,'visitor_name'=>$name,'visitor_contact'=>$contact,
        'role'=>'customer','message'=>$msg,'status'=>'new','created_at'=>current_time('mysql')
    ]);
    $id=(int)$wpdb->insert_id;

    $webhook=get_option('dslc_n8n_webhook','');
    $payload=['message_id'=>$id,'session_id'=>$sid,'name'=>$name,'contact'=>$contact,'message'=>$msg,'site'=>home_url('/'),'event'=>'livechat.message.created','version'=>DSLC_VER];
    if($webhook){
        wp_remote_post($webhook,[
            'timeout'=>8,'blocking'=>true,
            'headers'=>['Content-Type'=>'application/json'],
            'body'=>wp_json_encode($payload)
        ]);
    }
    return ['ok'=>true,'message_id'=>$id,'queued'=>true];
}

function dslc_history(WP_REST_Request $r){
    global $wpdb;
    $sid=sanitize_text_field($r->get_param('session_id')??'');
    if(!$sid) return new WP_Error('invalid','session_id required',['status'=>400]);
    $rows=$wpdb->get_results($wpdb->prepare(
        "SELECT id,role,message,created_at FROM {$wpdb->prefix}dslc_messages WHERE session_id=%s ORDER BY id ASC LIMIT 100",$sid
    ),ARRAY_A);
    return ['messages'=>$rows];
}

add_action('admin_menu',function(){
    add_options_page('دژبان‌سک Live Chat','Live Chat دژبان‌سک','manage_options','dslc','dslc_settings');
});
add_action('admin_init',function(){
    register_setting('dslc_settings','dslc_n8n_webhook',['sanitize_callback'=>'esc_url_raw']);
});
function dslc_settings(){
    if(!current_user_can('manage_options')) return;
    ?>
    <div class="wrap" dir="rtl">
      <h1>Live Chat دژبان‌سک</h1>
      <p>ویجت چت مشتری فعال است. برای اتصال خودکار، Webhook مربوط به n8n را اینجا وارد کنید.</p>
      <form method="post" action="options.php">
        <?php settings_fields('dslc_settings'); ?>
        <table class="form-table">
          <tr><th>n8n Webhook URL</th><td>
            <input class="regular-text" type="url" name="dslc_n8n_webhook" value="<?php echo esc_attr(get_option('dslc_n8n_webhook','')); ?>">
            <p class="description">فعلاً خالی بگذارید؛ بعداً URL وبهوک n8n را اینجا قرار می‌دهیم.</p>
          </td></tr>
        </table>
        <?php submit_button('ذخیره تنظیمات'); ?>
      </form>
    </div>
    <?php
}
