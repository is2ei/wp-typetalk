<?php
/**
 * Class PluginTest
 * 
 * @package WP_Typetalk
 * @subpackage Tests
 */

/**
 * Test for plugin.php
 */
class PluginTest extends WP_UnitTestCase {
    /**
     * Test run()
     */
    public function test_run() {
        $path = $GLOBALS['wp_typetalk']->plugin_path;
        $plugin = new WP_Typetalk_Plugin();
        $plugin->run( $path );

        $this->assertEquals( 'wp_typetalk', $plugin->name );
        $this->assertEquals( trailingslashit( plugin_dir_path( $path ) ), $plugin->plugin_path );
        $this->assertEquals( trailingslashit( plugin_dir_url( $path ) ), $plugin->plugin_url );
        $this->assertEquals( $plugin->plugin_path . trailingslashit( 'includes' ), $plugin->includes_path );
        $this->assertTrue( is_a( $plugin->event_manager, 'WP_Typetalk_Event_Manager' ) );
        $this->assertTrue( is_a( $plugin->notifier, 'WP_Typetalk_Notifier' ) );
        $this->assertTrue( is_a( $plugin->post_meta_box, 'WP_Typetalk_Post_Meta_Box' ) );
        $this->assertTrue( is_a( $plugin->post_type, 'WP_Typetalk_Post_Type' ) );
        $this->assertTrue( is_a( $plugin->submit_meta_box, 'WP_Typetalk_Submit_Meta_Box' ) );
    }
}