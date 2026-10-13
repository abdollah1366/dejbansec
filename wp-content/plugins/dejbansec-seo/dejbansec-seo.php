<?php
/**
 * Plugin Name: دژبان‌سک — سئو
 * Description: توضیحات متا، Open Graph و Twitter Card (پیش‌نمایش لینک در تلگرام و واتساپ)، داده‌ی ساختاریافته (Organization، WebSite، BlogPosting، BreadcrumbList، FAQPage)، نقشه‌ی سایت تمیز و noindex برای صفحه‌های کم‌محتوا. اگر Yoast، Rank Math یا AIOSEO فعال باشد، کاری نمی‌کند.
 * Version: 1.0.0
 * Author: DejbanSec
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) exit;

define('DSEO_VERSION', '1.0.0');
// Text the seed plugins put in placeholder posts and projects.
define('DSEO_THIN_MARKERS', array('این متن محتوای اولیه قابل ویرایش است', 'این نمونه‌کار محتوای نمونه است'));

function dseo_other_seo_plugin() {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION');
}

function dseo_settings() {
    $defaults = array(
        'home_description' => 'دژبان‌سک؛ خدمات امنیت سایبری، تست نفوذ مجاز، طراحی سایت امن و توسعه نرم‌افزار برای کسب‌وکارها، به‌همراه مقاله‌های روزانه‌ی آموزش امنیت.',
        'default_image'    => '',
        'logo'             => '',
        'same_as'          => array(),
        'output_meta'      => true, // description + Open Graph + Twitter
        'output_schema'    => true,
    );
    $s = get_option('dseo_settings', array());
    return wp_parse_args(is_array($s) ? $s : array(), $defaults);
}

function dseo_sanitize_settings($in) {
    $old = dseo_settings();
    $in  = is_array($in) ? $in : array();
    $same = $in['same_as'] ?? $old['same_as'];
    if (is_string($same)) $same = preg_split('/\s+/', $same);
    return array(
        'home_description' => sanitize_textarea_field($in['home_description'] ?? $old['home_description']),
        'default_image'    => esc_url_raw($in['default_image'] ?? $old['default_image']),
        'logo'             => esc_url_raw($in['logo'] ?? $old['logo']),
        'same_as'          => array_values(array_filter(array_map('esc_url_raw', (array) $same))),
        'output_meta'      => (bool) ($in['output_meta'] ?? $old['output_meta']),
        'output_schema'    => (bool) ($in['output_schema'] ?? $old['output_schema']),
    );
}

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */

function dseo_clean($text, $words = 32) {
    $text = wp_strip_all_tags(strip_shortcodes((string) $text), true);
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
    $text = wp_trim_words($text, $words, '…');
    return $text;
}

function dseo_site_name() {
    return get_bloginfo('name') ?: 'دژبان‌سک';
}

/** Published posts/projects that still hold seed placeholder text. */
function dseo_thin_ids() {
    $ids = get_transient('dseo_thin_ids');
    if (is_array($ids)) return $ids;
    global $wpdb;
    $where = array();
    foreach (DSEO_THIN_MARKERS as $m) $where[] = $wpdb->prepare('post_content LIKE %s', '%' . $wpdb->esc_like($m) . '%');
    $ids = array_map('intval', $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','dsu_project') AND (" . implode(' OR ', $where) . ')'
    ));
    set_transient('dseo_thin_ids', $ids, 12 * HOUR_IN_SECONDS);
    return $ids;
}
add_action('save_post', function () { delete_transient('dseo_thin_ids'); });

function dseo_description() {
    $s = dseo_settings();
    if (is_front_page() || is_home()) return $s['home_description'];
    if (is_singular()) {
        $post = get_queried_object();
        foreach (array('us_meta_description', '_dseo_description') as $key) {
            $manual = trim((string) get_post_meta($post->ID, $key, true));
            if ($manual !== '') return dseo_clean($manual, 40);
        }
        $text = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
        return dseo_clean($text, 32);
    }
    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $desc = dseo_clean(term_description($term), 32);
        return $desc !== '' ? $desc : sprintf('مقاله‌ها و راهنماهای «%s» در %s؛ آموزش کاربردی امنیت سایبری و وب.', $term->name, dseo_site_name());
    }
    return '';
}

