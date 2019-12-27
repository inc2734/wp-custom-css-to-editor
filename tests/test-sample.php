<?php
// @todo
class Sample_Test extends WP_UnitTestCase {

	public function setup() {
		parent::setup();
		new Inc2734\WP_Custom_CSS_To_Editor\Bootstrap();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function test() {
		$this->assertTrue( true );
	}
}
