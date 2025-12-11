<?php cscreative_get_template_part('header'); ?>

<div class="404-content">
    <h1><?php _e('404 Not Found'); ?></h1>
    <p><?php _e('Sorry, but the page you were trying to view does not exist.'); ?></p>
    <p><?php _e('You might want to check the URL or return to the homepage.'); ?></p>
    <a href="<?php echo home_url(); ?>"><?php _e('Return to Homepage'); ?></a>
</div>

<?php cscreative_get_template_part('footer'); ?>
