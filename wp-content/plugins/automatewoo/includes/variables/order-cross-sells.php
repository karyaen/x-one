<?php
/**
 * @class 		AW_Variable_Order_Cross_Sells
 * @package		AutomateWoo/Variables
 */

class AW_Variable_Order_Cross_Sells extends AW_Variable_Abstract_Product_Display
{
	protected $name = 'order.cross_sells';

	public $support_limit_field = true;


	function init()
	{
		parent::init();

		$this->description = sprintf(
			__( "Displays a product listing of cross sells based on the items in an order. Be sure to <a href='%s' target='_blank'>set up cross sells</a> before using.", 'automatewoo'),
			'http://docs.woothemes.com/document/related-products-up-sells-and-cross-sells/'
		);
	}


	/**
	 * @param $order WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters )
	{
		$limit = isset( $parameters['limit'] ) ? absint( $parameters['limit'] ) : 8;
		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;

		$cross_sells = aw_get_order_cross_sells( $order );

		if ( empty( $cross_sells ) )
			return false;

		$products = $this->prepare_products( $cross_sells, $limit );

		return $this->get_product_display_html( $template, [
			'products' => $products,
			'data_type' => 'order'
		]);
	}
}

return new AW_Variable_Order_Cross_Sells();

