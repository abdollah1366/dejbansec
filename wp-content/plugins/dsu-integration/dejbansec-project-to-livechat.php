<?php
/**
 * Plugin Name: دژبان‌سک — انتقال درخواست پروژه به Live Chat
 * Description: فرم «شروع همکاری» را به پرونده و مکالمه Live Chat متصل می‌کند و منوی قدیمی «درخواست‌های پروژه» را از مدیریت مخفی می‌کند.
 * Version: 1.0.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

/** Hide the old dsu_lead management screen without deleting existing records. */
add_filter('register_post_type_args', function($args, $post_type){
    if ($post_type !== 'dsu_lead') return $args;
    $args['show_ui'] = false;
    $args['show_in_menu'] = false;
    $args['show_in_admin_bar'] = false;
    return $args;
}, 20, 2);

/**
 * Capture the existing «شروع همکاری» form before the old dsu_lead handler runs.
 * The submitted request becomes the first customer message in Live Chat.
 */
add_action('init', function(){
    if (($_POST['dsu_action'] ?? '') !== 'lead') return;
    if (!isset($_POST['dsu_nonce']) || !wp_verify_nonce($_POST['dsu_nonce'], 'dsu_lead')) return;

    $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    $type    = sanitize_text_field(wp_unslash($_POST['project_type'] ?? ''));
    $body    = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!$name || !$contact || !$body) return;

    global $wpdb;
    $vis = $wpdb->prefix . 'dslc_visitors';
    $msg = $wpdb->prefix . 'dslc_messages';

    // If Live Chat Pro is not installed/active, leave the original handler in charge.
    if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $vis)) ||
        !$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $msg))) return;

    $token = sanitize_text_field(wp_unslash($_POST['dslc_visitor_token'] ?? ''));
    $cid   = sanitize_text_field(wp_unslash($_POST['dslc_conversation_id'] ?? ''));
    if (!$token) $token = 'v_' . wp_generate_uuid4();
    if (!$cid)   $cid   = 'c_' . wp_generate_uuid4();

    $now = current_time('mysql');
    $visitor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $vis WHERE visitor_token=%s", $token), ARRAY_A);

    if ($visitor) {
        $visitor_id = (int) $visitor['id'];
        $wpdb->update($vis, [
            'name' => $name,
            'contact' => $contact,
            'last_seen' => $now,
        ], ['id' => $visitor_id]);
    } else {
        $wpdb->insert($vis, [
            'visitor_token' => $token,
            'name' => $name,
            'contact' => $contact,
            'first_seen' => $now,
            'last_seen' => $now,
        ]);
        $visitor_id = (int) $wpdb->insert_id;
    }

    $text = "درخواست شروع همکاری\nنوع پروژه: {$type}\n\n{$body}";
    $wpdb->insert($msg, [
        'visitor_id' => $visitor_id,
        'conversation_id' => $cid,
        'role' => 'customer',
        'message' => $text,
        'status' => 'new',
        'created_at' => $now,
    ]);
    $message_id = (int) $wpdb->insert_id;

    // Reuse the Live Chat Pro → n8n notification pipeline when available.
    if (function_exists('dslc_notify_n8n')) {
        dslc_notify_n8n($message_id, $visitor_id, $cid, $name, $contact, $text);
    }

    $back = wp_get_referer() ?: home_url('/');
    wp_safe_redirect(add_query_arg('dsu_sent', '1', $back) . '#contact');
    exit;
}, 5);

/** Connect the form to the same browser identity used by Live Chat. */
add_action('wp_footer', function(){
    if (is_admin()) return;
    ?>
<script>
(function(){
  function connectProjectForm(){
    var form=document.querySelector('form.dsu-form');
    if(!form) return;
    var token=localStorage.getItem('dslc_visitor_token')||'';
    var cid=localStorage.getItem('dslc_conversation')||'';
    function hidden(name,value){
      var el=form.querySelector('input[name="'+name+'"]');
      if(!el){el=document.createElement('input');el.type='hidden';el.name=name;form.appendChild(el);}
      el.value=value;
    }
    hidden('dslc_visitor_token',token);
    hidden('dslc_conversation_id',cid);

    // Make the relationship clear without changing the existing design.
    var btn=form.querySelector('button[type="submit"]');
    if(btn) btn.textContent='ارسال درخواست و شروع گفتگو ←';
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',connectProjectForm);
  else connectProjectForm();
})();
</script>
<?php
}, 1000);

/** Friendly success notice on the existing contact section. */
add_action('wp_footer', function(){
    if (is_admin() || !isset($_GET['dsu_sent'])) return;
    ?>
<script>
(function(){
  var form=document.querySelector('form.dsu-form');
  if(!form) return;
  var note=document.createElement('div');
  note.style.cssText='margin:0 0 16px;padding:12px 15px;border:1px solid rgba(39,224,194,.35);border-radius:12px;background:rgba(39,224,194,.08);color:inherit;line-height:1.8;';
  note.textContent='درخواست شما ثبت شد و به گفتگوی آنلاین منتقل شد. از همین‌جا می‌توانید ادامه گفتگو را انجام دهید.';
  form.parentNode.insertBefore(note,form);
})();
</script>
<?php
}, 1001);
