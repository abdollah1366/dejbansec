<?php
/**
 * Dejban Child - Front Page
 * Keeps the existing WordPress homepage content intact while using
 * the child theme assets.
 */
if (!defined('ABSPATH')) exit;

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        the_content();
    endwhile;
endif;

get_footer();
