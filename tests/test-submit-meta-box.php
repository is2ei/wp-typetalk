<?php
/**
 * Class SubmitMetaBoxTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

/**
 * Test for submit-meta-box.php
 */
class SubmitMetaBoxTest extends WP_UnitTestCase {
    /**
     * Make sure expected hooks have expected callbacks.
     */
    public function test_registered_callbacks() {
        $instance = $GLOBALS['wp_typetalk']->submit_meta_box;

        $this->assertEquals( 10, has_action( 'add_meta_boxes', array( $instance, 'register_meta_box' ) ) );
    }
}