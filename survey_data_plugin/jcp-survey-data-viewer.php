<?php
/**
 * Plugin Name: JCP Survey Data Viewer
 * Description: Displays survey responses from the WordPress job_survey table under Users with JSON export.
 * Version: 1.0.3
 * Author: Porter Olson
 * License: GPL-2.0-or-later
 * Text Domain: jcp-survey-data-viewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JCPSDV_VERSION', '1.0.1' );
define( 'JCPSDV_PLUGIN_FILE', __FILE__ );
define( 'JCPSDV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once JCPSDV_PLUGIN_DIR . 'includes/class-jcpsdv-list-table.php';
require_once JCPSDV_PLUGIN_DIR . 'includes/class-jcpsdv-admin.php';
require_once JCPSDV_PLUGIN_DIR . 'includes/class-jcpsdv-plugin.php';

JCPSDV_Plugin::instance();
