<?php
/**
 * Class PostMetaBoxTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

 /**
  * Test for post-meta-box.php
  */
class PostMetaBoxTest extends WP_UnitTestCase {
    /**
     * Make sure expected hooks have expected callbacks.
     */
    public function test_registered_callbacks() {
        $instance = $GLOBALS['wp_typetalk']->post_meta_box;
        $this->assertEquals( 10, has_action( 'add_meta_boxes_typetalk_integration', array( $instance, 'add_meta_box' ) ) );
        $this->assertEquals( 10, has_action( 'save_post', array( $instance, 'save_post' ) ) );
        $this->assertEquals( 10, has_action( 'wp_ajax_typetalk_test_notify', array( $instance, 'ajax_test_notify' ) ) );
    }
}