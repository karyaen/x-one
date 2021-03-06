<?php
/**
 * @package     AutomateWoo/Models
 * @class       AW_Model_Workflow
 * @since       1.0.0
 */
class AW_Model_Workflow
{
	/** @var int */
	public $id;

	/** @var WP_Post */
	public $post;

	/** @var string */
	public $title;

	/** @var AW_Trigger */
	public $trigger;

	/** @var array */
	public $actions;

	/** @var array */
	public $data_items;

	/** @var bool */
	public $actions_loaded;

	/** @var array */
	private $options;

	/** @var array */
	private $trigger_options;

	/** @var array */
	private $rule_options;

	/** @var AW_Variables_Processor */
	private $variable_processor;

	/** @var AW_Model_Log */
	public $log;

	/** @var bool */
	public $exists = false;

	/** @var bool */
	public $preview_mode = false;

	/** @var bool */
	public $test_mode = false;


	/**
	 * @param $post mixed (object or post ID)
	 */
	function __construct( $post )
	{
		if ( ! $post instanceof WP_Post )
		{
			// Get from id
			$post = get_post($post);
		}

		// workflow doesn't exists
		if ( ! $post )
			return;

		$this->exists = true;
		$this->post = $post;
		$this->id = $post->ID;
		$this->title = $post->post_title;
	}


	/**
	 * Get trigger
	 */
	function load_trigger()
	{
		$trigger_name = $this->get_meta( 'trigger_name' );

		if ( ! $trigger_name )
		{
			$this->trigger = false;
			return;
		}

		if ( ! AW()->get_registered_trigger($trigger_name) )
			return;

		// @todo clone triggers just to retrieve options now seems a little confusing and inefficient
		$this->trigger = clone AW()->get_registered_trigger( $trigger_name );
		$this->trigger->set_options( $this->get_trigger_options() );
	}


	/**
	 * Get actions names from meta
	 */
	function load_actions()
	{
		if ( $this->actions_loaded )
			return;

		$this->actions_loaded = true;

		$actions_data = $this->get_meta( 'actions' );

		if ( ! $actions_data || ! is_array( $actions_data ) )
		{
			$this->actions = false;
			return;
		}


		$n = 1;
		foreach ( $actions_data as $action )
		{
			if ( ! isset( $action['action_name'] ) )
				continue;

			if ( ! AW()->get_registered_action( $action['action_name'] ) )
				continue;

			// Create an AW_Action object from the stored data
			$action_obj = clone AW()->get_registered_action( $action['action_name'] );
			$action_obj->set_options( $action );
			$this->actions[$n] = $action_obj;
			$n++;
		}
	}


	/**
	 * @return AW_Variables_Processor
	 */
	function variable_processor()
	{
		if ( ! isset( $this->variable_processor ) )
		{
			$this->variable_processor = new AW_Variables_Processor( $this );
		}

		return $this->variable_processor;
	}


	/**
	 * Returns the trigger that caused this rule to run
	 *
	 * @return AW_Trigger|false
	 */
	function get_trigger()
	{
		if ( ! isset( $this->trigger ) )
		{
			$this->load_trigger();
		}

		return $this->trigger;
	}


	/**
	 * Returns the saved actions with their data
	 *
	 * @return object
	 */
	function get_actions()
	{
		if ( ! $this->actions_loaded )
			$this->load_actions();

		return $this->actions;
	}


	/**
	 * Returns the saved actions with their data
	 *
	 * @param $number
	 *
	 * @return object
	 */
	function get_action( $number )
	{
		if ( ! $this->actions_loaded )
			$this->load_actions();

		if ( ! isset( $this->actions[$number] ) )
			return false;

		return $this->actions[$number];
	}


	/**
	 * @param $data_items
	 */
	function maybe_run( $data_items )
	{
		$this->set_data_items( $data_items );

		if ( $this->validate_workflow() )
		{
			$this->run();
		}
	}


	/**
	 * @return bool
	 */
	function validate_workflow()
	{
		if ( ! $trigger = $this->get_trigger() )
			return false;

		if ( ! $trigger->validate_workflow_language( $this ) )
			return false;

		if ( ! $trigger->validate_workflow( $this ) )
			return false;

		if ( ! $this->validate_rules() )
			return false;

		if ( ! apply_filters( 'automatewoo_custom_validate_workflow', true, $this ) )
			return false;

		return true;
	}


