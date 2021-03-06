<?php
/**
 * Update Product Meta Action
 *
 * @class       AW_Action_Update_Product_Meta
 * @package     AutomateWoo/Actions
 * @since       1.1.4
 */

class AW_Action_Update_Product_Meta extends AW_Action
{
	public $name = 'update_product_meta';

	public $required_data_items = array(
		'product',
	);


	function init()
	{
		$this->title = __('Add/Update Product Meta', 'automatewoo');

		// Registers the actions
		parent::init();
	}


	function load_fields()
	{
		$meta_key = new AW_Field_Text_Input();
		$meta_key->set_name('meta_key');
		$meta_key->set_title(__('Meta Key', 'automatewoo'));
		$meta_key->set_required(true);

		$meta_value = new AW_Field_Text_Input();
		$meta_value->set_name('meta_value');
		$meta_value->set_title(__('Meta Value', 'automatewoo'));

		$this->add_field($meta_key);
		$this->add_field($meta_value);
	}


	/**
	 * Requires a product data item
	 *
	 * @return mixed|void
	 */
	function run()
	{
		// Do we have an order object?
		if ( ! $product = $this->workflow->get_data_item('product') )
			return;

		$meta_key = $this->get_option('meta_key', true );
		$meta_value = $this->get_option('meta_value', true );

		// Make sure there is a meta key but a value is not required
		if ( $meta_key ) {
			update_post_meta( $product->id, $meta_key, $meta_value );
		}

	}

}
