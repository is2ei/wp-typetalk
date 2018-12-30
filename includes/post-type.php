<?php
/**
 * Custom Post Typet for WP_Typetalk
 * 
 * @package WP_Typetalk
 * @subpackage Integration
 */

/**
 * Custom post type where each post stores Typetalk integration settings.
 */
class WP_Typetalk_Post_Type {

  /**
   * Post type name
   * 
   * @var string
   */
  public $name = 'typetalk_integration';

  /**
   * Plugin's instance
   * 
   * @var WP_Typetalk_Plugin
   */
  private $plugin;

    /**
     * Constructor
     * 
     * @param WP_Typetalk_Plugin $plugin Plugin's instance
     */
    public function __construct( WP_Typetalk_Plugin $plugin ) {
      $this->plugin = $plugin;

      // Register custom post type to store Typetalk integration records.
      add_action( 'init', array( $this, 'register_post_type' ) );
      
      // Removes builtin submitdiv meta box.
      add_action( 'admin_menu', array( $this, 'remove_submitdiv' ) );

      // Enqueue scripts/styles and disables autosave for this post type.
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

      // Alters message when post is updated.
      add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

      // Alters message when bulk updating.
      add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

      // Custom bulk actions.
      add_filter( sprintf( 'bulk_actions-edit-%s', $this->name ), array( $this, 'custom_bulk_actions' ) );

      // Custom row actions.
      add_filter( 'post_row_actions', array( $this, 'custom_row_actions' ), 10, 2 );

      // Activate and deactivate actions.
      add_action( 'admin_action_activate',   array( $this, 'activate' ) );
      add_action( 'admin_action_deactivate', array( $this, 'deactivate' ) );

      // Add notices for activate/deactivate actions.
      add_action( 'all_admin_notices', array( $this, 'admin_notices' ) );

      // Custom columns.
      add_filter( sprintf( 'manage_%s_posts_columns', $this->name ), array( $this, 'columns_header' ) );
      add_filter( sprintf( 'manage_%s_posts_custom_column', $this->name ), array( $this, 'custom_column_row' ), 10, 2 );

      // Alter post class in admin to notice whether setting is activated or not.
      add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );

      // Hide sub top navigation.
      add_filter( sprintf( 'views_edit-%s', $this->name ), array( $this, 'hide_subsubsub' ) );

