<?php
/**
 * Plugin Name: دژبان‌سک Live Chat Pro
 * Description: سیستم کامل ارتباط با مشتری دژبان‌سک؛ ذخیره مراجع و مکالمات، پنل اپراتور، پاسخ زنده و اتصال دوطرفه به n8n.
 * Version: 2.1.0
 * Author: DejbanSec
 */
if (!defined('ABSPATH')) exit;

define('DSLC_VER','2.1.0');
define('DSLC_URL',plugin_dir_url(__FILE__));

function dslc_tables(){ global $wpdb; return [$wpdb->prefix.'dslc_visitors',$wpdb->prefix.'dslc_messages']; }

function dslc_install(){
    global $wpdb;
    [$vis,$msg]=dslc_tables(); $charset=$wpdb->get_charset_collate();
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE $vis (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_token VARCHAR(100) NOT NULL,
      name VARCHAR(190) DEFAULT '',
      contact VARCHAR(190) DEFAULT '',
      first_seen DATETIME NOT NULL,
      last_seen DATETIME NOT NULL,
      PRIMARY KEY(id), UNIQUE KEY visitor_token(visitor_token), KEY contact(contact), KEY last_seen(last_seen)
    ) $charset;");
    dbDelta("CREATE TABLE $msg (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NOT NULL,
      conversation_id VARCHAR(100) NOT NULL,
      role VARCHAR(20) NOT NULL DEFAULT 'customer',
      message TEXT NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'new',
      created_at DATETIME NOT NULL,
      PRIMARY KEY(id), KEY visitor_id(visitor_id), KEY conversation_id(conversation_id), KEY status(status), KEY created_at(created_at)
    ) $charset;");
    update_option('dslc_db_version', DSLC_VER);
}
register_activation_hook(__FILE__, 'dslc_install');
add_action('plugins_loaded', function(){ if(get_option('dslc_db_version')!==DSLC_VER) dslc_install(); });

add_action('wp_enqueue_scripts',function(){
    if(is_admin()) return;
    wp_enqueue_style('dslc',DSLC_URL.'assets/chat.css',[],DSLC_VER);
    wp_enqueue_script('dslc',DSLC_URL.'assets/chat.js',[],DSLC_VER,true);
    wp_localize_script('dslc','DSLC',['rest'=>esc_url_raw(rest_url('dejbansec/v3/chat')),'nonce'=>wp_create_nonce('wp_rest')]);
});
add_action('wp_footer',function(){ if(!is_admin()) echo do_shortcode('[dejbansec_live_chat]'); },99);

add_shortcode('dejbansec_live_chat',function(){ ob_start(); ?>
<div id="dslc-widget" dir="rtl">
 <button class="dslc-launch" type="button" aria-label="گفتگوی آنلاین"><span>💬</span><i class="dslc-unread-badge" hidden>0</i></button>
 <section class="dslc-panel" aria-label="گفتگوی آنلاین">
  <header class="dslc-head"><meta charset="utf-8"><div><strong>ارتباط با دژبان‌سک</strong><small>پشتیبانی آنلاین</small></div><button class="dslc-close" type="button">×</button></header>
  <div class="dslc-historybar"><span class="dslc-status-dot"></span><span>گفتگوی شما ذخیره می‌شود و در مراجعه بعدی قابل ادامه است.</span></div>
  <div class="dslc-messages" aria-live="polite"></div>
  <form class="dslc-form">
   <div class="dslc-fields"><input name="name" placeholder="نام و نام خانوادگی" autocomplete="name"><input name="contact" placeholder="شماره تماس یا ایمیل" autocomplete="email"></div>
   <textarea name="message" rows="2" required placeholder="پیام خود را بنویسید..."></textarea>
   <button type="submit">ارسال پیام <span>←</span></button>
  </form>
 </section>
</div><?php return ob_get_clean(); });

