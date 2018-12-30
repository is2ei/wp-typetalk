<?php
/**
 * Class TypetalkTest
 *
 * @package Wp_Typetalk
 * @subpackage Tests
 */

/**
 * Test for typetalk.php
 */
class WP_Typetalk_Test extends WP_UnitTestCase {

	/**
	 * Test classes are loaded by autoloader.
	 */
	public function test_classes_are_available_from_autoloader() {
		$this->assertTrue( class_exists( 'WP_Typetalk_Event_Manager' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Event_Payload' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Notifier' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Plugin' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Post_Meta_Box' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Post_Type' ) );
		$this->assertTrue( class_exists( 'WP_Typetalk_Submit_Meta_Box' ) );
	}

	/**
	 * Test instance WP_Typetalk_Plugin is available globally.
	 */
	public function test_typetalk_plugin_instance_available_globally() {
		$this->assertTrue( is_a( $GLOBALS['wp_typetalk'], 'WP_Typetalk_Plugin' ) );
	}
}
