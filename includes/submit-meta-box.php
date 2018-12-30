<?php
/**
 * Submit Meta Box
 * 
 * @package WP_Typetalk
 * @subpackage Integration
 */

/**
 * Replacement for builtin submitdiv meta box for Typetalk integration CPT.
 */
class WP_Typetalk_Submit_Meta_Box {

    /**
     * Plugin's instance
     * 
     * @var WP_Typetalk_Plugin
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param WP_Typetalk_Plugin $plugin Plugin's instance.
     */
    public function __construct( WP_Typetalk_Plugin $plugin ) {
        $this->plugin = $plugin;

        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
    }

    /**
     * Register submit meta box
     * 
     * @param string $post_type Post type
     */
    public function register_meta_box( $post_type ) {
        if ( $this->plugin->post_type->name === $post_type ) {
            add_meta_box( 'typetalk_submitdiv', __( 'Save Setting', 'typetalk' ), array( $this, 'typetalk_submitdiv' ), null, 'side', 'core' );
        }
    }

    /**
     * Display post submit form fields
     * 
     * @param WP_Post $post Post object
     */
    public function typetalk_submitdiv( $post ) {
        require_once sprintf( '%sviews/submit-meta-box.php', $this->plugin->plugin_path );
    }
}