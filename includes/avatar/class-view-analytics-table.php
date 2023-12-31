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
class View_Analytics_Avatar_Table {

	/**
	 * The single instance of the class.
	 *
	 * @var View_Analytics_Avatar_Table
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main View_Analytics_Avatar_Table Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see View_Analytics_Avatar_Table()
	 * @return View_Analytics_Avatar_Table - Main instance.
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
		return $wpdb->prefix . 'awp_va_avatar_view_log';
    }

	/**
	 * Create table
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $this->table_name();
		$view_sql = "CREATE TABLE {$table_name} (
			id 			bigint(20) NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) NULL,
			session varchar(255) NOT NULL DEFAULT '',
			key_id		varchar(255) NULL,
			user_id 	bigint(20) NOT NULL,
			type		varchar(255) NULL,
			action		varchar(255) NULL,
			is_new		tinyint(1) NOT NULL DEFAULT 1,
			locale varchar(50) NOT NULL,
			device varchar(50) NOT NULL DEFAULT 'desktop',
			action_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		maybe_create_table( $table_name, $view_sql );
	}

	/**
	 * Create table
	 */
	public function delete_table() {

		global $wpdb;

		$table_name = $this->table_name();
		$avatar_view_sql = "DROP TABLE IF EXISTS $table_name";

		$wpdb->query( $avatar_view_sql );


		/**
		 * We are not using this table any more
		 * Will delete this when version 2.0.0 is releasted
		 */
		$avatar_view_table_name_old		 = $wpdb->prefix . 'awp_va_avatar_view';
		$avatar_view_sql_old = "DROP TABLE IF EXISTS $avatar_view_table_name_old";
		$wpdb->query( $avatar_view_sql_old );

		/**
		 * We are not using this table any more
		 * Will delete this when version 2.0.0 is releasted
		 */
		$over_all_log_table_name		 = $wpdb->prefix . 'awp_va_log';
		$over_all_log_sql = "DROP TABLE IF EXISTS $over_all_log_table_name";
		$wpdb->query( $over_all_log_sql );
	}

	/**
	 * Add the current user has view avatar count
	 */
	public function user_add( $key_id, $user_id, $type = 'xprofile', $action = 'avatar', $value = 1 ) {
		global $wpdb;

		$device = wp_is_mobile() ? 'mobile' : 'desktop';
		$session = View_Analytics_Common::instance()->wp_get_current_session();

		return $wpdb->insert(
			$this->table_name(),
			array( 
				'blog_id' => get_current_blog_id(),
				'session' => $session,
				'key_id' => $key_id,
				'user_id' => $user_id,
				'type' => $type,
				'action' => $action,
				'device' => $device,
				'locale' => get_user_locale(),
			),
			array(
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Get the current user has already view the avatar or not
	 */
	public function user_get( $key_id, $type = 'xprofile', $action = 'avatar' ) {
		global $wpdb;

		$table_name = $this->table_name();

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE key_id = %d AND type = %s AND action = %s",
				$key_id,
				$type,
				$action
			)
		);
	}

	/**
	 * Delete the current user has view avatar count
	 */
	public function user_delete( $key_id ) {
		global $wpdb;
		$wpdb->delete( $this->table_name(), array( 'key_id' => $key_id ), array( '%d' ) );
		$wpdb->delete( $this->table_name(), array( 'user_id' => $key_id ), array( '%d' ) );
	}

	/**
	 * Get the avatar view details via $user_id
	 */
	public function get_details( $key_id, $type = 'xprofile', $action = 'avatar' ) {
		global $wpdb;

		$table_name = $this->table_name();

		return $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE key_id = %d AND type = %s AND action = %s",
				$key_id,
				$type,
				$action
			),
			ARRAY_A
		);
	}
}
