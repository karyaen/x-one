<?php
/**
 * @class AW_Integrations
 */

class AW_Integrations
{
	/** @var AW_Integration_Mailchimp */
	private $mailchimp;

	/** @var AW_Integration_ActiveCampaign */
	private $activecampaign;


	/**
	 * @return bool
	 */
	function is_wpml()
	{
		return class_exists('SitePress');
	}


	/**
	 * @return bool
	 */
	function is_woo_pos()
	{
		return class_exists('WC_POS');
	}


	/**
	 * @return bool
	 */
	function subscriptions_enabled()
	{
		if ( ! class_exists( 'WC_Subscriptions' ) ) return false;
		if ( WC_Subscriptions::$version < '2.0.0' ) return false;
		return true;
	}



	function load_twilio()
	{
		if ( ! function_exists( 'Services_Twilio_autoload' ) )
		{
			require_once AW()->lib_path( '/twilio-php/Services/Twilio.php' );
		}
	}


	function load_campaignmonitor()
	{
		if ( ! class_exists( 'CS_REST_Subscribers' ) )
		{
			require_once AW()->lib_path( '/campaignmonitor-createsend-php/csrest_subscribers.php' );
		}
	}


	function load_madmimi()
	{
		if ( ! class_exists( 'MadMimi' ) )
		{
			require_once AW()->lib_path( '/madmimi-php/MadMimi.class.php' );
		}
	}


	/**
	 * @return AW_Integration_Mailchimp
	 */
	function mailchimp()
	{
		if ( ! isset( $this->mailchimp ) )
		{
			$this->mailchimp = new AW_Integration_Mailchimp( AW()->options()->mailchimp_api_key );
		}

		return $this->mailchimp;
	}


	/**
	 * @return AW_Integration_ActiveCampaign
	 */
	function activecampaign()
	{
		if ( ! isset( $this->activecampaign ) )
		{
			$api_url = AW()->options()->active_campaign_api_url;
			$api_key = AW()->options()->active_campaign_api_key;

			$this->activecampaign = new AW_Integration_ActiveCampaign( $api_url, $api_key );
		}

		return $this->activecampaign;
	}


}


