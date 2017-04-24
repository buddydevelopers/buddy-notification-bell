<?php

class Buddy_Notification_Bell_PublicTest extends WP_UnitTestCase {

	function test_get_instance(){
		$instance = Buddy_Notification_Bell_Public::get_instance();
		$this->assertNotEmpty($instance);
	}
}