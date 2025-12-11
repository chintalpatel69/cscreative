<?php
/**
 * Template Name: Services Template
 */
cscreative_get_template_part('header');
 ?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri()."/assets/css/services.css"?>">
<div class="page-content">
    <?php
        // Fetching and displaying Services for Services Section from Post with added category "Services"
        // To add services simply create new post and assign service category
            $services = array('post_type' => 'post', 'category_name' => 'services');
            $service = new WP_Query($services);
            ?><h2>Services</h2><section class="services"><?php
                if ($service->have_posts()) :
                    while ($service->have_posts()) : $service->the_post(); ?>
                        <div>
                            <a href="<?php the_permalink(); ?>">
                            <div class="service-box">
                                <div class="services-image"><?php the_post_thumbnail('medium');?></div>
                                <div class="services-title"><?php the_title(); ?></div>
                                <div class="services-excerpt text-block long-text"><?php the_excerpt(); ?></div>
                            </div>
                            </a>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                else :
                    echo '<p>No Services Added in post.</p>';
                endif;
                ?>
</div>
<?php
cscreative_get_template_part('footer');
?>