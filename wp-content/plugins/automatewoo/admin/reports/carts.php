<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * AW_Report_Logs
 *
 * @since 2.0
 */
class AW_Report_Carts extends AW_Report_List_Table
{
	public $_column_headers;
	public $max_items;


	/**
	 * __construct function.
	 */
	function __construct()
	{
		parent::__construct( array(
			'singular'  => __( 'Cart', 'automatewoo' ),
			'plural'    => __( 'Carts', 'automatewoo' ),
			'ajax'      => false
		) );
	}

	/**
	 * No items found text
	 */
	function no_items()
	{
		_e( 'No active carts found.', 'automatewoo' );
	}



	/**
	 * column_default function.
	 *
	 * @param AW_Model_Abandoned_Cart $cart
	 * @param mixed $column_name
	 */
	function column_default( $cart, $column_name )
	{
		if ( $cart->user_id )
		{
			$user = get_user_by( 'id', $cart->user_id );
		}
		else
		{
			$guest = $cart->get_guest();
		}

		switch( $column_name )
		{
			case 'id':
				echo '#' . $cart->id;
				break;

			case 'workflow':
				echo '<a href="' . get_edit_post_link( $cart->workflow_id ) . '"><strong>' . get_the_title( $cart->workflow_id ) . '</strong></a>';
				break;

			case 'user':

				if ( ! $cart->user_id && $guest )
				{
					$this->format_guest( $guest->email );
				}
				elseif ( $cart->user_id )
				{
					$this->format_user( $user );
				}
				else
				{
					$this->format_blank();
				}
				break;

			case 'last_modified':
				$this->format_date( $cart->last_modified );
				break;

			case 'items':

				if ( $cart->items )
					echo count( $cart->items );
				else
					$this->format_blank();

				break;

			case 'total':
				echo wc_price($cart->total);
				break;

			case 'language':

				if ( ! AW()->integrations()->is_wpml() )
					return;

				if ( $cart->user_id )
				{
					echo AW()->language_helper->get_user_language( $user );
				}
				else
				{
					echo AW()->language_helper->get_guest_language( $guest );
				}
				break;
		}
	}


	/**
	 * @param $cart AW_Model_Abandoned_Cart
	 * @return string
	 */
	function column_cb( $cart )
	{
		return '<input type="checkbox" name="cart_ids[]" value="' . absint( $cart->id ) . '" />';
	}


	/**
	 * get_columns function.
	 */
	function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'id' => __( 'Cart', 'automatewoo' ),
			'user' => __( 'User', 'automatewoo' ),
			'last_modified' => __( 'Last Modified', 'automatewoo' ),
			'items' => __( 'Items', 'automatewoo' ),
			'total' => __( 'Total', 'automatewoo' ),
		);

		if ( AW()->integrations()->is_wpml() )
		{
			$columns['language'] = __( 'Language', 'automatewoo' );
		}

		return $columns;
	}

	/**
	 * prepare_items function.
	 */
	function prepare_items()
	{
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page = absint( $this->get_pagenum() );
		$per_page = apply_filters( 'automatewoo_report_items_per_page', 20 );

		$this->get_items( $current_page, $per_page );

		/**
		 * Pagination
		 */
		$this->set_pagination_args( array(
			'total_items' => $this->max_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page )
		) );
	}



	/**
	 * Get Products matching stock criteria
	 */
	function get_items( $current_page, $per_page )
	{
		$query = new AW_Query_Abandoned_Carts();
		$query->set_limit( $per_page );
		$query->set_offset( ($current_page - 1 ) * $per_page );
		$query->set_ordering('last_modified', 'DESC');
		$res = $query->get_results();

		$this->items = $res;

		$this->max_items = $query->found_rows;

	}


	/**
	 * Retrieve the bulk actions
	 */
	function get_bulk_actions()
	{
		$actions = array(
			'bulk_delete' => __( 'Delete', 'automatewoo' ),
		);

		return $actions;
	}

}
