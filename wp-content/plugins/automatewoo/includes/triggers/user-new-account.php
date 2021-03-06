<?php
/**
 * @class       AW_Trigger_User_New_Account
 * @package     AutomateWoo/Triggers
 * @since       1.0.0
 */

class AW_Trigger_User_New_Account extends AW_Trigger
{
	public $name = 'user_new_account';

	public $group = 'User';

	public $supplied_data_items = [ 'user', 'shop' ];


	function init()
	{
		$this->title = __('New User Account Created', 'automatewoo');

		// Registers the trigger
		parent::init();
	}


	/**
	 * Add options to the trigger
	 */
	function load_fields() {}



	/**
	 * When might this trigger run?
	 */
	function register_hooks()
	{
		add_action( 'user_register', array( $this, 'catch_hooks' ) );
	}


	/**
	 * Route hooks through here
	 * @param int $user_id
	 */
	function catch_hooks( $user_id )
	{
		if ( get_user_meta( $user_id, '_aw_user_registered', true ) ) return;

		add_user_meta( $user_id, '_aw_user_registered', true );

		$user = get_user_by( 'id', $user_id );

		$this->maybe_run(array(
			'user' => $user
		));
	}


	/**
	 * @param $workflow AW_Model_Workflow
	 * @return bool
	 */
	function validate_workflow( $workflow )
	{
		if ( ! $user = $workflow->get_data_item('user') )
			return false;

		return true;
	}

}
