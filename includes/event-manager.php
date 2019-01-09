<?php
/**
 * Event Manager
 * 
 * @package WP_Typetalk
 * @subpackage Event
 */

// Block direct access to the file via URL.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dispatch registered events.
 * 
 * Event registered in WP_Typetalk is an array with following structure:
 * 
 * {
 *   @type string   $action      WordPress hook.
 *   @type string   $description Description for the event. Appears in integration setting.
 *   @type bool     $default     Default value for integration setting. True means it's checked by default.
 *   @type function $message     The callback for $action. This function must returns a string to notify to Typetalk.
 * }
 */
class WP_Typetalk_Event_Manager {

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

        $this->dispatch_events();
    }

    /**
     * Dispatch events
     */
    private function dispatch_events() {

        $events = $this->get_events();
        
        // Get all integration settings.
        $integrations = get_posts( array(
            'post_type'      => $this->plugin->post_type->name,
            'nopaging'       => true,
            'posts_per_page' => -1,
        ) );

        foreach ( $integrations as $integration ) {
            $setting = get_post_meta( $integration->ID, 'typetalk_integration_setting', true );

            // Skip if inactive.
            if ( empty( $setting['active'] ) ) {
                continue;
            }
            if ( ! $setting['active'] ) {
                continue;
            }

            if ( empty( $setting['events'] ) ) {
                continue;
            }

            // For each checked event calls the callback, that's, hooking into
            // event's action-name to let notifier deliver notification based on
            // current integration setting.
            foreach ( $setting['events'] as $event => $is_enabled ) {
                if ( ! empty( $events[ $event ] ) && $is_enabled ) {
                    $this->notify_via_action( $events[ $event ], $setting );
                }
            }
        }
    }

    /**
     * Get list of events. There's filter `typetalk_get_events` to extend available
     * events that can be notified to Typetalk.
     * 
     * @return array List of events
     */
    public function get_events() {
        return apply_filters( 'typetalk_get_events', array(
            'post_published'  => array(
                'action'      => 'transition_post_status',
                'description' => __( 'When a post is published', 'tyeptalk' ),
                'default'     => true,
                'message'     => function( $new_status, $old_status, $post ) {
                    $notified_post_types = apply_filters( 'typetalk_event_transition_post_status_post_types', array(
                        'post',
                    ) );

                    if ( ! in_array( $post->post_type, $notified_post_types ) ) {
                        return false;
                    }

                    if ( 'publish' !== $old_status && 'publish' === $new_status ) {
                        $excerpt = has_excerpt( $post->ID ) ?
                            apply_filters( 'get_the_expect', $post->post_expect )
                            :
                            wp_trim_words( strip_shortcodes( $post->post_content ), 55, '&hellip;' );

                        $txt = html_entity_decode( $excerpt, ENT_QUOTES, get_bloginfo( 'charset' ) );
                        if ( strlen( $txt ) > 140 ) {
                            $txt = substr( $txt, 0, 140 ) . '...';
                        }
                        return sprintf(
                            /* translators: 1) post author, 2) post title, and 3) URL. */
                            __( 'New post published by %1$s' . "\n" . '[%2$s](%3$s)', 'typetalk' ) . "\n" .
                            '> %4$s',
                            get_the_author_meta( 'display_name', $post->post_author ),
                            html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
                            get_permalink( $post->ID ),
                            $txt
                        );
                    }
                },
            ),

            'post_pending_review' => array(
                'action'      => 'transition_post_status',
                'description' => __( 'When a post needs review', 'typetalk' ),
                'default'     => false,
                'message'     => function( $new_status, $old_status, $post ) {
                    $notified_post_types = apply_filters( 'typetalk_event_transition_post_status_post_types', array(
                        'post',
                    ) );

                    if ( ! in_array( $post->post_type, $notified_post_types ) ) {
                        return false;
                    }

                    if ( 'pending' !== $old_status && 'pending' === $new_status ) {
                        $excerpt = has_excerpt( $post->ID ) ?
                            apply_filters( 'get_the_excerpt', $post->post_excerpt )
                            :
                            wp_trim_words( strip_shortcodes( $post->post_content ), 55, '&hellip;' );
                        
                        $txt = html_entity_decode( $excerpt, ENT_QUOTES, get_bloginfo( 'charset' ) );
                        if ( strlen( $txt ) > 140 ) {
                            $txt = substr( $txt, 0, 140 ) . '...';
                        }
                        return sprintf(
                            /* translators: 1) post author, 2) post title and 3) URL. */
                            __( 'New post by %1$s needs review' . "\n" . '[%2$s](%3$s)', 'typetalk' ) . "\n" .
                            '> %4$s',
                            get_the_author_meta( 'display_name', $post->post_author ),
                            html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
                            admin_url( sprintf( 'post.php?post=%d&action=edit', $post->ID ) ),
                            $txt
                        );
                    }
                },
            ),

            'new_comment'     => array(
                'action'      => 'wp_insert_comment',
                'priority'    => 999,
                'description' => __( 'When there is a new comment', 'typetalk' ),
                'default'     => false,
                'message'     => function( $comment_id, $comment ) {
                    $comment = is_object( $comment ) ? $comment : get_comment( absint( $comment ) );
                    $post_id = $comment->comment_post_ID;

                    $notified_post_types = apply_filters( 'typetalk_event_wp_insert_comment_post_types', array(
                        'post',
                    ) );

                    if ( ! in_array( get_post_type( $post_id ), $notified_post_types ) ) {
                        return false;
                    }

                    $post_title     = get_the_title( $post_id );
                    $comment_status = wp_get_comment_status( $comment_id );

                    // Ignore spam
                    if ( 'spam' === $comment_status ) {
                        return false;
                    }

                    $txt = preg_replace( "/\n/", "\n>", get_comment_text( $comment_id ) );
                    if ( strlen( $txt ) > 140 ) {
                        $txt = substr( $txt, 0, 140 ) . '...';
                    }
                    return sprintf(
                        /* translators: 1) comment author, 2) edit URL, 3) post title, 4) post URL, and 5) comment status. */
                        __( '[New comment by %1$s](%2$s) on [%3$s](%4$s) (%5$s)', 'typetalk' ) . "\n" .
                        '>%6$s',
                        $comment->comment_author,
                        admin_url( "comment.php?c=$comment_id&action=editcomment" ),
                        html_entity_decode( $post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ),
                        get_permalink( $post_id ),
                        $comment_status,
                        $txt
                    );
                },
            ),
        ) );
    }

    /**
     * Notify Typetalk from invoked action callback
     * 
     * @param array $event   Event
     * @param array $setting Integration setting
     */
    public function notify_via_action( array $event, array $setting ) {
        $priority = 10;
        if ( ! empty( $event['priority'] ) ) {
            $priority = intval( $event['priority'] );
        }

        $callback = $this->get_event_callback( $event, $setting );

        add_action( $event['action'], $callback, $priority, 5 );
    }


    /**
     * Get event callback for a given event and setting.
     * 
     * @param array $event   Event
     * @param array $setting Integration setting
     * 
     * @return function Callback for a given event and setting
     */
    public function get_event_callback( array $event, array $setting ) {
        $notifier = $this->plugin->notifier;

        return function() use ( $event, $setting, $notifier ) {
            $callback_args = array();
            $message = '';

            if ( is_string( $event['message'] ) ) {
                $message = $event['message'];
            } elseif ( is_callable( $event['message'] ) ) {
                $callback_args = func_get_args();
                $message = call_user_func_array( $event['message'], $callback_args );
            }

            if ( ! empty( $message ) ) {
                $setting =wp_parse_args(
                    array(
                        'message' => $message,
                    ),
                    $setting
                );

                $resp = $notifier->notify( new WP_Typetalk_Event_Payload( $setting ) );

                /**
                 * Fires after notify an event to Typetalk.
                 * 
                 * @param array|WP_Error $resp          Results from wp_remote_post
                 * @param array          $event         Event
                 * @param array          $setting       Integration setting
                 * @param array          $callback_args Callback arguments
                 */
                do_action( 'typetalk_after_notify', $resp, $event, $setting, $callback_args );
            }
        };
    }

}