/** array(url, width, height) for the page's share image, or null. */
function dseo_image() {
    if (is_singular() && has_post_thumbnail(get_queried_object_id())) {
        $img = wp_get_attachment_image_src(get_post_thumbnail_id(get_queried_object_id()), 'large');
        if ($img) return array($img[0], (int) $img[1], (int) $img[2]);
    }
    $s = dseo_settings();
    if ($s['default_image']) return array($s['default_image'], 0, 0);
    if (has_site_icon()) return array(get_site_icon_url(512), 512, 512);
    return null;
}

function dseo_logo() {
    $s = dseo_settings();
    if ($s['logo']) return $s['logo'];
    $custom = get_theme_mod('custom_logo');
    if ($custom && ($src = wp_get_attachment_image_url($custom, 'full'))) return $src;
    if (has_site_icon()) return get_site_icon_url(512);
    $dsu = get_option('dsu_logo_url');
    return is_string($dsu) && $dsu ? $dsu : '';
}

function dseo_url() {
    if (is_singular()) return get_permalink(get_queried_object_id());
    if (is_front_page() || is_home()) return home_url('/');
    if (is_category() || is_tag() || is_tax()) {
        $link = get_term_link(get_queried_object());
        return is_wp_error($link) ? '' : $link;
    }
    return '';
}

/* ------------------------------------------------------------------ *
 * <head>: description, Open Graph, Twitter
 * ------------------------------------------------------------------ */

add_action('wp_head', function () {
    if (dseo_other_seo_plugin() || is_admin()) return;
    $s = dseo_settings();
    if (!$s['output_meta'] || is_404() || is_search()) return;

    $tags  = array();
    $desc  = dseo_description();
    $url   = dseo_url();
    $title = is_front_page() || is_home() ? dseo_site_name() . ' | ' . get_bloginfo('description') : wp_get_document_title();
    if (is_singular() && !is_front_page()) $title = single_post_title('', false);
    $title = trim(html_entity_decode(wp_strip_all_tags($title), ENT_QUOTES, 'UTF-8'), ' |');

    if ($desc !== '') $tags[] = array('name', 'description', $desc);
    $tags[] = array('property', 'og:locale', 'fa_IR');
    $tags[] = array('property', 'og:site_name', dseo_site_name());
    $tags[] = array('property', 'og:type', is_singular('post') ? 'article' : 'website');
    $tags[] = array('property', 'og:title', $title);
    if ($desc !== '') $tags[] = array('property', 'og:description', $desc);
    if ($url) $tags[] = array('property', 'og:url', $url);

    $img = dseo_image();
    if ($img) {
        $tags[] = array('property', 'og:image', $img[0]);
        if ($img[1]) {
            $tags[] = array('property', 'og:image:width', (string) $img[1]);
            $tags[] = array('property', 'og:image:height', (string) $img[2]);
        }
        $tags[] = array('property', 'og:image:alt', $title);
    }

    if (is_singular('post')) {
        $post = get_queried_object();
        $tags[] = array('property', 'article:published_time', get_post_time('c', true, $post));
        $tags[] = array('property', 'article:modified_time', get_post_modified_time('c', true, $post));
        $cats = get_the_category($post->ID);
        if ($cats) $tags[] = array('property', 'article:section', $cats[0]->name);
        foreach ((array) get_the_tags($post->ID) as $t) if ($t) $tags[] = array('property', 'article:tag', $t->name);
    }

    $tags[] = array('name', 'twitter:card', $img ? 'summary_large_image' : 'summary');
    $tags[] = array('name', 'twitter:title', $title);
    if ($desc !== '') $tags[] = array('name', 'twitter:description', $desc);
    if ($img) $tags[] = array('name', 'twitter:image', $img[0]);

    echo "\n<!-- dejbansec-seo " . DSEO_VERSION . " -->\n";
    foreach ($tags as $t) printf('<meta %s="%s" content="%s">' . "\n", $t[0], esc_attr($t[1]), esc_attr($t[2]));
}, 2);

