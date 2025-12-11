<?php
/**
 * Template Name: Home Template
 */
// Get header
cscreative_get_template_part('header');

// Enqueue styles and scripts properly
// function custom_home_template_scripts() {
//     wp_enqueue_style('home-style', get_template_directory_uri() . '/assets/css/home.css');
//     wp_enqueue_script('custom-slider', get_template_directory_uri() . '/assets/js/custom-slider.js', array(), false, true);
// }
// add_action('wp_enqueue_scripts', 'custom_home_template_scripts');

?>
<head>
<link rel="stylesheet" href=<?php echo get_template_directory_uri() . '/assets/css/home.css' ?>>
<script src="<?php echo get_template_directory_uri() . '/assets/js/custom-slider.js' ?>"></script>
</head>

<div class="page-content">
    <?php
    // Banner Image Slider Context
    $slides = array();
    for ($i = 1; $i <= 3; $i++) {
        $image = get_theme_mod("my_slider_image_$i");
        $tagline = get_theme_mod("my_slider_tagline_$i");
        if ($image) {
            $slides[] = array(
                'image'  => $image,
                'tagline' => $tagline,
            );
        }
    }
    ?>
    <div class="custom-slider">
        <div class="slides">
            <?php foreach ($slides as $slide): ?>
                <div class="slide">
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="Slide Image">
                    <?php if (!empty($slide['tagline'])): ?>
                        <div class="caption"><?php echo esc_html($slide['tagline']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <button class="prev-slide">&#10094;</button>
        <button class="next-slide">&#10095;</button>
        <div class="dots">
            <?php for ($i = 0; $i < count($slides); $i++): ?>
                <span class="dot" data-slide="<?php echo $i; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
        
        <?php
    // Display Page Content
    while (have_posts()) : the_post();
    ?>
        <article <?php post_class(); ?>>
            <div><?php the_content(); ?></div>
        </article>
        <?php endwhile; ?>
        
    <?php
        // Fetch and display Featured Posts
        $featured_posts_args = array(
            'post_type' => 'post',
            'category_name' => 'featured',
            'category__not_in' => array(20) // Ensure this is an array
        );
        $featured_posts_query = new WP_Query($featured_posts_args);
        ?>
        <h2 class="posts-title">Popular Posts</h2>
        <p>Listing of our most popular and featured posts</p>
        <section class="posts">
            <?php
            if ($featured_posts_query->have_posts()) :
                while ($featured_posts_query->have_posts()) : $featured_posts_query->the_post();
            ?>
                <div>
                    <a href="<?php the_permalink(); ?>">
                        <div class="post-box">
                            <div class="posts-image"><?php the_post_thumbnail('medium'); ?></div>
                            <div class="posts-title"><?php the_title(); ?></div>
                            <div class="posts-excerpt text-block long-text"><?php the_excerpt(); ?></div>
                        </div>
                    </a>
                </div>
                <?php endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No posts featured.</p>';
            endif;
            ?>
        </section>
    <?php
    // Fetch and display Services
    $services_cat = get_term_by('slug', 'services', 'category');
    $featured_cat = get_term_by('slug', 'featured', 'category');

    if ($services_cat && $featured_cat) {
        $services_cat_id = $services_cat->term_id;
        $featured_cat_id = $featured_cat->term_id;

        $services_args = array(
            'post_type' => 'post',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $services_cat_id,
                    'operator' => 'IN',
                ),
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $featured_cat_id,
                    'operator' => 'IN',
                ),
            ),
        );
        $services_query = new WP_Query($services_args);
    ?>
        <h2 class="services-title">Popular Services</h2>
        <p>Listing of our Most Popular and Featured Services</p>
        <section class="services">
            <?php
            if ($services_query->have_posts()) :
                while ($services_query->have_posts()) : $services_query->the_post();
            ?>
                <div>
                    <a href="<?php the_permalink(); ?>">
                    <div class="service-box">
                        <div class="services-image"><?php the_post_thumbnail('medium'); ?></div>
                        <div class="services-title"><?php the_title(); ?></div>
                        <div class="services-excerpt text-block long-text"><?php the_excerpt(); ?></div>
                    </div>
                    </a>
                </div>
                <?php endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No services added.</p>';
            endif;
            ?>
        </section>
    <?php
    } else {
        echo '<p>Categories not found.</p>';
    }
    ?>
</div>
<?php
// Get footer
cscreative_get_template_part('footer');
?>
