<?php
/**
 * Template for displaying inner sections of single products with improved structure and modern design
 */

cscreative_get_template_part('header'); 

?>

<head>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri() . '/assets/css/woocommerce/single-product.css'; ?>">
    <script src=<?php echo get_template_directory_uri() . '/assets/js/product-gallery.js'; ?>></script>
</head>

<div class="product-inner-container">
    <!-- Product Gallery Section -->
    <section class="product-gallery">
        <!-- Display Main Product Image -->
        <?php if (has_post_thumbnail()) : ?>
            <div class="main-image">
                <img id="large-image" src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" alt="Main Product Image" class="main-product-image">
            </div>
            <?php endif; ?>
            
            <!-- Display Gallery Thumbnails -->
            <?php
        $product = wc_get_product(get_the_ID());
        if ($product && $product->get_gallery_image_ids()) :
        ?>
            <div class="gallery-thumbnails">
                <img id="product-image" src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'small'); ?>" class="thumbnail-image" alt="Thumbnail Image">
                <?php foreach ($product->get_gallery_image_ids() as $image_id) : ?>
                    <img src="<?php echo wp_get_attachment_url($image_id); ?>" class="thumbnail-image" alt="Thumbnail Image">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            
            
            <!-- Product Details Section -->
            <section class="product-details">
                <?php while (have_posts()) : the_post(); ?>
                <!-- Product Title -->
                <div class="product-header">
                    <p class="product-title"><?php the_title(); ?></p>
                </div>
                
                <!-- Price -->
                <div class="price">
                    <?php echo $product->get_price_html(); ?>
                </div>
                
                <div class="scrollable-content">
                    <!-- Product Description -->
                    <div class="description">
                        <?php the_content(); ?>
                    </div>
                </div>
                <!-- Section to add product review below product description -->
                <!-- <div id="product-rating" class="product-rating">
                    <?php
                            // if (comments_open()) {
                            //     comments_template();
                            // }
                    ?>
                </div> -->
        <!-- Add to Cart Button -->
        <!-- <div class="product-actions">
            <button class="button add-to-cart">Add to Cart</button>
        </div> -->
        <?php endwhile; ?>
    </section>
</div>

<?php cscreative_get_template_part('footer'); ?>
