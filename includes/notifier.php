<?php
/**
 * Typetalk Notifier
 * 
 * @package WP_Typetalk
 * @subpackage Notifier
 */

// Block direct access to the file via url.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Notify Typetalk via PostMessage API
 * 
 * @see https://developer.nulab-inc.com/docs/typetalk/api/1/post-message/
 */
class WP_Typetalk_Notifier {
    
    /**
     * Plugin's instance
     * 
     * @var WP_Typetalk_Plugin
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param WP_Typetalk_Plugin $plugin Plugin instance
     */
    public function __construct( WP_Typetalk_Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Notify Typetalk with given payload
     * 
     * @param WP_Typetalk_Event_Payload $payload Payload to send via POST.
     * 
     * @return mixed True if success, otherwise WP_Error
     */
    public function notify( WP_Typetalk_Event_Payload $payload ) {
        $payload_json = $payload->get_json_string();

        $resp = wp_remote_post( $payload->get_url(), array(
            'user-agent' => sprintf( '%s/%s', $this->plugin->name, $this->plugin->version ),
            'body'       => $payload_json,
            'headers'    => array(
                'Content-Type' => 'application/json',
            ),
        ) );
        
        if ( is_wp_error( $resp ) ) {
            return $resp;
        } else {
            $status = intval( wp_remote_retrieve_response_code( $resp ) );
            $message = wp_remote_retrieve_body( $resp );
            if ( 200 !== $status ) {
                return new WP_Error( 'typetalk_unexpected_response', $message );
            }

            return $resp;
        }
    }
}