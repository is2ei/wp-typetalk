<?php
/**
 * Integrations Settings Meta Box
 * 
 * @package WP_Typetalk
 * @subpackage Integration
 */

/**
 * Handles Integration Setting Meta Box in Edit Typetalk Integration screen.
 */
class WP_Typetalk_Post_Meta_Box {
    
    /**
     * Plugin's instance
     * 
     * @var WP_Typetalk_Plugin
     */
    private $plugin;

    /**
     * Constructor.
     * 
     * @param WP_Typetalk_Plugin $plugin Plugin instance.
     */
    public function __construct( WP_Typetalk_Plugin $plugin ) {
        $this->plugin = $plugin;

        add_action( sprintf( 'add_meta_boxes_%s', $this->plugin->post_type->name ), array( $this, 'add_meta_box' ) );

        add_action( 'save_post', array( $this, 'save_post' ) );

        // AJAX handler to test sending notification.
        add_action( 'wp_ajax_typetalk_test_notify', array( $this, 'ajax_test_notify' ) );
    }

    /**
     * Add the meta box
     */
    public function add_meta_box() {
        add_meta_box(
            'typetalk_setting_metabox',              // ID
            __( 'Integration Setting', 'typetalk' ), // Title
            array( $this, 'render_meta_box' ),       // Renderer callback
            $this->plugin->post_type->name,          // Screen to render
            'advanced',                              // Context
            'high'                                   // Priority
        );
    }

    /**
     * Display the meta box
     * 
     * @param WP_Post $post Post object
     */
    public function render_meta_box( $post ) {
        wp_nonce_field(
            $this->plugin->post_type->name,            // Action
            $this->plugin->post_type->name . '_nonce'  // Name
        );

        // Get existing setting
        $setting = get_post_meta( $post->ID, 'typetalk_integration_setting', true );

        // Available events
        $events = $this->plugin->event_manager->get_events();

        require_once sprintf( '%sviews/post-meta-box.php', $this->plugin->plugin_path );
    }

    /**
     * Saves data in meta box to post meta
     * 
     * @param int $post_id The ID of the post being saved
     */
    public function save_post( $post_id ) {
        if ( get_post_type( $post_id ) !== $this->plugin->post_type->name ) {
            return;
        }

        // Check nonce.
        if ( empty( $_POST[ $this->plugin->post_type->name . '_nonce' ] ) ) {
            return;
        }

        // Verify nonce.
        if ( ! wp_verify_nonce( $_POST[ $this->plugin->post_type->name . '_nonce' ], $this->plugin->post_type->name ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_POST['typetalk_setting'] ) ) {
            return;
        }

        $events = array_keys( $this->plugin->event_manager->get_events() );
        $fields = array(
            'endpoint_url' => 'esc_url',
            'active'       => function( $val ) {
                if ( $val ) {
                    return true;
                } else {
                    return false;
                }
            },
            'events' => function( $val ) use ( $events ) {
                $saved = array_fill_keys( $events , 0 );

                foreach ( $events as $event ) {
                    if ( ! empty( $val[ $event ] ) ) {
                        $saved[ $event ] = absint( $val[ $event ] );
                    }
                }

                return $saved;
            },
        );

        $cleaned = array();

        $previous_setting = get_post_meta( $post_id, 'typetalk_integration_setting', true );
        foreach ( $fields as $field => $sanitizer ) {
            if ( is_callable( $sanitizer ) ) {
                $cleaned[ $field ] = call_user_func(
                    $sanitizer,
                    ! empty( $_POST['typetalk_setting'][ $field ] ) ? $_POST['typetalk_setting'][ $field ] : null,
                    ! empty( $previous_setting[ $field ] ) ? $previous_setting[ $field ] : null
                );
            }
        }

        update_post_meta( $post_id, 'typetalk_integration_setting', $cleaned );
    }

    /**
     * AJAX handler for test notify in edit Typtalk integration screen
     */
    public function ajax_test_notify() {
        try {
            $expected_params = array(
                'endpoint_url',
                'test_notify_nonce',
            );
            foreach ( $expected_params as $param ) {
                if ( ! isset( $_REQUEST[ $param ] ) ) {
                    /* translators: placeholder is request parameter for test notify AJAX. */
                    throw new Exception( sprintf( __( 'Missing param %s', 'typetalk' ), $param ) );
                }
            }

            if ( ! wp_verify_nonce( $_REQUEST['test_notify_nonce'], 'test_notify_nonce' ) ) {
                throw new Exception( __( 'Malformed value for nonce', 'typetalk' ) );
            }

            $payload = array(
                'endpoint_url' => esc_url( $_REQUEST['endpoint_url'] ),
                'message'      => __( 'Test sending payload!', 'typetalk' ),
            );

            $resp = $this->plugin->notifier->notify( new WP_Typetalk_Event_Payload( $payload ) );

            if ( is_wp_error( $resp ) ) {
                throw new Exception( $resp->get_error_message() );
            }

            $body = trim( wp_remote_retrieve_body( $resp ) );
            if ( 200 !== intval( wp_remote_retrieve_response_code( $resp ) ) ) {
                throw new Exception( $body ); 
            }

            if ( 'ok' !== strtolower( $body ) ) {
                throw new Exception( $body );
            }
            wp_send_json_success();
        } catch ( Exception $e ) {
            $status_code = 500;
            $message = $e->getMessage();

            if ( ! $message ) {
                $message = __( 'Unexpected response', 'typetalk' );
            }

            status_header( 500 );
            wp_send_json_error( $message );
        }
    }
}