	/**
	 * @return bool
	 */
	function validate_rules()
	{
		$rule_options = $this->get_rule_options();

		// no rules exists
		if ( empty( $rule_options ) )
			return true;

		foreach ( $rule_options as $rule_group )
		{
			$is_group_valid = true;

			foreach ( $rule_group as $rule )
			{
				// rules have AND relationship so all must return true
				if ( ! $this->validate_rule( $rule ) )
				{
					$is_group_valid = false;
					break;
				}
			}

			// groups have an OR relationship so if one is valid we can break the loop and return true
			if ( $is_group_valid )
				return true;
		}

		// no groups were valid
		return false;
	}


	/**
	 * Returns true if rule is missing data so that the rule is skipped
	 *
	 *
	 *
	 * @param array $rule
	 * @return bool
	 */
	function validate_rule( $rule )
	{
		if ( ! is_array( $rule ) )
			return true;

		$rule_name = isset( $rule['name'] ) ? $rule['name'] : false;
		$rule_compare = isset( $rule['compare'] ) ? $rule['compare'] : false;
		$rule_value = isset( $rule['value'] ) ? $rule['value'] : false;

		// its ok for compare to be false for boolean type rules
		if ( ! $rule_name || ! $rule_value )
			return true;

		$rule_object = AW()->rules()->get_rule( $rule_name );

		// get the data required to validate the rule
		$data_item = $this->get_data_item( $rule_object->data_item );

		if ( ! $data_item )
			return false;

		// some rules need the full workflow object
		$rule_object->set_workflow( $this );

		return $rule_object->validate( $data_item, $rule_compare, $rule_value );
	}



	/**
	 * Will run or queue the workflow based on its options
	 *
	 * @param bool $from_queue
	 *
	 * @return bool
	 */
	function run( $from_queue = false )
	{
		$when_to_run = $this->get_option( 'when_to_run' );

		// Run now?
		if ( $when_to_run == 'immediately' || $from_queue )
		{
			if ( defined( 'AW_PREVENT_WORKFLOWS' ) && AW_PREVENT_WORKFLOWS )
			{
				$log = new WC_Logger();
				$log->add( 'automatewoo-prevented-workflows', $this->title );
				return false;
			}

			if ( $from_queue )
			{
				if ( ! $trigger = $this->get_trigger() )
					return false;

				if ( ! $trigger->validate_before_queued_event( $this ) )
					return false;

				if ( ! $this->validate_rules() )
					return false;
			}


			// Run now

			// Create log
			$this->create_run_log();

			if ( $this->get_actions() )
			{
				foreach ( $this->get_actions() as $action )
				{
					$action->workflow = $this;

					do_action('automatewoo_before_action_run', $action, $this );

					$action->run();

					do_action('automatewoo_after_action_run', $action, $this );

				}
			}

			do_action( 'automatewoo_after_workflow_run', $this );

		}
		else
		{
			// Add to queue

			$queue = new AW_Model_Queued_Event();
			$queue->set_data_layer( $this->data_items );
			$queue->workflow_id = $this->id;

			if ( $when_to_run == 'delayed' )
			{
				$date = $queue->calculate_delay( absint( $this->get_option('run_delay_value') ), $this->get_option('run_delay_unit') );
			}
			elseif ( $when_to_run == 'datetime' )
			{
				$datetime = $this->get_option('queue_datetime', true );
				if ( $datetime )
				{
					$timestamp = strtotime( $this->get_option('queue_datetime', true ), current_time('timestamp' ) );

					$date = new DateTime();
					$date->setTimestamp($timestamp);

					// convert to UTC
					$utc_date_string = get_gmt_from_date( $date->format('Y-m-d H:i:s') );
					$date = new DateTime( $utc_date_string );
				}
				else
				{
					// if datetime is empty do not queue the item
				}
			}


			if ( ! empty( $date ) )
			{
				$queue->set_date( $date );
				$queue->save();
			}

		}

		return true;
	}



	/**
	 * Record that the workflow has been run
	 */
	function create_run_log()
	{
		// Create log
		$this->log = new AW_Model_Log();
		$this->log->set_workflow( $this );
		$this->log->date = current_time( 'mysql', true );

		if ( $this->get_option('click_tracking') )
		{
			$this->log->tracking_enabled = true;

			if ( $this->get_option('conversion_tracking') )
			{
				$this->log->conversion_tracking_enabled = true;
			}
		}

		$this->log->save();
		$this->log->store_data_layer();

		do_action( 'automatewoo_create_run_log', $this->log, $this );
	}



