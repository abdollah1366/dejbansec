<?php
if (!defined('ABSPATH')) exit;

function dejban_child_enqueue_assets() {
    wp_enqueue_style('impreza-parent', get_template_directory_uri() . '/style.css', array(), null);
    wp_enqueue_style('dejban-child', get_stylesheet_uri(), array('impreza-parent'), '1.0.0');
    if (is_rtl()) {
        wp_enqueue_style('dejban-child-rtl', get_stylesheet_directory_uri() . '/rtl.css', array('dejban-child'), '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'dejban_child_enqueue_assets', 20);

function dejban_child_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'dejban_child_setup');
