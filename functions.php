<?php
/**
 * cscreative functions and definitions
 *
 * @package cscreative
 */

// Enqueue styles and scripts
function cscreative_enqueue_styles() {
    // Enqueue main stylesheet
    wp_enqueue_style( 'cscreative-style', get_stylesheet_uri() );

    // Enqueue custom scripts (if any)
    //wp_enqueue_script( 'cscreative-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'cscreative_enqueue_styles' );

// Theme setup
function cscreative_theme_setup() {
    // Add support for dynamic title tag
    add_theme_support( 'title-tag' );

    // Add support for post thumbnails
    add_theme_support( 'post-thumbnails' );

    // Add support for custom logos
    add_theme_support( 'custom-logo', array(
        'height'      => 50,
        'width'       => 200,
        'flex-width'  => true,
        'flex-height' => true,
    ));

    // Register navigation menus
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'cscreative' ),
        'footer'  => __( 'Footer Menu', 'cscreative' ),
    ));

    // Add support for HTML5 markup
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action( 'after_setup_theme', 'cscreative_theme_setup' );

// Register widget areas
function cscreative_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'cscreative' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here.', 'cscreative' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action( 'widgets_init', 'cscreative_widgets_init' );

// Load template parts from custom folders
function cscreative_get_template_part($slug, $name = null) {
    if ($name) {
        $templates = array("templates/{$slug}-{$name}.php", "parts/{$slug}-{$name}.php");
    } else {
        $templates = array("templates/{$slug}.php", "parts/{$slug}.php");
    }

    foreach ($templates as $template) {
        if (file_exists(get_template_directory() . '/' . $template)) {
            load_template(get_template_directory() . '/' . $template, false);
            return;
        }
    }
}

// Example usage in a theme file:
// cscreative_get_template_part('header', 'custom'); // Looks for templates/header-custom.php or parts/header-custom.php

// Customize excerpt length
function cscreative_custom_excerpt_length( $length ) {
    return 20;
}
add_filter( 'excerpt_length', 'cscreative_custom_excerpt_length', 999 );

//Register Post Type for Theme Settings
function cscreative_add_theme_page() {
    add_menu_page(
        'CS Creative Theme Settings', // Page title
        'Theme Settings',              // Menu title
        'manage_options',              // Capability
        'cscreative-theme-settings',   // Menu slug
        'cscreative_theme_settings_page', // Callback function
        'dashicons-admin-generic',     // Icon
        100                            // Position
    );
}

add_action('admin_menu', 'cscreative_add_theme_page');

//Page content for theme setting page
function cscreative_theme_settings_page() {
    ?>
    <div class="wrap">
        <h1>CS Creative Theme Settings</h1>
        <form method="post" action="options.php">
            <?php            
            // Output security fields for the registered setting
            settings_fields('cscreative_theme_options_group');
            // Output setting sections and their fields
            do_settings_sections('cscreative-theme-settings');
            // Output save settings button
            submit_button();
        ?>
        </form>
    </div>
    <?php
}

// Include the theme settings functions
require_once get_template_directory() . '/include/functions/theme-settings.php';

//Stylesheet enque for theme settings
function cscreative_admin_styles() {
    wp_enqueue_style('cscreative-admin-styles', get_template_directory_uri() . '/assets/css/admin.css');
}

add_action('admin_enqueue_scripts', 'cscreative_admin_styles');

function cscreative_enqueue_scripts() {
    // Enqueue the main stylesheet
    wp_enqueue_style('cscreative-style', get_stylesheet_uri());
    
    // Enqueue the JavaScript file
    wp_enqueue_script(
        'cscreative-menu-toggle',
        get_template_directory_uri() . '/assets/js/menu-toggle.js',
        array(), // Dependencies (none in this case)
        null,    // Version number (null for no version)
        true     // Load in footer (true) or header (false)
    );
}
add_action('wp_enqueue_scripts', 'cscreative_enqueue_scripts');


//Slider configuration for wp-admin settings

function my_custom_slider_settings($wp_customize) {
    // Add a new section for the slider
    $wp_customize->add_section('my_slider_section', array(
        'title'    => __('Custom Slider', 'your-textdomain'),
        'priority' => 30,
    ));

    // Add settings and controls for the slider images and taglines
    for ($i = 1; $i <= 3; $i++) {
        $wp_customize->add_setting("my_slider_image_$i", array(
            'default'   => '',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "my_slider_image_$i", array(
            'label'    => __("Slide $i Image", 'your-textdomain'),
            'section'  => 'my_slider_section',
            'settings' => "my_slider_image_$i",
        )));

        $wp_customize->add_setting("my_slider_tagline_$i", array(
            'default'   => '',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control("my_slider_tagline_$i", array(
            'label'    => __("Slide $i Tagline", 'your-textdomain'),
            'section'  => 'my_slider_section',
            'type'     => 'text',
        ));
    }
}
add_action('customize_register', 'my_custom_slider_settings');

//Support for woocommerce
add_action('after_setup_theme', 'theme_supports_woocommerce');

function theme_supports_woocommerce() {
    add_theme_support('woocommerce');
}

//Fixing price display issue when product on sale
add_filter('woocommerce_get_price_html', 'custom_price_html', 10, 2);

function custom_price_html($price, $product) {
    // Get the regular price and sale price
    $regular_price = wc_price($product->get_regular_price());
    $sale_price = wc_price($product->get_sale_price());

    if ($product->is_on_sale()) {
        // Format for sale products
        return '<del>' . $regular_price . '</del> || <ins>' . $sale_price . '</ins>';
    }

    // Format for non-sale products
    return $price;
}
function cscreative_default_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
    $options = get_option('cscreative_options');
    $default_image = $options['logo_url'];
    if (empty($html)) {
        // Path to your default image in the theme folder
        $html = '<img src="' . esc_url($default_image) . '" alt="Default Image" />';
        echo '<pre><hr>';
        print_r($default_image);
        echo '</pre></hr>';
        die('aaahh!');
    }
    return $html;
}
add_filter('post_thumbnail_html', 'cscreative_default_thumbnail', 10, 5);