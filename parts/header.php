<!DOCTYPE html>
<html <?php
    // Include Theme Settings as array to use in theme parts
    $options = get_option('cscreative_options');
    language_attributes();
  ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); ?></title>
    <script src=<?php echo get_template_directory_uri().'/assets/js/menu-toggle.js'?>></script>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri().'/assets/css/nav-bar.css'?>">
</head>
<body <?php body_class(); ?>>
    <header class="site-header">
        <div class="header-container">
            <div class="header-content">
                <div class="main_header_logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url($options['logo_url']); ?>" width="80" alt="<?php bloginfo('name'); ?>" />
                    </a>
                </div>
                <div class="main_header_name">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <div class="header_name"><?php bloginfo('name'); ?></div>
                    </a>
                </div>
            </div>
            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                <span class="menu-icon"></span>
            </button>
            <nav id="primary-menu" class="primary-menu-container">
                <ul id="menu-primary-menu" class="primary-menu">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'items_wrap'     => '%3$s', // Remove container div
                    ));
                    ?>
                </ul>
            </nav>
        </div>
    </header>
</body>
</html>
