<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://acrosswp.com
 * @since      1.0.0
 *
 * @package    View_Analytics
 * @subpackage View_Analytics/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    View_Analytics
 * @subpackage View_Analytics/includes
 * @author     AcrossWP <contact@acrosswp.com>
 */
class View_Analytics_Group_Table {

	/**
	 * The single instance of the class.
	 *
	 * @var View_Analytics_Group_Table
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * The single instance of the class.
	 *
	 * @var View_Analytics_Log_Table
	 * @since 1.0.0
	 */
	public $log_table = null;

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 */
	public $log_table_key = 'group_view';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
        $this->log_table = View_Analytics_Log_Table::instance();
	}

	/**
	 * Main View_Analytics_Group_Table Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see View_Analytics_Group_Table()
	 * @return View_Analytics_Group_Table - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
     * Return the View Analytics Media Count Ket
     */
    public function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'awp_va_group_view';
    }

	/**
	 * Add the current user has view group count
	 */
	public function user_add( $group_id, $viewer_id, $value = 1 ) {
		global $wpdb;

		$add = $wpdb->insert(
			$this->table_name(),
			array( 
				'group_id' => $group_id,
				'viewer_id' => $viewer_id,
				'value' => $value,
			),
			array(
				'%d',
				'%d',
				'%d',
			)
		);

		if ( $add ) {
			$this->log_table->user_add( $this->log_table_key, $group_id, 0, $viewer_id );
		}

		return $add;
	}

	/**
	 * Get the current user has already view the group or not
	 */
	public function user_get( $group_id, $viewer_id ) {
		global $wpdb;

		$table_name = $this->table_name();

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE group_id = %d AND viewer_id = %d",
				$group_id,
				$viewer_id
			)
		);
	}

	/**
	 * Update the current user has view group count
	 */
	public function user_update( $id, $value, $details = false ,$mysql_time = false ) {
		global $wpdb;
		
		if ( empty( $mysql_time ) ) {
			$mysql_time = $wpdb->get_var( 'select CURRENT_TIMESTAMP()' );
		}

		$update = $wpdb->update(
			$this->table_name(),
			array(
				'last_date' => $mysql_time,
				'value' => $value,
				'is_new' => 1,
			),
			array( 
				'id' => $id 
			),
			array( '%s','%d','%d' ),
			array( '%d' )
		);

		if ( $update && ! empty( $details->group_id ) && ! empty( $details->viewer_id ) ) {
			$this->log_table->user_add( $this->log_table_key, $details->group_id, 0, $details->viewer_id );
		}

		return $update;
	}

	/**
	 * Delete the current user has view group count
	 */
	public function user_delete( $group_id ) {
		global $wpdb;
		$wpdb->delete( $this->table_name(), array( 'group_id' => $group_id ), array( '%d' ) );
	}

	/**
	 * Get the group view details via $group_id
	 */
	public function get_details( $group_id ) {
		global $wpdb;

		$table_name = $this->table_name();

		return $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE group_id = %d",
				$group_id
			)
		);
	}
}