/* ------------------------------------------------------------------ *
 * JSON-LD
 * ------------------------------------------------------------------ */

/** Question/answer pairs from an article's «سؤالات متداول» section (h3 + p, up to the next h2). */
function dseo_article_faq($html) {
    if (!preg_match('~<h2[^>]*>\s*(?:سؤالات|سوالات|پرسش‌های)\s*متداول\s*</h2>(.*?)(?=<h2|$)~su', (string) $html, $m)) return array();
    preg_match_all('~<h3[^>]*>(.*?)</h3>\s*((?:<p[^>]*>.*?</p>\s*)+)~su', $m[1], $qa, PREG_SET_ORDER);
    $out = array();
    foreach ($qa as $x) {
        $q = dseo_clean($x[1], 60);
        $a = dseo_clean($x[2], 120);
        if ($q !== '' && $a !== '') $out[] = array($q, $a);
    }
    return $out;
}

function dseo_faq_node($pairs, $id) {
    $items = array();
    foreach ($pairs as $p) {
        $items[] = array('@type' => 'Question', 'name' => $p[0], 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $p[1]));
    }
    return array('@type' => 'FAQPage', '@id' => $id, 'inLanguage' => 'fa-IR', 'mainEntity' => $items);
}

function dseo_print_jsonld($graph) {
    echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

add_action('wp_head', function () {
    if (dseo_other_seo_plugin() || is_admin() || is_404()) return;
    $s = dseo_settings();
    if (!$s['output_schema']) return;

    $home = home_url('/');
    $org  = array(
        '@type'         => 'Organization',
        '@id'           => $home . '#organization',
        'name'          => dseo_site_name(),
        'alternateName' => 'DejbanSec',
        'url'           => $home,
    );
    if ($logo = dseo_logo()) $org['logo'] = array('@type' => 'ImageObject', 'url' => $logo);
    $email = get_option('dsu_email', 'info@dejbansec.ir');
    if (is_email($email)) $org['email'] = $email;
    if ($s['same_as']) $org['sameAs'] = $s['same_as'];

    $graph = array($org, array(
        '@type'           => 'WebSite',
        '@id'             => $home . '#website',
        'url'             => $home,
        'name'            => dseo_site_name(),
        'inLanguage'      => 'fa-IR',
        'publisher'       => array('@id' => $home . '#organization'),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array('@type' => 'EntryPoint', 'urlTemplate' => $home . '?s={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ),
    ));

    if (is_singular('post')) {
        $post = get_queried_object();
        $url  = get_permalink($post);
        $art  = array(
            '@type'            => 'BlogPosting',
            '@id'              => $url . '#article',
            'headline'         => wp_strip_all_tags(get_the_title($post)),
            'description'      => dseo_description(),
            'datePublished'    => get_post_time('c', true, $post),
            'dateModified'     => get_post_modified_time('c', true, $post),
            'inLanguage'       => 'fa-IR',
            'mainEntityOfPage' => $url,
            'author'           => array('@id' => $home . '#organization'),
            'publisher'        => array('@id' => $home . '#organization'),
            'isPartOf'         => array('@id' => $home . '#website'),
        );
        $img = dseo_image();
        if ($img && has_post_thumbnail($post)) $art['image'] = $img[0];
        $cats = get_the_category($post->ID);
        if ($cats) $art['articleSection'] = $cats[0]->name;
        $tags = get_the_tags($post->ID);
        if ($tags) $art['keywords'] = implode('، ', wp_list_pluck($tags, 'name'));
        $art['wordCount'] = count(preg_split('/\s+/u', trim(wp_strip_all_tags($post->post_content)), -1, PREG_SPLIT_NO_EMPTY));
        $graph[] = $art;

        $crumbs = array(array('خانه', $home));
        if ($cats) $crumbs[] = array($cats[0]->name, get_category_link($cats[0]));
        $crumbs[] = array(wp_strip_all_tags(get_the_title($post)), $url);
        $list = array();
        foreach ($crumbs as $i => $c) $list[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]);
        $graph[] = array('@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $list);

        $faq = dseo_article_faq($post->post_content);
        if ($faq) $graph[] = dseo_faq_node($faq, $url . '#faq');
    }

    dseo_print_jsonld($graph);
}, 3);

/* The home FAQ lives inside the [dejbansec_ultimate_home] shortcode; read it from the rendered output. */
$GLOBALS['dseo_home_faq'] = array();
add_filter('do_shortcode_tag', function ($output, $tag) {
    if ($tag !== 'dejbansec_ultimate_home' || !is_front_page()) return $output;
    if (preg_match('~class="dsu-faq"(.*?)</section>~su', $output, $m)) {
        preg_match_all('~<summary[^>]*>(.*?)</summary>\s*<p[^>]*>(.*?)</p>~su', $m[1], $qa, PREG_SET_ORDER);
        foreach ($qa as $x) $GLOBALS['dseo_home_faq'][] = array(dseo_clean($x[1], 60), dseo_clean($x[2], 120));
    }
    return $output;
}, 10, 2);

add_action('wp_footer', function () {
    if (dseo_other_seo_plugin() || empty($GLOBALS['dseo_home_faq'])) return;
    if (!dseo_settings()['output_schema']) return;
    dseo_print_jsonld(array(dseo_faq_node($GLOBALS['dseo_home_faq'], home_url('/') . '#faq')));
}, 5);

/* ------------------------------------------------------------------ *
 * Robots and sitemap
 * ------------------------------------------------------------------ */

add_filter('wp_robots', function ($robots) {
    if (dseo_other_seo_plugin()) return $robots;
    $robots['max-image-preview'] = 'large';
    $form_echo = isset($_GET['dsu_sent']) || isset($_GET['dss_sent']) || isset($_GET['dfg_err']);
    $thin = is_singular() && in_array(get_queried_object_id(), dseo_thin_ids(), true);
    if (is_search() || $form_echo || $thin) {
        unset($robots['max-image-preview']);
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
});

// User sitemaps list author slugs (handy for attackers, useless for search).
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

add_filter('wp_sitemaps_post_types', function ($types) {
    unset($types['attachment']);
    return $types;
});

add_filter('wp_sitemaps_posts_query_args', function ($args) {
    $thin = dseo_thin_ids();
    if ($thin) $args['post__not_in'] = array_merge($args['post__not_in'] ?? array(), $thin);
    return $args;
});

add_filter('wp_sitemaps_taxonomies', function ($tax) {
    unset($tax['post_format']);
    return $tax;
});

/* ------------------------------------------------------------------ *
 * Admin and REST
 * ------------------------------------------------------------------ */

add_action('admin_menu', function () {
    add_options_page('سئوی دژبان‌سک', 'سئوی دژبان‌سک', 'manage_options', 'dseo-settings', 'dseo_settings_page');
});
add_action('admin_init', function () {
    register_setting('dseo_settings', 'dseo_settings', array('sanitize_callback' => 'dseo_sanitize_settings'));
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_options') || get_option('blog_public')) return;
    printf('<div class="notice notice-warning"><p><strong>موتورهای جستجو سایت را ایندکس نمی‌کنند.</strong> در <a href="%s">تنظیمات ← خواندن</a> تیک «از موتورهای جستجو درخواست کنید» را بردارید.</p></div>', esc_url(admin_url('options-reading.php')));
});

function dseo_settings_page() {
    if (!current_user_can('manage_options')) return;
    $s = dseo_settings();
    ?>
<div class="wrap" dir="rtl"><h1>سئوی دژبان‌سک</h1>
<?php if (dseo_other_seo_plugin()) echo '<div class="notice notice-info"><p>یک افزونه‌ی سئوی دیگر فعال است؛ این افزونه خروجی نمی‌دهد.</p></div>'; ?>
<form method="post" action="options.php"><?php settings_fields('dseo_settings'); ?>
<table class="form-table">
<tr><th>توضیحات صفحه‌ی اصلی</th><td><textarea class="large-text" rows="3" name="dseo_settings[home_description]"><?php echo esc_textarea($s['home_description']); ?></textarea><p class="description">۱۲۰ تا ۱۶۰ نویسه؛ در نتایج گوگل و پیش‌نمایش لینک دیده می‌شود.</p></td></tr>
<tr><th>تصویر پیش‌فرض اشتراک‌گذاری</th><td><input class="large-text" dir="ltr" name="dseo_settings[default_image]" value="<?php echo esc_attr($s['default_image']); ?>"><p class="description">برای صفحه‌هایی که تصویر شاخص ندارند. ۱۲۰۰×۶۳۰ پیکسل بهترین است.</p></td></tr>
<tr><th>لوگو</th><td><input class="large-text" dir="ltr" name="dseo_settings[logo]" value="<?php echo esc_attr($s['logo']); ?>"></td></tr>
<tr><th>شبکه‌های اجتماعی</th><td><textarea class="large-text" dir="ltr" rows="3" name="dseo_settings[same_as]"><?php echo esc_textarea(implode("\n", $s['same_as'])); ?></textarea><p class="description">هر خط یک لینک (لینکدین، تلگرام، اینستاگرام، گیت‌هاب…)</p></td></tr>
<tr><th>خروجی</th><td>
<input type="hidden" name="dseo_settings[output_meta]" value="0"><label><input type="checkbox" name="dseo_settings[output_meta]" value="1" <?php checked($s['output_meta']); ?>> توضیحات متا و Open Graph</label><br>
<input type="hidden" name="dseo_settings[output_schema]" value="0"><label><input type="checkbox" name="dseo_settings[output_schema]" value="1" <?php checked($s['output_schema']); ?>> داده‌ی ساختاریافته (JSON-LD)</label>
</td></tr>
</table>
<?php submit_button('ذخیره'); ?></form>
<p>صفحه‌های با محتوای نمونه که noindex شده‌اند و در نقشه‌ی سایت نیستند: <?php echo count(dseo_thin_ids()); ?> — نقشه‌ی سایت: <a href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" dir="ltr">wp-sitemap.xml</a></p>
</div>
<?php
}

add_action('rest_api_init', function () {
    $admin = function () { return current_user_can('manage_options'); };
    register_rest_route('dejbansec/v1', '/seo/status', array(
        'methods' => WP_REST_Server::READABLE, 'permission_callback' => $admin,
        'callback' => function () {
            return array(
                'version'      => DSEO_VERSION,
                'blog_public'  => (int) get_option('blog_public'),
                'other_plugin' => dseo_other_seo_plugin(),
                'settings'     => dseo_settings(),
                'thin_ids'     => dseo_thin_ids(),
            );
        },
    ));
    register_rest_route('dejbansec/v1', '/seo/settings', array(
        'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => $admin,
        'callback' => function (WP_REST_Request $r) {
            $p = $r->get_json_params();
            if (isset($p['blog_public'])) update_option('blog_public', $p['blog_public'] ? '1' : '0');
            unset($p['blog_public']);
            if ($p) update_option('dseo_settings', dseo_sanitize_settings($p));
            return array('blog_public' => (int) get_option('blog_public'), 'settings' => dseo_settings());
        },
    ));
});
