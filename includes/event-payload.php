<?php
/**
 * Event Payload
 * 
 * @package WP_Typetalk
 * @subpackage Event
 */

 // Block direct access to the file via url.
 if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Event payload representation.
 */
class WP_Typetalk_Event_Payload {

    /**
	 * Setting.
	 *
	 * @var array
	 */
    private $setting;
    
	/**
	 * Constructor.
	 *
	 * @param array $setting Setting values.
	 */
	public function __construct( array $setting ) {
		$this->setting = $setting;
    }

	/**
	 * Get endpoint URL.
	 *
	 * @return string Endpoint URL
	 */
	public function get_url() {
		return $this->setting['endpoint_url'];
    }
    
	/**
	 * Get JSON string for the setting.
	 *
	 * @return string JSON string
	 */
	public function get_json_string() {
		return json_encode( array(
			'message' => $this->setting['message'],
		) );
	}
}