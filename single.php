<?php cscreative_get_template_part('header'); ?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri().'/assets/css/single.css';?>">
<div class="single-post-content">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            ?>
            <article <?php post_class(); ?>>
                <div class="featured-image"><?php the_post_thumbnail('medium');?></div>
                <h1 class="post-title"><?php the_title(); ?></h1>
                <div class="post-content"><?php the_content(); ?></div>
                <nav class="post-navigation">
                    <div class="nav-previous">
                        <?php
                        $prev_post = get_previous_post();
                        if (!empty($prev_post)) :
                            ?>
                            <a href="<?php echo get_permalink($prev_post->ID); ?>">&laquo; <?php echo esc_html(get_the_title($prev_post->ID)); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="nav-home">
                        <a href="<?php echo esc_url(home_url('/')); ?>">&#8962; Home</a>
                    </div>
                    <div class="nav-next">
                        <?php
                        $next_post = get_next_post();
                        if (!empty($next_post)) :
                            ?>
                            <a href="<?php echo get_permalink($next_post->ID); ?>"><?php echo esc_html(get_the_title($next_post->ID)); ?> &raquo;</a>
                        <?php endif; ?>
                    </div>
                </nav>
            </article>
        <?php
        endwhile;
    else :
        ?>
        <p><?php _e('Post not found.'); ?></p>
    <?php endif; ?>
</div>

<?php cscreative_get_template_part('footer'); ?>
