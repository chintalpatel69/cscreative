<?php
/**
 * Template Name: Products Template
 */
cscreative_get_template_part('header'); 
?>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/woocommerce/products-archieve.css">

    <div class="page-content">
    <h1><?php echo esc_html("Products"); ?></h1>
        <div class="products-grid">
            <?php
            // Arguments for the query
            $args = array(
                'post_type'      => 'product',  // WooCommerce products
                'posts_per_page' => 12,         // Number of products to display
                'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
                'post_status'    => 'publish',  // Only show published products
            );

            // Create a new instance of WP_Query
            $query = new WP_Query($args);

            // Start the loop
            if ($query->have_posts()) : ?>
                <div class="products-list">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <article class="product-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="product-thumbnail">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="product-details">
                                    <h2 class="product-title"><?php the_title(); ?></h2>
                                    <div class="product-price">
                                        <?php echo wp_kses_post($product->get_price_html()); ?>
                                    </div>
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
                <p><?php _e('Sorry, no products matched your criteria.'); ?></p>
            <?php endif;

            // Reset post data
            wp_reset_postdata();
            ?>
        </div>
    </div>

<?php
cscreative_get_template_part('footer');
?>
