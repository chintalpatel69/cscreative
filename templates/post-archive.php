<?php
/*
Template Name: Post Archive Page
*/
cscreative_get_template_part('header');
?><head>
<link rel="stylesheet" href="<?php echo get_template_directory_uri().'/assets/css/archieve.css'; ?>">
</head>
<div class="archive-container">
    <header class="archive-header">
        <h1><?php echo esc_html(get_the_title()); ?></h1>
        <p><?php echo esc_html(get_the_excerpt()); ?></p>
    </header>

    <div class="archive-content">
        <?php

        // Define the ID of the category to exclude
        $excluded_category_id = '20';

        // Define query arguments to fetch posts
        $args = array(
            'post_type'      => 'post',  // Adjust if using custom post types
            'posts_per_page' => 10,      // Number of posts to display
            'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
            'category__not_in' => array($excluded_category_id),
        );

        // Create a new instance of WP_Query
        $query = new WP_Query($args);

        // Start the loop
        if ($query->have_posts()) : ?>
            <div class="post-grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-item'); ?>>
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-thumbnail">
                                    <?php the_post_thumbnail('medium'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="post-details">
                                <h2 class="post-title"><?php the_title(); ?></h2>
                                <p class="post-excerpt text-block long-text"><?php the_excerpt(); ?></p>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="pagination">
                <?php 
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'prev_text' => __('« Previous'),
                    'next_text' => __('Next »'),
                )); 
                ?>
            </div>
            

        <?php else : ?>
            <p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
        <?php endif;

        // Reset post data
        wp_reset_postdata();
        ?>
    </div>
</div>

<?php cscreative_get_template_part('footer'); ?>