<?php cscreative_get_template_part('header'); ?>

<div class="site-content">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </article>
        <?php
        endwhile;
        the_posts_navigation();
    else :
        ?>
        <p><?php _e('No content found.'); ?></p>
    <?php endif; ?>
</div>

<?php cscreative_get_template_part('footer'); ?>