	/**
	 * @return int
	 */
	function get_times_run()
	{
		$cache_key = 'times_run/workflow=' . $this->id;
		$cache = AW()->cache()->get( $cache_key );

		if ( $cache !== false )
			return (int) $cache;

		$log_query = ( new AW_Query_Logs() )
			->where( 'workflow_id', $this->id );

		$results = $log_query->get_results();

		$count = $results ? count($results) : 0;

		AW()->cache()->set( $cache_key, $count, 720 );

		return (int) $count;
	}


	/**
	 * @param bool $try_cache
	 * @return int
	 */
	function get_current_queue_count( $try_cache = true )
	{
		$cache_key = 'current_queue_count/workflow=' . $this->id;
		$cache = AW()->cache()->get( $cache_key );

		if ( $try_cache && $cache !== false )
		{
			return $cache;
		}
		else
		{
			$query = ( new AW_Query_Queue() )
				->where( 'workflow_id', $this->id );

			$results = $query->get_results();
			$count = $results ? count( $results ) : '-';

			AW()->cache()->set( $cache_key, $count, 720 );

			return $count;
		}
	}


	/**
	 * @return array
	 */
	function get_options()
	{
		if ( ! isset( $this->options ) )
		{
			$this->options = $this->get_meta( 'workflow_options' );

			if ( ! $this->options )
			{
				$this->options = [];
			}
		}

		return $this->options;
	}


	/**
	 * @param string $name
	 * @param bool $replace_vars
	 *
	 * @return mixed
	 */
	function get_option( $name, $replace_vars = false )
	{
		$this->get_options(); // ensure options are loaded

		if ( ! isset( $this->options[$name] ) )
			return false;

		if ( $replace_vars )
		{
			return $this->variable_processor()->process_field( $this->options[$name] );
		}

		return apply_filters( 'automatewoo/workflow/option', $this->options[$name], $name, $this );
	}


