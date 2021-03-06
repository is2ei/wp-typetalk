<?php
/**
 * Class EventManagerTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

/**
 * Test for event-manager.php
 */
class EventManagerTest extends WP_UnitTestCase {

    /**
     * Test setUp
     * 
     * Creates typetalk integration posts.
     */
    public function setUp() {
        $post_id = wp_insert_post( array(
            'post_title'  => 'Post Title',
            'post_status' => 'publish',
            'post_type'   => 'typetalk_integration',
        ) );

        $this->setting = array(
            'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
            'active'       => true,
            'events'       => array(
                'post_published'      => 1,
                'post_pending_review' => 1,
                'new_comment'         => 1,
            ),
        );

        add_post_meta( $post_id, 'typetalk_integration_setting', $this->setting );

        parent::setUp();
    }

    /**
     * Test WP_Typetalk_Event_Manager::dispatch_events() for post_published.
     * 
     * Test published post will makes HTTP request to Typetalk. To be able to test
     * this, wp_remote_request is short-circuited via `pre_http_request`. This
     * would allow the test to mock the HTTP response. Once HTTP response is
     * filtered, the action can be triggered by creating a post. The assertion
     * of expected outcome can be done in `typetalk_after_notify` action.
     */
    public function test_dispatch_event_post_published() {
        new WP_Typetalk_Event_Manager( $GLOBALS['wp_typetalk'] );

        // Test successful response.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_success' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_succeed' ), 10, 4 );
        $this->_create_post();
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_success' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_succeed' ), 10, 4 );

        // Test response with WP_Error.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_wp_error' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_error' ), 10, 4 );
        $this->_create_post();
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_wp_error' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_error' ), 10, 4 );

        // Test non-200 status returns WP_Error.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_400' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_with_bad_request' ), 10, 4 );
        $this->_create_post();
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_400' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_post_published_with_bad_request' ), 10, 4 );
    }

    /**
     * Creates a new post to trigger the hook.
     * 
     * @return int Post ID
     */
    protected function _create_post() {
        return wp_insert_post( array(
            'post_title'  => 'Test post',
            'post_status' => 'publish',
            'post_author' => 1,
        ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response is OK.
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_post_published_succeed( $resp, $event, $setting, $args ) {
        // Assert the callback args for transition_post_status hook.
        $this->assertEquals( 3, sizeof( $args ) );
        $this->assertEquals( 'publish', $args[0] );
        $this->assertEquals( 'new', $args[1] );
        $this->assertTrue( is_a( $args[2], 'WP_Post' ) );

        // Assert the message to notify to Typetalk when a post is published.
        $post    = $args[2];
        $message = $this->_get_new_published_post_message( $post );
        $this->assertEquals( $message, $setting['message'] );

        // Assert the Typetalk setting for incoming webhook.
        $expect_setting         = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'transition_post_status',
            'description' => 'When a post is published',
            'default'     => true,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertEquals( 200, wp_remote_retrieve_response_code( $resp ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response is WP_Error
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_post_published_error( $resp, $event, $setting, $args ) {
        // Assert the callback args for transition_post_status hook.
        $this->assertEquals( 3, sizeof( $args ) );
        $this->assertEquals( 'publish', $args[0] );
        $this->assertEquals( 'new', $args[1] );
        $this->assertTrue( is_a( $args[2], 'WP_Post' ) );

        // Assert the message to notify to Typetalk when a post is published.
        $post    = $args[2];
        $message = $this->_get_new_published_post_message( $post );
        $this->assertEquals( $message, $setting['message'] );

        // Assert the Typetalk setting for incoming webhook.
        $expect_setting         = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'transition_post_status',
            'description' => 'When a post is published',
            'default'     => true,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertTrue( is_wp_error( $resp ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response has status code 400.
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_post_published_with_bad_request( $resp, $event, $setting, $args ) {
        // Assert the callback args for transition_post_status hook.
        $this->assertEquals( 3, sizeof( $args ) );
        $this->assertEquals( 'publish', $args[0] );
        $this->assertEquals( 'new', $args[1] );
        $this->assertTrue( is_a( $args[2], 'WP_Post' ) );

        // Assert the message to notify to Typetalk when a post is published.
        $post    = $args[2];
        $message = $this->_get_new_published_post_message( $post );
        $this->assertEquals( $message, $setting['message'] );

        // Assert the Typetalk setting for incoming webhook.
        $expect_setting         = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'transition_post_status',
            'description' => 'When a post is published',
            'default'     => true,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertTrue( is_wp_error( $resp ) );
        $this->assertEquals( 'typetalk_unexpected_response', $resp->get_error_code() );
    }

    /**
     * Get notified Typetalk message when a new post is published.
     * 
     * @param WP_Post $post Post object
     * 
     * @return string Message
     */
    protected function _get_new_published_post_message( $post ) {
        $excerpt = has_excerpt( $post->ID ) ?
            apply_filters( 'get_the_excerpt', $post->post_excerpt )
            :
            wp_trim_words( strip_shortcodes( $post->post_content ), 55, '&hellip;' );

        return sprintf(
            'New post published by %1$s' . "\n" . '[%2$s](%3$s)' . "\n" .
            '> %4$s',
            get_the_author_meta( 'display_name', $post->post_author ),
            html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
            get_permalink( $post->ID ),
            html_entity_decode( $excerpt, ENT_QUOTES, get_bloginfo( 'charset' ) )
        );
    }

    /**
     * Test WP_Typetalk_Event_Manager::dispatch_events() for new comment.
     * 
     * Test new comment will makes HTTP request to Typetalk. To be able to test
     * this, wp_remote_request is short-circuited via `pre_http_request`. This
     * would allow the test to mock the HTTP response. Once HTTP response is
     * filtered, the action can be triggered by creating a comment. The assertion
     * of expected outcome can be done in `typetalk_after_notify` action.
     */
    public function test_dispatch_event_new_comment() {
        $post_id = $this->_create_post();
        new WP_Typetalk_Event_Manager( $GLOBALS['wp_typetalk'] );

        // Test successful response.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_success' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_succeed' ), 10, 4 );
        $this->_create_comment( $post_id );
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_success' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_succeed' ), 10, 4 );

        // Test response with WP_Error.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_wp_error' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_error' ), 10, 4 );
        $this->_create_comment( $post_id );
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_wp_error' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_error' ), 10, 4 );

        // Test non-200 status returns WP_Error.
        add_filter( 'pre_http_request', array( $this, 'pre_http_resp_400' ) );
        add_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_with_bad_request' ), 10, 4 );
        $this->_create_comment( $post_id );
        remove_filter( 'pre_http_request', array( $this, 'pre_http_resp_400' ) );
        remove_action( 'typetalk_after_notify', array( $this, 'after_notify_new_comment_with_bad_request' ), 10, 4 );
    }

    /**
     * Creates a new comment to trigger the hook.
     * 
     * @param int $post_id Post ID
     * 
     * @return int Comment ID
     */
    protected function _create_comment( $post_id ) {
        return wp_insert_comment( array(
            'comment_post_ID'  => $post_id,
            'comment_author'   => 1,
            'comment_content'  => 'Test comment',
            'comment_approved' => 1,
        ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response is OK.
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_new_comment_succeed( $resp, $event, $setting, $args ) {
        // Assert the callback args for wp_insert_comment hook.
        $this->assertEquals( 2, sizeof( $args ) );
        $this->assertTrue( is_numeric( $args[0] ) );
        $this->assertTrue( is_a( $args[1], 'WP_Comment' ) );

        // Assert the message to notify to Typetalk when a comment is added.
        $comment = $args[1];
        $message = $this->_get_new_comment_message( $comment );
        $this->assertEquals( $message, $setting['message'] );

        // Assert Typetalk setting for incoming webhook.
        $expect_setting            = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'wp_insert_comment',
            'description' => 'When there is a new comment',
            'default'     => false,
            'priority'    => 999,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertEquals( 200, wp_remote_retrieve_response_code( $resp ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response is WP_Error.
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_new_comment_error( $resp, $event, $setting, $args ) {
        // Assert the callback args for wp_insert_comment hook.
        $this->assertEquals( 2, sizeof( $args ) );
        $this->assertTrue( is_numeric( $args[0] ) );
        $this->assertTrue( is_a( $args[1], 'WP_Comment' ) );

        // Assert the message to notify to Typetalk when a comment is added.
        $comment = $args[1];
        $message = $this->_get_new_comment_message( $comment );
        $this->assertEquals( $message, $setting['message'] );
        
        // Assert the Typetalk setting for incoming webhook.
        $expect_setting            = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'wp_insert_comment',
            'description' => 'When there is a new comment',
            'default'     => false,
            'priority'    => 999,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertTrue( is_wp_error( $resp ) );
    }

    /**
     * Callback for `typetalk_after_notify` to assert the results when response has status code 400.
     * 
     * @param array $resp    HTTP response
     * @param array $event   Event
     * @param array $setting Integration setting
     * @param array $args    Callback args
     */
    public function after_notify_new_comment_with_bad_request( $resp, $event, $setting, $args ) {
        // Assert the callback args for wp_insert_comment hook.
        $this->assertEquals( 2, sizeof( $args ) );
        $this->assertTrue( is_numeric( $args[0] ) );
        $this->assertTrue( is_a( $args[1], 'WP_Comment' ) );

        // Assert the message to notify to Typetalk when a comment is added.
        $comment = $args[1];
        $message = $this->_get_new_comment_message( $comment );
        $this->assertEquals( $message, $setting['message'] );

        // Assert the Typetalk setting for incoming webhook.
        $expect_setting            = $this->setting;
        $expect_setting['message'] = $message;
        $this->assertEquals( $expect_setting, $setting );

        // Assert the event occurred in WP.
        $expect_event = array(
            'action'      => 'wp_insert_comment',
            'description' => 'When there is a new comment',
            'default'     => false,
            'priority'    => 999,
        );
        unset( $event['message'] );
        $this->assertEquals( $expect_event, $event );

        // Assert the HTTP response.
        $this->assertTrue( is_wp_error( $resp ) );
        $this->assertEquals( 'typetalk_unexpected_response', $resp->get_error_code() );
    }

    /**
     * Get notified Typetalk message when a new comment is added.
     * 
     * @param WP_Comment $comment Comment object
     * 
     * @return string Message
     */
    protected function _get_new_comment_message( $comment ) {
        return sprintf(
            '[New comment by %1$s](%2$s) on [%3$s](%4$s) (%5$s)' . "\n" .
            '>%6$s',
            $comment->comment_author,
            admin_url( "comment.php?c=$comment->comment_ID&action=editcomment" ),
            html_entity_decode( get_the_title( $comment->comment_post_ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
            get_permalink( $comment->comment_post_ID ),
            wp_get_comment_status( $comment->comment_ID ),
            preg_replace( "/\n/", "\n>", get_comment_text( $comment->comment_ID ) )
        );
    }

    /**
     * Shortcircuit wp_remote_post to returns success response
     * 
     * @return array HTTP response
     */
    public function pre_http_resp_success() {
        return array(
            'response' => array(
                'code'    => 200,
                'message' => 'OK',
            ),
        );
    }

    /**
     *  Shortcircuit wp_remote_post to returns success response
     * 
     * @return WP_Error Error on HTTP request
     */
    public function pre_http_resp_wp_error() {
        return new WP_Error( 'http_request_failed', 'A valid URL was not provided.' );
    }

    /**
     * Shortcircuit wp_remote_post to returns response with HTTP status 400
     * 
     * @return array HTTP response
     */
    public function pre_http_resp_400() {
        return array(
            'response' => array(
                'code' => 400,
            ),
        );
    }

    /**
     * Test retrieving registered events
     */
    public function test_get_events() {
        $manager = $GLOBALS['wp_typetalk']->event_manager;

        $expected = array(
            'post_published'  => array(
                'action'      => 'transition_post_status',
                'description' => 'When a post is published',
                'default'     => true,
            ),
            'post_pending_review' => array(
                'action'      => 'transition_post_status',
                'description' => 'When a post needs review',
                'default'     => false,
            ),
            'new_comment'     => array(
                'action'      => 'wp_insert_comment',
                'priority'    => 999,
                'description' => 'When there is a new comment',
                'default'     => false,
            ),
        );

        $actual = $manager->get_events();
        foreach ( $actual as $k => $v ) {
            unset( $actual[ $k ]['message']);
        }

        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test `tyeptalk_get_events` filter can be used to add custom event.
     */
    public function test_filter_get_events() {
        $expected = array(
            'action'      => 'wp_hook',
            'description' => 'My custom event',
            'default'     => true,
        );

        add_filter( 'typetalk_get_events', function( $events ) use ( $expected ) {
            $events['custom_event'] = $expected;
            return $events;
        } );

        $manager = $GLOBALS['wp_typetalk']->event_manager;
        $actual  = $manager->get_events()['custom_event'];

        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test WP_Typetalk_Event_Manager::notify_via_action() will register callback
     * via `add_action`.
     */
    public function test_notify_via_action() {
        $manager = $GLOBALS['wp_typetalk']->event_manager;

        $event = array(
            'action'  => 'transition_post_status',
            'message' => 'test',
        );

        $setting = array(
            'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
            'active'       => true,
        );

        $manager->notify_via_action( $event, $setting );

        // FIXME test priority value
        $this->assertEquals( has_action( 'transition_post_status' ), true );

        // Supply priority via setting.
        $setting = array(
            'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
            'active'       => true,
            'priority'     => 999,
        );

        $manager->notify_via_action( $event, $setting );

        // FIXME test priority value
        $this->assertEquals( has_action( 'transition_post_status' ), true );
    }

    /**
     * Test WP_Typetalk_Event_Manager::get_event_callback() returns callable function.
     */
    public function test_get_event_callback() {
        $manager = $GLOBALS['wp_typetalk']->event_manager;

        $event = array(
            'action'  => 'transition_post_status',
            'message' => 'test',
        );
        $setting = array(
            'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
            'active'       => true,
        );

        $this->assertTrue( is_callable( $manager->get_event_callback( $event, $setting ) ) );
    }
}