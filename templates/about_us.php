<?php
/**
 * Template Name: About Us Template
 */
cscreative_get_template_part('header');
 ?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri()."/assets/css/about.css"?>">
<div class="page-content">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</div>
<?php
cscreative_get_template_part('footer');
?>