	/**
	 * @return array
	 */
	function get_trigger_options()
	{
		if ( ! isset( $this->trigger_options ) )
		{
			$this->trigger_options = $this->get_meta( 'trigger_options' );

			if ( ! $this->trigger_options )
			{
				$this->trigger_options = [];
			}
		}

		return $this->trigger_options;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	function get_trigger_option( $name )
	{
		$this->get_trigger_options(); // ensure options are loaded

		if ( ! isset( $this->trigger_options[$name] ) )
			return false;

		return apply_filters( 'automatewoo_trigger_option', $this->trigger_options[$name], $name, $this );
	}


	/**
	 * @param array $rule_options
	 */
	function set_rule_options( $rule_options )
	{
		if ( ! is_array( $rule_options ) )
			return;

		$this->rule_options = $rule_options;
		update_post_meta( $this->id, 'rule_options', $rule_options );
	}


	/**
	 * @return array
	 */
	function get_rule_options()
	{
		if ( ! isset( $this->rule_options ) )
		{
			$this->rule_options = $this->get_meta( 'rule_options' );

			if ( ! $this->rule_options )
			{
				$this->rule_options = [];
			}
		}

		return $this->rule_options;
	}



	/**
	 * @param $user_id
	 *
	 * Store time unsubscribed for tracking purposes
	 *
	 * @return bool
	 */
	function unsubscribe_user( $user_id )
	{
		if ( ! $user_id )
			return false;

		$unsubscribe = new AW_Model_Unsubscribe();
		$unsubscribe->user_id = $user_id;
		$unsubscribe->workflow_id = $this->id;
		$unsubscribe->date = current_time( 'mysql', true );
		$unsubscribe->save();

		return true;
	}


	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	function is_user_unsubscribed( $user_id )
	{
		$unsubscribe_query = ( new AW_Query_Unsubscribes() )
			->set_limit(1)
			->where( 'workflow_id', $this->id )
			->where( 'user_id', $user_id );

		return $unsubscribe_query->get_results();
	}



	/**
	 * @param $user WP_User or guest user
	 *
	 * @return bool
	 */
	function is_first_run_for_user( $user )
	{
		return $this->get_times_run_for_user( $user ) === 0;
	}


	/**
	 * Counts items in log and in queue for this user and workflow
	 *
	 * @param $user WP_User or AW_Model_Guest_Order
	 *
	 * @return int
	 */
	function get_times_run_for_user( $user )
	{
		$log_query = ( new AW_Query_Logs() )
			->where( 'workflow_id', $this->id );

		if ( $user->ID === 0 ) // guest user
		{
			$log_query->where( 'guest_email', $user->user_email );
		}
		else
		{
			$log_query->where( 'user_id', $user->ID );
		}

		if ( $results = $log_query->get_results() )
			return count($results);

		return 0;
	}



	/**
	 * Counts items in log and in queue for this guest and workflow
	 *
	 * @param $guest AW_Model_Guest
	 *
	 * @return int
	 */
	function get_times_run_for_guest( $guest )
	{
		$log_query = ( new AW_Query_Logs() )
			->where( 'workflow_id', $this->id )
			->where( 'guest_email', $guest->email );

		if ( $results = $log_query->get_results() )
			return count($results);

		return 0;
	}



	/**
	 * @param $name
	 * @param $item
	 * @deprecated
	 */
	function add_data_item( $name, $item )
	{
		$this->set_data_item( $name, $item );
	}


	/**
	 * @param $name
	 * @param $item
	 */
	function set_data_item( $name, $item )
	{
		$this->data_items[$name] = $item;
	}



	/**
	 * @param $data_items
	 */
	function set_data_items( $data_items )
	{
		if ( is_array( $data_items ) )
			$this->data_items = $data_items;
	}


	/**
	 * Returns unvalidated data layer
	 * @return array
	 */
	function get_data_layer()
	{
		return $this->data_items ? $this->data_items : [];
	}


	/**
	 * Retrieve and validate a data item
	 *
	 * @param $name string
	 *
	 * @return mixed
	 */
	function get_data_item( $name )
	{
		if ( ! isset( $this->data_items[$name] ) )
			return false;

		$item = $this->data_items[$name];

		return aw_validate_data_item( $name, $item );
	}


	/**
	 * @return bool
	 */
	function is_active()
	{
		if ( ! $this->exists ) return false;

		return $this->post->post_status == 'publish';
	}


	/**
	 * @return bool
	 */
	function is_tracking_enabled()
	{
		return $this->get_option('click_tracking');
	}


	/**
	 * @return bool
	 */
	function is_ga_tracking_enabled()
	{
		return ( $this->is_tracking_enabled() && $this->get_option('ga_link_tracking') ) ;
	}


	/**
	 * @param string $url
	 * @return string
	 */
	function append_ga_tracking_to_url( $url )
	{
		if ( empty( $url ) || ! $this->is_ga_tracking_enabled() )
			return $url;

		$url .=  strstr( $url, '?' ) ? '&' : '?';
		$url .= $this->get_option('ga_link_tracking');

		return $url;
	}


	/**
	 * @return false|string
	 */
	function get_language()
	{
		if ( AW()->integrations()->is_wpml() )
		{
			$info = wpml_get_language_information( null, $this->id );
			if ( is_array( $info ) )
				return $info['language_code'];
		}
	}


	/**
	 * Return array with all versions of this workflow including the original
	 * @return array
	 */
	function get_translation_ids()
	{
		if ( ! AW()->integrations()->is_wpml() )
		{
			return array( $this->id );
		}

		global $sitepress;

		$ids = array();

		$translations = $sitepress->get_element_translations( $this->id, 'post_post', false, true );

		if ( is_array($translations) ) foreach ( $translations as $translation )
		{
			$ids[] = $translation->element_id;
		}

		return $ids;
	}


	/**
	 * @param $key
	 * @param bool $single
	 * @return mixed
	 */
	function get_meta( $key, $single = true )
	{
		return get_post_meta( $this->id, $key, $single );
	}


	/**
	 * @param $key
	 * @param $value
	 * @return bool|int
	 */
	function update_meta( $key, $value )
	{
		return update_post_meta( $this->id, $key, $value );
	}


	/**
	 *
	 */
	function enable_preview_mode()
	{
		$this->preview_mode = true;

		$this->set_data_items( AW_Preview_Data::get_preview_data_layer() );
	}


	/**
	 *
	 */
	function enable_test_mode()
	{
		$this->test_mode = true;

		// todo using log #1 is probably not the best idea
		$this->log = new AW_Model_Log();
		$this->log->id = 1;

		$this->set_data_items( AW_Preview_Data::get_preview_data_layer() );
	}


}
