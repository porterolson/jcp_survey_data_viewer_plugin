<?php
/**
 * Main plugin bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JCPSDV_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var JCPSDV_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var JCPSDV_Admin|null
	 */
	private $admin;

	/**
	 * Get singleton instance.
	 *
	 * @return JCPSDV_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->admin = is_admin() ? new JCPSDV_Admin() : null;

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'jcp-survey-data-viewer', false, dirname( plugin_basename( JCPSDV_PLUGIN_FILE ) ) . '/languages' );
	}
}
