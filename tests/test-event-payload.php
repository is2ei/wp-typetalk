<?php
/**
 * Class EventPayloadTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

/**
 * Test for event-payload.php
 */
class EventPayloadTest extends WP_UnitTestCase {

    /**
     * Test retrieving service URL.
     */
    public function test_get_url() {
        $payload = new WP_Typetalk_Event_Payload( array(
            'endpoint_url' => 'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
        ) );

        $this->assertEquals(
            'https://typetalk.com/api/v1/topics/1234567890?typetalkToken=xxxxx',
            $payload->get_url()
        );
    }

    /**
     * Test retrieving JSON strings of setting.
     */
    public function test_get_json_string() {
        $payload = new WP_Typetalk_Event_Payload( array(
            'message' => 'Message to send',
        ) );

        $this->assertEquals(
            '{"message":"Message to send"}',
            $payload->get_json_string()
        );
    }
}