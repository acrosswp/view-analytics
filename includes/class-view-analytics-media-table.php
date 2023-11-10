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
class View_Analytics_Media_Table {

	/**
	 * The single instance of the class.
	 *
	 * @var View_Analytics_Media_Table
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main View_Analytics_Media_Table Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see View_Analytics_Media_Table()
	 * @return View_Analytics_Media_Table - Main instance.
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
    public function media_view_count_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'awp_va_media_view';
    }

	/**
	 * Add the current user has view media count
	 */
	public function user_media_add( $user_id, $media_id, $attachment_id, $value = 1 ) {
		global $wpdb;

		return $wpdb->insert(
			$this->media_view_count_table_name(),
			array( 
				'user_id' => $user_id,
				'media_id' => $media_id,
				'attachment_id' => $attachment_id,
				'value' => $value,
			),
			array(
				'%d',
				'%d',
				'%d',
				'%d',
			)
		);
	}

	/**
	 * Get the current user has already view the media or not
	 */
	public function user_media_get( $user_id, $attachment_id ) {
		global $wpdb;

		$table_name = $this->media_view_count_table_name();

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE user_id = %d AND attachment_id = %d",
				$user_id,
				$attachment_id
			)
		);
	}

	/**
	 * Get the media view details via $attachment_id
	 */
	public function media_get_details( $attachment_id ) {
		global $wpdb;

		$table_name = $this->media_view_count_table_name();

		return $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE attachment_id = %d",
				$attachment_id
			)
		);
	}

	/**
	 * Update the current user has view media count
	 */
	public function user_media_update( $id, $value ) {
		global $wpdb;
		$wpdb->update(
			$this->media_view_count_table_name(),
			array(
				'value' => $value,
			),
			array( 
				'id' => $id 
			),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Delete the current user has view media count
	 */
	public function user_media_delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->media_view_count_table_name(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_media_details( $media_id ) {
		global $wpdb;
		global $bp;

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM {$bp->media->table_name} WHERE id = %d",
				$media_id
			)
		);
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_media_attachment_id( $media_id ) {

		$media_details = $this->get_bb_media_details( $media_id );

		/**
		 * if not empty
		 */
		if ( ! empty( $media_details->attachment_id ) ) {
			return $media_details->attachment_id;
		}

		return false;
	}
}