      // Alter title placeholder.
      add_filter( 'enter_title_here', array( $this, 'title_placeholder' ) );
    }

    /**
     * Register the custom post type
     */
    public function register_post_type() {
      $args = array(
        'description'         => '',
        'public'              => false,
        'publicly_queryable'  => false,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'exclude_from_search' => true,

        'show_ui'             => true,
        'show_in_menu'        => true,

        'menu_position'       => 75, // Below tools.
        'menu_icon'           => 'dashicons-admin-plugins',
        'can_export'          => true,
        'delete_with_user'    => true,
        'hierarchical'        => false,
        'has_archive'         => false,
        'query_var'           => false,

        'map_meta_cap' => false,
        'capabilities' => array(

          // Meta caps (don't assign these to roles).
          'edit_post'              => 'manage_options',
          'read_post'              => 'manage_options',
          'delete_post'            => 'manage_options',

          // Primitive/meta caps.
          'create_posts'           => 'manage_options',
          
          // Primitive caps used outside of map_meta_cap().
          'edit_posts'             => 'manage_options',
          'edit_others_posts'      => 'manage_options',
          'publish_posts'          => 'manage_options',
          'read_private_posts'     => 'manage_options',

          // Primitive caps used inside of map_meta_cap().
          'read'                   => 'manage_options',
          'delete_posts'           => 'manage_options',
          'delete_private_posts'   => 'manage_options',
          'delete_published_posts' => 'manage_options',
          'delete_others_posts'    => 'manage_options',
          'edit_private_posts'     => 'manage_options',
          'edit_published_posts'   => 'manage_options',
        ),

        'rewrite' => false,

        // What features the post type supports.
        'supports' => array(
          'title',
        ),

        'labels' => array(
          'name'               => __( 'Typetalk Integration',             'typetalk' ),
          'singular_name'      => __( 'Typetalk Integration',             'typetalk' ),
          'menu_name'          => __( 'Typetalk',                         'typetalk' ),
          'name_admin_bar'     => __( 'Typetalk',                         'typetalk' ),
          'add_new'            => __( 'Add New',                          'typetalk' ),
          'add_new_item'       => __( 'Add New Typetalk Integration',     'typetalk' ),
          'edit_item'          => __( 'Edit Typetalk Integration',        'typetalk' ),
          'new_item'           => __( 'New Typetalk Integration',         'typetalk' ),
          'view_item'          => __( 'View Typetalk Integration',        'typetalk' ),
          'search_items'       => __( 'Search Typetalk Integration',      'typetalk' ),
          'not_found'          => __( 'No Typetalk integration found',    'typetalk' ),
          'not_found_in_trash' => __( 'No Typetalk integration in trash', 'typetalk' ),
          'all_items'          => __( 'Typetalk Integrations',            'typetalk' ),
        ),
      );

      // Register the post type.
      register_post_type( $this->name, $args );
    }

    /**
     * Remove default submit meta box.
     */
    public function remove_submitdiv() {
      remove_meta_box( 'submitdiv', $this->name, 'side' );
    }

    /**
     * Enqueue scripts for Typetalk Integration screens.
     */
    public function enqueue_scripts() {
      if ( get_post_type() === $this->name ) {
        wp_dequeue_script( 'autosave' );

        wp_enqueue_style(
          'typetalk-admin',                                                      // Handle
          sprintf( '%scss/admin.css', $this->plugin->plugin_url ),               // Src
          array(),                                                               // Deps
          filemtime( sprintf( '%scss/admin.css', $this->plugin->plugin_path ) ), // Version
          'all'                                                                  // Media
        );

        wp_enqueue_script(
          'typetalk-admin-js',                                                // Handle
          sprintf( '%sjs/admin.js', $this->plugin->plugin_url ),              // Src
          array( 'jquery' ),                                                  // Deps
          filemtime( sprintf( '%sjs/admin.js', $this->plugin->plugin_path ) ) // Ver
        );
      }
    }

    /**
     * Filter message notice when integration setting is updated.
     * 
     * @param array $messages List of messages when post is update
     * 
     * @return array Updated messages
     */
    public function post_updated_messages( $messages ) {
      $messages[ $this->plugin->post_type->name ] = array_fill( 0, 11,  __( 'Setting updated.', 'typetalk' ) );
      
      return $messages;
    }

    /**
     * Filter message notice when integration settings were bulk-updated.
     * 
     * @param array $bulk_messages Arrays of messages, each keyed by the
     *                             corresponding post type. Messages are keyed with
     *                             'updated', 'locked', 'deleted', 'trashed', and
     *                             'untrashed'.
     * @param array $bulk_counts   Array of item counts for each message, used to
     *                             build internationalized strings.
     * 
     * @return array Updated bulk messages
     */
    public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
      $screen = get_current_screen();

      if ( $this->name === $screen->post_type ) {
        $bulk_messages['post'] = array(
          /* translators: placeholder is number of updated integrations. */
          'updated'   => _n( '%s integration updated.', '%s integrations updated.', $bulk_counts['updated'], 'typetalk' ),
          /* translators: placeholder is number of updated integrations. */
          'locked'    => _n( '%s integration not updated, somebody is editing it.', '%s integrations not updated, somebody is editing them.', $bulk_counts['locked'], 'typetalk' ),
          /* translators: placeholder is number of deleted integrations. */
          'deleted'   => _n( '%s integration permanently deleted.', '%s integrations permanently deleted.', $bulk_counts['deleted'], 'typetalk' ),
          /* translators: placeholder is number of trashed integrations. */
          'trashed'   => _n( '%s integration moved to the Trash.', '%s integrations moved to the Trash.', $bulk_counts['trashed'], 'typetalk' ),
          /* translators: placeholder is number of restored integrations. */
          'untrashed' => _n( '%s integration restored from the Trash.', '%s integrations restored from the Trash.', $bulk_counts['untrashed'], 'typetalk' ),
        );
      }

      return $bulk_messages;
    }

    /**
     * Custom bulk actions
     * 
     * @param  array $actions List of actions
     * @return array List of actions
     * 
     * @filter bulk_actions-edit-typetalk_integration 
     */
    public function custom_bulk_actions( $actions ) {
      unset( $actions['edit'] );

      $actions['activate']   = __( 'Activate',   'typetalk' );
      $actions['deactivate'] = __( 'Deactivate', 'typetalk' );

      return $actions;
    }

    /**
     * Custom row actions for this post type.
     * 
     * @param  array $actions List of actions
     * 
     * @return array List of actions
     * 
     * @filter post_row_actions
     */
    public function custom_row_actions( $actions ) {
      $post = get_post();

      if ( get_post_type( $post ) === $this->plugin->post_type->name ) {
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );

        $setting          = get_post_meta( $post->ID, 'typetalk_integration_setting', true );
        $post_type_object = get_post_type_object( $post->post_type );

        if ( $setting['active'] ) {
          $actions['deactivate'] = sprintf(
            '<a title="%1$s" href="%2$s">%3$s</a>',
            esc_attr__( 'Deactivate this integration setting', 'typetalk' ),
            wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=deactivate', $post->ID ) ), 'deactivate-post_' . $post->ID ),
            esc_html__( 'Deactivate', 'typetalk' )
          );
        } else {
          $actions['activate'] = sprintf(
            '<a title="%1$s" href="%2$s">%3$s</a>',
            esc_attr__( 'Activate this integration setting', 'typetalk' ),
            wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=activate', $post->ID ) ), 'activate-post_' . $post->ID ),
            esc_html__( 'Activate', 'typetalk' )
          );
        }
      }
      
      return $actions;
    }

    /**
     * Activates the integration.
     * 
     * @action admin_action_{action}
     */
    public function activate() {
      $this->_set_active_setting();
    }

    /**
     * Deactivates the integration.
     * 
     * @action admin_action_{action}
     */
    public function deactivate() {
      $this->_set_active_setting( false );
    }

    /**
     * Action handler for activating/deactivating integration setting(s).
     * 
     * @param bool $activate Flag to indicated whether this is activattion action.
     */
    private function _set_active_setting( $activate = true ) {
      $screen = get_current_screen();
      if ( $screen->id !== $this->name ) {
        return;
      }

      $post = ! empty( $_REQUEST['post'] ) ? get_post( $_REQUEST['post'] ) : null;
      if ( ! $post ) {
        wp_die(
          /* translators: placeholder is action type, either 'activate' or 'deactivate'. */
          sprintf( __( 'The integration you are trying to %s is no longer exists.', 'typetalk' ), $activate ? 'activate' : 'deactivate' )
        );
      }

      check_admin_referer( sprintf( '%s-post_%d' , $activate ? 'activate' : 'deactivate', $post->ID ) );

      $sendback = admin_url( 'edit.php?post_type=' . $this->name );
      $setting  = get_post_meta( $post->ID, 'typetalk_integration_setting', true );
      $setting['active'] = $activate;

      update_post_meta( $post->ID, 'typetalk_integration_setting', $setting );

      $key_arg = $activate ? 'activated' : 'deactivated';

      wp_redirect( add_query_arg(
        array(
          "$key_arg" => 1,
          'ids'      => $post->ID,
        ),
        $sendback
      ) );

      exit;
    }

    /**
     * Display notice when integration is activated or deactivated.
     * 
     * @action all_admin_notices
     */
    public function admin_notices() {
      $screen = get_current_screen();
      if ( 'edit-' . $this->name !== $screen->id ) {
        return;
      }

      $bulk_counts = array(
        'activated'   => isset( $_REQUEST['activated'] )   ? absint( $_REQUEST['activated'] )   : 0,
        'deactivated' => isset( $_REQUEST['deactivated'] ) ? absint( $_REQUEST['deactivated'] ) : 0,
      );

      $bulk_messages = array(
        /* translators: number of activated integrations. */
        'activated'   => _n( '%s integration activated.', '%s integrations activated.', $bulk_counts['activated'], 'typetalk' ),
        /* translators: number of deactivated integrations. */
        'deactivated' => _n( '%s integration deactivated.', '%s integrations deactivated.', $bulk_counts['deactivated'], 'typetalk' ),
      );

      $bulk_counts = array_filter( $bulk_counts );

      // If we have a bulk message to display.
      $messages = array();
      foreach ( $bulk_counts as $message => $count ) {
        if ( isset( $bulk_messages[ $message ] ) ) {
          $messages[] = sprintf( $bulk_messages[ $message ], number_format_i18n( $count ) );
        }
      }

      if ( $messages ) {
        echo '<div id="message" class="updated"><p>' . join( ' ', $messages ) . '</p></div>';
      }
    }

    /**
     * Custom columns for this post type.
     * 
     * @param  array $columns Post list columns
     * @return array Post list columns
     * 
     * @filter manage_{post_type}_posts_columns
     */
    public function columns_header( $columns ) {
      unset( $columns['date'] );

      $columns['endpoint_url'] = __( 'Service URL', 'typetalk' );
      $columns['events']       = __( 'Notified Events', 'typetalk' );

      return $columns;
    }

    /**
     * Custom column appears in each row.
     * 
     * @param string $column  Column name
     * @param int    $post_id Post ID
     * 
     * @action manage_{post_type}_posts_custom_column
     */
    public function custom_column_row( $column, $post_id ) {
      $setting = get_post_meta( $post_id, 'typetalk_integration_setting', true );
      switch ( $column ) {
        case 'endpoint_url':
          echo ! empty( $setting['endpoint_url'] ) ? sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $setting['endpoint_url'] ), esc_html( $setting['endpoint_url'] ) ) : '';
          break;
        case 'events':
          $events = $this->plugin->event_manager->get_events();

          if ( ! empty( $setting['events'] ) ) {
            echo '<ul>';
            foreach ( $setting['events'] as $event => $enabled ) {
              if ( $enabled && ! empty( $events[ $event ] ) ) {
                printf( '<li>%s</li>', esc_html( $events[ $event ]['description'] ) );
              }
            }
            echo '</ul>';
          }
          break;
      }
    }

    /**
     * Alter post class in list table to notice whether setting is activated or not.
     * 
     * @param array  $classes An array of post classes
     * @param string $class   A comma-separated list of additional classes added
     *                        to the post.
     * @param int    $post_id The post ID
     * 
     * @return array Array of post classes
     * 
     * @filter post_class
     */
    public function post_class( $classes, $class, $post_id ) {
      if ( ! is_admin() ) {
        return $classes;
      }

      $screen = get_current_screen();
      if ( sprintf( 'edit-%s', $this->name ) !== $screen->id ) {
        return $classes;
      }

      $setting = get_post_meta( $post_id, 'typetalk_integration_setting', true );
      if ( ! $setting['active'] ) {
        $classes[] = 'inactive';
      } else {
        $classes[] = 'active';
      }

      return $classes;
    }

    /**
     * Hides subsubsub top nav.
     * 
     * @return array Top nav links
     */
    public function hide_subsubsub() {
      return array();
    }

    /**
     * Change title placeholder for Typetalk Integration post.
     * 
     * @param string $title Title placeholder.
     * 
     * @return string Updated title placeholder
     */
    public function title_placeholder( $title ) {
      $screen = get_current_screen();

      if ( $this->name == $screen->post_type ) {
        $title = __( 'Integration Name', 'typetalk' );
      }

      return $title;
    }
}