add_action('rest_api_init',function(){
 register_rest_route('dejbansec/v3','/chat',[
   ['methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>'__return_true','callback'=>'dslc_receive'],
   ['methods'=>WP_REST_Server::READABLE,'permission_callback'=>'__return_true','callback'=>'dslc_history']
 ]);
 register_rest_route('dejbansec/v3','/conversations',['methods'=>WP_REST_Server::READABLE,'permission_callback'=>'dslc_admin_permission','callback'=>'dslc_admin_conversations']);
 register_rest_route('dejbansec/v3','/conversation',['methods'=>WP_REST_Server::READABLE,'permission_callback'=>'dslc_admin_permission','callback'=>'dslc_admin_conversation']);
 register_rest_route('dejbansec/v3','/mark-read',['methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>'dslc_admin_permission','callback'=>'dslc_mark_read']);
 register_rest_route('dejbansec/v3','/admin-reply',['methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>'dslc_admin_permission','callback'=>'dslc_admin_reply']);
 register_rest_route('dejbansec/v3','/reply',['methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>'dslc_reply_permission','callback'=>'dslc_n8n_reply']);
});
function dslc_admin_permission(){ return current_user_can('manage_options'); }
function dslc_reply_permission($r){ $secret=get_option('dslc_n8n_secret',''); $got=$r->get_header('x-dslc-secret'); return $secret!=='' && hash_equals($secret,(string)$got); }

function dslc_get_visitor($token,$name='',$contact=''){
 global $wpdb; [$vis,$msg]=dslc_tables();
 $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $vis WHERE visitor_token=%s",$token),ARRAY_A);
 if($row){
   $data=['last_seen'=>current_time('mysql')];
   if($name!=='') $data['name']=$name;
   if($contact!=='') $data['contact']=$contact;
   $wpdb->update($vis,$data,['id'=>$row['id']]);
   return (int)$row['id'];
 }
 $wpdb->insert($vis,['visitor_token'=>$token,'name'=>$name,'contact'=>$contact,'first_seen'=>current_time('mysql'),'last_seen'=>current_time('mysql')]);
 return (int)$wpdb->insert_id;
}
function dslc_receive(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables();
 if(strlen($r->get_body())>12000) return new WP_Error('too_large','پیام بیش از حد مجاز است',['status'=>413]);
 $p=$r->get_json_params();
 $token=sanitize_text_field($p['visitor_token']??''); $cid=sanitize_text_field($p['conversation_id']??'');
 $name=sanitize_text_field($p['name']??''); $contact=sanitize_text_field($p['contact']??''); $text=sanitize_textarea_field($p['message']??'');
 if(!$token||!$cid||!$text) return new WP_Error('invalid','اطلاعات پیام ناقص است',['status'=>400]);
 $vid=dslc_get_visitor($token,$name,$contact);
 $wpdb->insert($msg,['visitor_id'=>$vid,'conversation_id'=>$cid,'role'=>'customer','message'=>$text,'status'=>'new','created_at'=>current_time('mysql')]);
 $id=(int)$wpdb->insert_id;
 dslc_notify_n8n($id,$vid,$cid,$name,$contact,$text);
 return ['ok'=>true,'message_id'=>$id,'visitor_id'=>$vid,'conversation_id'=>$cid];
}
function dslc_notify_n8n($id,$vid,$cid,$name,$contact,$text){
 $webhook=get_option('dslc_n8n_webhook',''); if(!$webhook) return;
 $payload=['event'=>'customer_message','message_id'=>$id,'visitor_id'=>$vid,'conversation_id'=>$cid,'name'=>$name,'contact'=>$contact,'message'=>$text,'site'=>home_url('/'),'reply_endpoint'=>rest_url('dejbansec/v3/reply')];
 $secret=get_option('dslc_n8n_secret','');
 wp_remote_post($webhook,['timeout'=>8,'blocking'=>false,'headers'=>['Content-Type'=>'application/json','X-DSLC-Secret'=>$secret],'body'=>wp_json_encode($payload)]);
}
function dslc_history(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables();
 $token=sanitize_text_field($r->get_param('visitor_token')??''); $cid=sanitize_text_field($r->get_param('conversation_id')??'');
 if(!$token) return new WP_Error('invalid','visitor_token required',['status'=>400]);
 $v=$wpdb->get_row($wpdb->prepare("SELECT * FROM $vis WHERE visitor_token=%s",$token),ARRAY_A);
 if(!$v) return ['visitor'=>null,'messages'=>[],'conversations'=>[],'unread'=>0];
 $where=$cid?$wpdb->prepare(' AND conversation_id=%s',$cid):'';
 $rows=$wpdb->get_results($wpdb->prepare("SELECT id,conversation_id,role,message,status,created_at FROM $msg WHERE visitor_id=%d $where ORDER BY id ASC LIMIT 500",(int)$v['id']),ARRAY_A);
 $convs=$wpdb->get_results($wpdb->prepare("SELECT conversation_id,MIN(created_at) started_at,MAX(created_at) last_at,COUNT(*) message_count,SUM(CASE WHEN role='customer' AND status='new' THEN 1 ELSE 0 END) unread FROM $msg WHERE visitor_id=%d GROUP BY conversation_id ORDER BY last_at DESC LIMIT 50",(int)$v['id']),ARRAY_A);
 return ['visitor'=>['id'=>(int)$v['id'],'name'=>$v['name'],'contact'=>$v['contact']],'messages'=>$rows,'conversations'=>$convs,'unread'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $msg WHERE visitor_id=%d AND role IN ('agent','bot') AND status='new'",(int)$v['id']))];
}
function dslc_admin_conversations(){
 global $wpdb; [$vis,$msg]=dslc_tables();
 return $wpdb->get_results("SELECT v.id,v.visitor_token,v.name,v.contact,v.first_seen,v.last_seen,
 COUNT(m.id) message_count,MAX(m.created_at) last_message,
 SUM(CASE WHEN m.role='customer' AND m.status='new' THEN 1 ELSE 0 END) unread,
 (SELECT m2.message FROM $msg m2 WHERE m2.id=(SELECT MAX(m3.id) FROM $msg m3 WHERE m3.visitor_id=v.id)) last_text
 FROM $vis v LEFT JOIN $msg m ON m.visitor_id=v.id
 GROUP BY v.id ORDER BY (SUM(CASE WHEN m.role='customer' AND m.status='new' THEN 1 ELSE 0 END)>0) DESC,last_message DESC,last_seen DESC LIMIT 300",ARRAY_A);
}
function dslc_admin_conversation(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables(); $vid=absint($r->get_param('visitor_id')??0); $cid=sanitize_text_field($r->get_param('conversation_id')??'');
 if(!$vid)return new WP_Error('invalid','visitor_id الزامی است',['status'=>400]);
 $v=$wpdb->get_row($wpdb->prepare("SELECT * FROM $vis WHERE id=%d",$vid),ARRAY_A); if(!$v)return new WP_Error('not_found','مراجع پیدا نشد',['status'=>404]);
 $where=$cid?$wpdb->prepare(' AND conversation_id=%s',$cid):'';
 $messages=$wpdb->get_results($wpdb->prepare("SELECT id,conversation_id,role,message,status,created_at FROM $msg WHERE visitor_id=%d $where ORDER BY id ASC LIMIT 500",$vid),ARRAY_A);
 $convs=$wpdb->get_results($wpdb->prepare("SELECT conversation_id,MIN(created_at) started_at,MAX(created_at) last_at,COUNT(*) message_count,SUM(CASE WHEN role='customer' AND status='new' THEN 1 ELSE 0 END) unread FROM $msg WHERE visitor_id=%d GROUP BY conversation_id ORDER BY last_at DESC",$vid),ARRAY_A);
 return ['visitor'=>['id'=>(int)$v['id'],'token'=>$v['visitor_token'],'name'=>$v['name'],'contact'=>$v['contact'],'first_seen'=>$v['first_seen'],'last_seen'=>$v['last_seen']],'messages'=>$messages,'conversations'=>$convs];
}
function dslc_mark_read(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables(); $vid=absint($r->get_param('visitor_id')??0); $cid=sanitize_text_field($r->get_param('conversation_id')??'');
 if(!$vid)return new WP_Error('invalid','visitor_id الزامی است',['status'=>400]);
 $where=$cid?$wpdb->prepare(' AND conversation_id=%s',$cid):'';
 $wpdb->query($wpdb->prepare("UPDATE $msg SET status='read' WHERE visitor_id=%d AND role='customer' AND status='new' $where",$vid));
 return ['ok'=>true];
}
function dslc_admin_reply(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables(); $p=$r->get_json_params(); $vid=absint($p['visitor_id']??0); $cid=sanitize_text_field($p['conversation_id']??''); $text=sanitize_textarea_field($p['message']??'');
 if(!$vid||!$cid||!$text)return new WP_Error('invalid','اطلاعات پاسخ ناقص است',['status'=>400]);
 $wpdb->insert($msg,['visitor_id'=>$vid,'conversation_id'=>$cid,'role'=>'agent','message'=>$text,'status'=>'new','created_at'=>current_time('mysql')]);
 return ['ok'=>true,'message_id'=>(int)$wpdb->insert_id];
}
function dslc_n8n_reply(WP_REST_Request $r){
 global $wpdb; [$vis,$msg]=dslc_tables(); $p=$r->get_json_params(); $vid=absint($p['visitor_id']??0); $cid=sanitize_text_field($p['conversation_id']??''); $text=sanitize_textarea_field($p['message']??'');
 if(!$vid||!$cid||!$text)return new WP_Error('invalid','visitor_id, conversation_id و message الزامی است',['status'=>400]);
 $wpdb->insert($msg,['visitor_id'=>$vid,'conversation_id'=>$cid,'role'=>'bot','message'=>$text,'status'=>'new','created_at'=>current_time('mysql')]);
 return ['ok'=>true,'message_id'=>(int)$wpdb->insert_id];
}

add_action('admin_menu',function(){add_menu_page('گفتگوهای مشتریان','Live Chat دژبان‌سک','manage_options','dslc','dslc_admin_page','dashicons-format-chat',26);});
add_action('admin_init',function(){
 register_setting('dslc_settings','dslc_n8n_webhook',['sanitize_callback'=>'esc_url_raw']);
 register_setting('dslc_settings','dslc_n8n_secret',['sanitize_callback'=>'sanitize_text_field']);
});
function dslc_admin_page(){ if(!current_user_can('manage_options'))return; $nonce=wp_create_nonce('wp_rest'); ?>
<div class="wrap dslc-admin" dir="rtl">
<h1>💬 گفتگوی مشتریان دژبان‌سک</h1>
<p class="description">هر مراجعه‌کننده یک پرونده دارد. نام، راه تماس و تمام مکالمات او در دیتابیس ذخیره می‌شود. پیام‌های جدید با نشان قرمز مشخص می‌شوند و پاسخ شما بدون رفرش به مشتری می‌رسد.</p>
<div id="dslc-admin-app">
 <aside class="dslc-sidebar">
  <div class="dslc-sidebar-top"><strong>مراجعین</strong><button class="button" id="dslc-refresh">↻ به‌روزرسانی</button></div>
  <div id="dslc-visitors"><div class="dslc-empty">در حال بارگذاری...</div></div>
 </aside>
 <main class="dslc-main">
  <div id="dslc-head" class="dslc-card dslc-head-empty">یک مراجعه‌کننده را انتخاب کنید.</div>
  <div id="dslc-conv-tabs" class="dslc-conv-tabs"></div>
  <div id="dslc-messages" class="dslc-card dslc-message-area"><div class="dslc-empty">برای مشاهده مکالمه، یک مراجعه‌کننده را انتخاب کنید.</div></div>
  <div class="dslc-reply-box"><textarea id="dslc-text" rows="3" placeholder="پاسخ خود را بنویسید..."></textarea><button class="button button-primary button-large" id="dslc-send">ارسال پاسخ</button></div>
 </main>
</div>
<hr><h2>🤖 اتصال به n8n</h2>
<form method="post" action="options.php"><?php settings_fields('dslc_settings'); ?><table class="form-table">
<tr><th>Webhook ورودی n8n</th><td><input class="regular-text" type="url" name="dslc_n8n_webhook" value="<?php echo esc_attr(get_option('dslc_n8n_webhook','')); ?>"><p class="description">هر پیام مشتری به این آدرس ارسال می‌شود.</p></td></tr>
<tr><th>Secret</th><td><input class="regular-text" type="text" name="dslc_n8n_secret" value="<?php echo esc_attr(get_option('dslc_n8n_secret','')); ?>"><p class="description">برای احراز هویت پاسخ n8n به وردپرس استفاده می‌شود.</p></td></tr>
</table><?php submit_button('ذخیره تنظیمات n8n'); ?></form>
<style>
.dslc-admin{max-width:1400px}.dslc-admin #dslc-admin-app{display:grid;grid-template-columns:340px minmax(0,1fr);gap:16px;margin-top:18px}.dslc-sidebar,.dslc-card,.dslc-reply-box{background:#fff;border:1px solid #dcdcde;border-radius:12px}.dslc-sidebar{overflow:hidden}.dslc-sidebar-top{padding:14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}.dslc-visitor{padding:13px;border-bottom:1px solid #eee;cursor:pointer}.dslc-visitor:hover,.dslc-visitor.active{background:#f0f8f7}.dslc-visitor b{display:block}.dslc-visitor small{display:block;color:#666;margin-top:4px}.dslc-unread{float:left;background:#d63638;color:#fff;border-radius:20px;padding:2px 7px;font-size:11px}.dslc-last{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:290px}.dslc-main{min-width:0}.dslc-head-empty{padding:18px}.dslc-head-card{padding:16px;display:flex;justify-content:space-between;gap:20px}.dslc-head-card .contact{color:#666}.dslc-conv-tabs{display:flex;gap:7px;flex-wrap:wrap;margin:10px 0}.dslc-conv-tab{border:1px solid #dcdcde;background:#fff;padding:7px 11px;border-radius:20px;cursor:pointer}.dslc-conv-tab.active{background:#2271b1;color:#fff;border-color:#2271b1}.dslc-message-area{height:520px;overflow:auto;padding:18px;background:#f6f7f7}.dslc-bubble-row{display:flex;margin:8px 0}.dslc-bubble-row.customer{justify-content:flex-start}.dslc-bubble-row.agent,.dslc-bubble-row.bot{justify-content:flex-end}.dslc-bubble{max-width:75%;padding:10px 13px;border-radius:14px;line-height:1.8;white-space:pre-wrap}.dslc-bubble-row.customer .dslc-bubble{background:#fff;border:1px solid #ddd}.dslc-bubble-row.agent .dslc-bubble{background:#d9f7f1}.dslc-bubble-row.bot .dslc-bubble{background:#e7e7ff}.dslc-bubble small{display:block;color:#777;font-size:10px;margin-top:3px}.dslc-reply-box{margin-top:10px;padding:12px}.dslc-reply-box textarea{width:100%;box-sizing:border-box;margin-bottom:8px}.dslc-empty{padding:30px;text-align:center;color:#777}.dslc-new-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#d63638;margin-left:5px}@media(max-width:900px){.dslc-admin #dslc-admin-app{grid-template-columns:1fr}.dslc-message-area{height:420px}}
</style>
<script>
(function(){const api='<?php echo esc_js(rest_url('dejbansec/v3')); ?>',nonce='<?php echo esc_js($nonce); ?>';let visitors=[],selected=null,selectedCid=null;
const $=id=>document.getElementById(id),esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
async function json(url,opt={}){opt.headers=Object.assign({'X-WP-Nonce':nonce},opt.headers||{});const r=await fetch(url,opt);const j=await r.json();if(!r.ok)throw new Error(j.message||'خطا');return j;}
async function loadVisitors(){try{visitors=await json(api+'/conversations');const box=$('dslc-visitors');box.innerHTML=visitors.map(v=>`<div class="dslc-visitor ${selected&&String(selected.id)===String(v.id)?'active':''}" data-id="${v.id}"><span class="dslc-unread" ${Number(v.unread||0)?'':'style="display:none"'}>${Number(v.unread||0)}</span><b>${esc(v.name||'مراجع بدون نام')}</b><small>${esc(v.contact||'بدون راه تماس')}</small><small>${Number(v.message_count||0)} پیام · ${esc(v.last_message||'')}</small><small class="dslc-last">${esc(v.last_text||'')}</small></div>`).join('')||'<div class="dslc-empty">هنوز پیامی ثبت نشده است.</div>';
box.querySelectorAll('.dslc-visitor').forEach(el=>el.onclick=()=>selectVisitor(Number(el.dataset.id)));}catch(e){$('dslc-visitors').innerHTML='<div class="dslc-empty">خطا در دریافت گفتگوها</div>';}}
async function selectVisitor(id,cid){selected=visitors.find(v=>Number(v.id)===Number(id))||selected;if(!selected)return;const j=await json(api+'/conversation?visitor_id='+id+(cid?'&conversation_id='+encodeURIComponent(cid):''));const convs=j.conversations||[];if(!selectedCid)selectedCid=cid||(convs[0]&&convs[0].conversation_id)||'';if(cid)selectedCid=cid;renderHead(j.visitor);renderTabs(convs);await renderMessages(id,selectedCid);await json(api+'/mark-read',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({visitor_id:id,conversation_id:selectedCid})});loadVisitors();}
function renderHead(v){$('dslc-head').className='dslc-card dslc-head-card';$('dslc-head').innerHTML=`<div><strong>👤 ${esc(v.name||'مراجع بدون نام')}</strong><div class="contact">${esc(v.contact||'راه تماس ثبت نشده')}</div></div><div><small>اولین مراجعه: ${esc(v.first_seen||'')}</small><br><small>آخرین فعالیت: ${esc(v.last_seen||'')}</small></div>`;}
function renderTabs(convs){$('dslc-conv-tabs').innerHTML=convs.map((c,i)=>`<button class="dslc-conv-tab ${String(c.conversation_id)===String(selectedCid)?'active':''}" data-cid="${esc(c.conversation_id)}">گفتگو ${convs.length-i} · ${Number(c.message_count)} پیام ${Number(c.unread)?' 🔴 '+Number(c.unread):''}</button>`).join('');$('dslc-conv-tabs').querySelectorAll('button').forEach(b=>b.onclick=()=>{selectedCid=b.dataset.cid;selectVisitor(selected.id,selectedCid);});}
async function renderMessages(id,cid){const j=await json(api+'/conversation?visitor_id='+id+'&conversation_id='+encodeURIComponent(cid));const box=$('dslc-messages');box.innerHTML=(j.messages||[]).map(m=>`<div class="dslc-bubble-row ${esc(m.role)}"><div class="dslc-bubble"><b>${m.role==='customer'?'مشتری':m.role==='bot'?'🤖 n8n':'شما'}</b><br>${esc(m.message)}<small>${esc(m.created_at)}</small></div></div>`).join('')||'<div class="dslc-empty">این گفتگو هنوز پیامی ندارد.</div>';box.scrollTop=box.scrollHeight;}
async function send(){const t=$('dslc-text').value.trim();if(!t||!selected||!selectedCid)return alert('ابتدا یک گفتگو را انتخاب کنید.');const b=$('dslc-send');b.disabled=true;try{await json(api+'/admin-reply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({visitor_id:selected.id,conversation_id:selectedCid,message:t})});$('dslc-text').value='';await renderMessages(selected.id,selectedCid);await loadVisitors();}catch(e){alert(e.message)}finally{b.disabled=false;}}
$('dslc-refresh').onclick=loadVisitors;$('dslc-send').onclick=send;$('dslc-text').addEventListener('keydown',e=>{if(e.key==='Enter'&&(e.ctrlKey||e.metaKey)){e.preventDefault();send();}});
loadVisitors();setInterval(async()=>{if(!selected)return loadVisitors();try{await renderMessages(selected.id,selectedCid);loadVisitors();}catch(e){}},2500);
})();
</script>
<?php }
