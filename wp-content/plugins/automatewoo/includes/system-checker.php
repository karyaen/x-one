<?php
/**
 * @class 		AW_System_Checker
 * @package		AutomateWoo
 * @since 		2.3
 */

class AW_System_Checker
{
	/** @var array */
	private $system_checks = array(
		'cron_running' => 'AW_System_Check_Cron_Running'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		add_action( 'admin_init', array( $this, 'maybe_background_check' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_notices' ) );
	}


	/**
	 * @return array
	 */
	function get_checks()
	{
		return apply_filters( 'automatewoo_system_checks', $this->system_checks, $this );
	}


	/**
	 * Maybe background check for high priority issues
	 */
	function maybe_background_check()
	{
		if ( get_transient('automatewoo_background_system_check') || ! AW()->options()->enable_background_system_check ) return;

		foreach( $this->get_checks() as $check )
		{
			/** @var AW_System_Check $check */
			$check = new $check();

			if ( ! $check->high_priority )
				continue;

			$response = $check->run();

			if ( $response['success'] == false )
			{
				set_transient( 'automatewoo_background_system_check_errors', true, DAY_IN_SECONDS );
				continue;
			}
		}

		set_transient( 'automatewoo_background_system_check', true, DAY_IN_SECONDS * 4 );
	}


	/**
	 *
	 */
	function maybe_display_notices()
	{
		if ( ! get_transient('automatewoo_background_system_check_errors') || ! current_user_can('manage_woocommerce') ) return;

		$strong = __( 'AutomateWoo system check has found issues.', 'automatewoo' );
		$more = sprintf( __( '<a href="%s">View details</a>', 'automatewoo' ), AW()->admin->page_url( 'system-check' ) );

		AW()->admin->notice('error is-dismissible', $strong, $more, 'aw-notice-system-error' );
	}


}
