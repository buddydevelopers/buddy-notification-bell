<?php

class BNB_File_IncludeTest extends WP_UnitTestCase {

	/**
	 * Function to test load_bnb_component_init
	 */
	function test_load_bnb_component_init() {
		load_bnb_component_init();
		$this->assertNotEmpty(BUDDY_NOTIFICATION_BELL_PLUGINS_URL);
		$this->assertNotEmpty(BUDDY_NOTIFICATION_BELL_PLUGINS_PATH);
		$this->assertTrue(class_exists('Buddy_Notification_Bell_Public'));
	}
}
