<?php
/**
 * Class NotifierTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

/**
 * Test for notifier.php
 */
class NotifierTest extends WP_UnitTestCase {
  /** 
   * Test notify to Typetalk
   * 
   * Similar to testing WP_Typetalk_Event_Manager::dispatch_events(), this involves
   * filtering HTTP response.
   */
  public function test_notify() {
    $notifier = $GLOBALS['wp_typetalk']->notifier;
    $self     = $this;

    $payload = new WP_Typetalk_Event_Payload( array(
      'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
      'message'      => 'Test message',
    ) );

    // Assert all requests data when short-circuited the HTTP request.
    add_filter( 'pre_http_request', function( $preempt, $r, $url ) use ( $self, $payload ) {
      $self->assertEquals( 'POST', $r['method'] );
      $self->assertEquals( sprintf('wp_typetalk/%s', $GLOBALS['wp_typetalk']->version ), $r['user-agent'] );
      $self->assertEquals( $payload->get_url(), $url );
      $self->assertEquals( $payload->get_json_string(), $r['body'] );

      // This response should be returned by notify().
      return array(
        'response' => array(
          'code' => 200,
        ),
      );
    }, 10, 3 );
    $resp = $notifier->notify( $payload );
    $this->assertEquals( 200, wp_remote_retrieve_response_code( $resp ) );

    add_filter( 'pre_http_request', function( $preempt, $r, $url ) {
      return array(
        'response' => array(
          'code' => 400,
        ),
      );
    }, 10, 3 );
    $resp = $notifier->notify( $payload );
    $this->assertTrue( is_wp_error( $resp ) );
    $this->assertEquals( 'typetalk_unexpected_response', $resp->get_error_code() );
  }
}