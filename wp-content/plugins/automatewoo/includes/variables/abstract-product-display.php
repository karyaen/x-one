<?php
/**
 * @class 		AW_Variable_Abstract_Product_Display
 * @package		AutomateWoo/Variables
 */

abstract class AW_Variable_Abstract_Product_Display extends AW_Variable
{
	public $support_limit_field = false;

	/**
	 * Init
	 */
	function init()
	{
		$templates = apply_filters( 'automatewoo/variables/product_templates', [
			'' => __( 'Default', 'automatewoo' ),
			'product-grid-2-col.php' => __('Product Grid - 2 Column', 'automatewoo'),
			'product-grid-3-col.php' => __('Product Grid - 3 Column', 'automatewoo'),
			'product-rows.php' => __('Product Rows', 'automatewoo')
		]);

		$this->add_parameter_select_field( 'template', __( "Select which template will be used to display the products. The default is 'Product Grid - 2 Column'. For information on creating custom templates please refer to the documentation.", 'automatewoo'), $templates );

		if ( $this->support_limit_field )
		{
			$this->add_parameter_text_field( 'limit', __( 'Set the maximum number of products that will be displayed.', 'automatewoo'), false, 8 );
		}
	}



	/**
	 * @param $product_ids
	 * @param $limit
	 * @return array
	 */
	function prepare_products( $product_ids, $limit = 8 )
	{
		if ( empty( $product_ids ) )
			return false;

		$args = array(
			'post_type'           => 'product',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
			'posts_per_page'      => $limit,
			'post__in'            => $product_ids,
			'meta_query'          => WC()->query->get_meta_query(),
			'fields' => 'ids'
		);

		return array_map( 'wc_get_product', get_posts( $args ) );
	}



	/**
	 * @param string $template
	 * @param array $args
	 *
	 * @return string
	 */
	function get_product_display_html( $template, $args = array() )
	{
		ob_start();

		if ( $template )
		{
			$template = sanitize_file_name( $template );
		}
		else
		{
			$template = 'product-grid-2-col.php';
		}

		aw_get_template( 'email/' . $template, $args );

		return ob_get_clean();
	}

}
