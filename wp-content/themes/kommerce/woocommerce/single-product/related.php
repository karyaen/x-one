<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $post;
$cat_count = sizeof( get_the_terms( $post->ID, 'product_cat' ) );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product, $woocommerce_loop;

if ( empty( $product ) || ! $product->exists() ) {
	return;
}

if ( ! $related = $product->get_related( $posts_per_page ) ) {
	return;
}

$args = apply_filters( 'woocommerce_related_products_args', array(
	'post_type'            => 'product',
	'ignore_sticky_posts'  => 1,
	'no_found_rows'        => 1,
	'posts_per_page'       => $posts_per_page,
	'orderby'              => $orderby,
	'post__in'             => $related,
	'post__not_in'         => array( $product->id )
) );

$products                    = new WP_Query( $args );
$woocommerce_loop['name']    = 'related';
$woocommerce_loop['columns'] = apply_filters( 'woocommerce_related_products_columns', $columns );


if ( $products->have_posts() ) : ?>
<?php $cat_count = sizeof( get_the_terms( $post->ID, 'product_cat' ) ); ?>
	<section class="related products related-products">

		<ul class="products">
			<li class="description">
				<h1>You Might also like</h1>
				<div class="latest-product-blurb">
					<p>
						SEO text. Sound that is designed to bring you music the way the artists intended it – honest, clean and with passion.
					</p>
				</div>
				<div class="parent-category-link">
					<?php $term = get_the_terms( $post->ID, 'product_cat' );

					foreach ($term as $t) {
						$category_link = get_category_link( $t->term_id );
						echo '<p><a href="'. $category_link.'">Back to Category <span class="back-icon"></span></a></p>';
					}
					?>
				</div>
			</li>

			<?php while ( $products->have_posts() ) : $products->the_post(); ?>

				<?php wc_get_template_part( 'content', 'product' ); ?>

			<?php endwhile; // end of the loop. ?>

		<?php woocommerce_product_loop_end(); ?>

		</ul>
	</section>

<?php endif;

wp_reset_postdata();