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
    public function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'awp_va_media_view';
    }

	/**
     * Return the View Analytics Media Count Ket
     */
    public function table_name_log() {
		global $wpdb;
		return $wpdb->prefix . 'awp_va_media_view_log';
    }

	/**
	 * Create table
	 */
	public function create_table() {

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		/**
		 * Profile View
		 */
		$table_name		 = $this->table_name();
		$view_sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			author_id bigint(20) NOT NULL DEFAULT 0,
			key_id varchar(255) NOT NULL DEFAULT 0,
			hash_id varchar(255) NOT NULL DEFAULT 0,
			media_id bigint(20) NOT NULL DEFAULT 0,
			attachment_id bigint(20) NOT NULL DEFAULT 0,
			users_list longtext NULL,
			user_count bigint(20) NOT NULL DEFAULT 1,
			ref_count bigint(20) NOT NULL DEFAULT 1,
			session_count bigint(20) NOT NULL DEFAULT 1,
			type varchar(50) NOT NULL DEFAULT 'photo',
			mime_type varchar(50) NOT NULL DEFAULT '',
			is_new tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		$table_name_log		 = $this->table_name_log();
		$view_sql_log = "CREATE TABLE {$table_name_log} (
			id bigint(20) NOT NULL AUTO_INCREMENT ,
			match_id bigint(20) NOT NULL DEFAULT 0,
			blog_id bigint(20) NULL,
			session varchar(255) NOT NULL DEFAULT '',
			viewer_id bigint(20) NOT NULL DEFAULT 0,
			key_id varchar(255) NOT NULL DEFAULT 0,
			url varchar(255) NOT NULL DEFAULT '',
			site_components varchar(255) NULL DEFAULT '',
			components varchar(255) NULL DEFAULT '',
			object varchar(255) NULL DEFAULT '',
			primitive varchar(255) NULL DEFAULT '',
			variable varchar(255) NULL DEFAULT '',
			device varchar(50) NOT NULL DEFAULT 'desktop',
			locale varchar(50) NOT NULL,
			action_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		maybe_create_table( $table_name, $view_sql );
		maybe_create_table( $table_name_log, $view_sql_log );	
	}

	/**
	 * Create table
	 */
	public function delete_table() {
		global $wpdb;

		/**
		 * profile and Profile view log
		 */
		$table_name		 = $this->table_name();
		$view_sql = "DROP TABLE IF EXISTS $table_name";

		$table_name_log		 = $this->table_name_log();
		$view_sql_log = "DROP TABLE IF EXISTS $table_name_log";

		$wpdb->query( $view_sql );
		$wpdb->query( $view_sql_log );

	}

	/**
	 * Add the current user has view media count
	 */
	public function user_add( $viewer_id, $key_id, $hash_id = '0', $media_id = 0, $attachment_id = 0, $media_owner_id = 0, $media_type = 'photo', $components = array(), $ref_count = 1 ) {
		global $wpdb;

		$mime_type = get_post_mime_type( $attachment_id );

		$add = $wpdb->insert(
			$this->table_name(),
			array(
				'key_id' => $key_id,
				'hash_id' => $hash_id,
				'media_id' => $media_id,
				'attachment_id' => $attachment_id,
				'users_list' => serialize( array( $viewer_id ) ),
				'author_id' => $media_owner_id,
				'type' => $media_type,
				'ref_count' => $ref_count,
				'mime_type' => $mime_type,
			),
			array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
			)
		);

		if ( $add ) {
			$this->add_log( $wpdb->insert_id, $viewer_id, $key_id, $components );
		}

		return $add;
	}

	/**
	 * Get the current user has already view the media or not
	 */
	public function user_get( $viewer_id, $key_id, $session = false ) {
		global $wpdb;

		$table_name = $this->table_name_log();

		if ( $session ) {
			$session = View_Analytics_Common::instance()->wp_get_current_session();
			$sql = $wpdb->prepare( 
				"SELECT * FROM $table_name WHERE viewer_id = %d AND key_id = %s AND session = %s",
				$viewer_id,
				$key_id,
				$session,
			);
		} else {
			$sql = $wpdb->prepare( 
				"SELECT * FROM $table_name WHERE viewer_id = %d AND key_id = %s",
				$viewer_id,
				$key_id
			);
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get the current user has already view the media or not
	 */
	public function get_user_log_view( $viewer_id, $key_id ) {
		global $wpdb;

		$table_name = $this->table_name_log();

		$sql = $wpdb->prepare( 
			"SELECT MIN( action_date ) as action_date FROM $table_name WHERE viewer_id = %d AND key_id = %s",
			$viewer_id,
			$key_id
		);

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Get the media view details via $attachment_id
	 */
	public function get_details( $key_id ) {
		global $wpdb;

		$table_name = $this->table_name();

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM $table_name WHERE key_id = %s",
				$key_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get the media view details via $attachment_id
	 */
	public function get_all_details( $orderby = 'ref_count', $order = 'DESC', $per_page = 20, $offset = 0, $media_type = false ) {
		global $wpdb;

		$table_name = $this->table_name();
		$orderby = sanitize_text_field( $orderby );
		$order = sanitize_text_field( $order );

		if ( empty( $media_type ) || 'all' == $media_type ) {
			$sql = $wpdb->prepare( 
				"SELECT * FROM $table_name ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		} else {
			$sql = $wpdb->prepare( 
				"SELECT * FROM $table_name WHERE type = %s ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$media_type,
				$per_page,
				$offset
			);
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get the media view details via $attachment_id
	 */
	public function get_all_details_count( $media_type = false ) {
		global $wpdb;

		$table_name = $this->table_name();

		if ( empty( $media_type ) || 'all' == $media_type ) {
			$sql = $wpdb->prepare( 
				"SELECT id FROM $table_name"
			);
		} else {
			$sql = $wpdb->prepare( 
				"SELECT id FROM $table_name WHERE type = %s",
				$media_type
			);
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return empty( $results ) ? 0 : count( $results );
	}

	/**
	 * Update the current user has view media count
	 */
	public function user_update( $id, $users_list, $user_count, $ref_count, $session_count, $viewer_id, $details = false, $components = array() ) {
		global $wpdb;

		$update = $wpdb->update(
			$this->table_name(),
			array(
				'users_list' => serialize( $users_list ),
				'user_count' => $user_count,
				'ref_count' => $ref_count,
				'session_count' => $session_count,
				'is_new' => 1,
			),
			array( 
				'id' => $id 
			),
			array( '%s', '%d', '%d', '%d', '%d' ),
			array( '%d' )
		);

		if ( 
			$update 
			&& ! empty( $details['key_id'] ) 
			&& ! empty( $details['author_id'] ) 
			) {
			$this->add_log( $id, $viewer_id, $details['key_id'], $components );
		}

		return $update;
	}

	/**
	 * Delete the current user has view media count
	 */
	public function user_delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->table_name(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Delete the current user has view media count
	 */
	public function delete( $ids ) {
		global $wpdb;

		$table_name = $this->table_name();
		$table_name_log = $this->table_name_log();

		$wpdb->query( "DELETE FROM $table_name WHERE id IN( {$ids} )" );

		$wpdb->query( "DELETE FROM $table_name_log WHERE id IN( {$ids} )" );
	}

	/**
	 * Get the Media type count
	*/
	public function get_bb_media_type_count( $type ) {
		global $wpdb;

		$table_name = $this->table_name();

		$sql = $wpdb->prepare( 
			"SELECT count(1) as count FROM {$table_name} WHERE type = %s",
			$type
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return empty( $result['count'] ) ? 0 : absint( $result['count'] );
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_media_type_count_from_bb( $table = 'media', $type = '' ) {
		global $wpdb;
		global $bp;

		
		$sql = $wpdb->prepare( 
			"SELECT id FROM {$bp->media->table_name} WHERE type = %s",
			$type
		);

		if ( 'document' == $table ) {
			$sql = $wpdb->prepare( 
				"SELECT id FROM {$bp->document->table_name}",
				$type
			);
		}

		$result = $wpdb->get_results( $sql, ARRAY_A );
		return empty( $result ) ? 0 : count( $result );
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_media_type_from_bb() {
		global $wpdb;
		global $bp;

		$type = array();
		$sql = "SELECT DISTINCT type FROM {$bp->media->table_name}";

		$media_types = $wpdb->get_results( $sql, ARRAY_A );

		if( ! empty( $media_types ) ) {
			foreach( $media_types as $media_type ) {

				if( ! in_array( $media_type['type'], $type ) ) {
					$type[] = $media_type['type'];
				}
			}
		}
		return $type;
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
			),
			ARRAY_A
		);
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_document_details( $document_id ) {
		global $wpdb;
		global $bp;

		return $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT * FROM {$bp->document->table_name} WHERE id = %d",
				$document_id
			),
			ARRAY_A
		);
	}

	/**
	 * Add value in Log table
	 */
	public function add_log( $match_id, $viewer_id, $key_id, $components ) {
		global $wpdb;

		$device = wp_is_mobile() ? 'mobile' : 'desktop';
		$session = View_Analytics_Common::instance()->wp_get_current_session();

		return $wpdb->insert(
			$this->table_name_log(),
			array( 
				'blog_id' => get_current_blog_id(),
				'session' => $session,
				'match_id' => $match_id,
				'viewer_id' => $viewer_id,
				'key_id' => $key_id,
				'url' => $components['url'],
				'site_components' => $components['site_components'],
				'components' => $components['components'],
				'object' => $components['object'],
				'primitive' => $components['primitive'],
				'variable' => $components['variable'],
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
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_media_owner_id( $media_id, $type = 'media' ) {
		
		if( 'document' == $type ) {

			$details = $this->get_bb_document_details( $media_id );
		} else {
			$details = $this->get_bb_media_details( $media_id );
		}

		/**
		 * if not empty
		 */
		if ( ! empty( $details['user_id'] ) ) {
			return $details['user_id'];
		}

		return false;
	}


	/**
	* Here this will work only for Image and Video 
	* This function wont work if it's document because docuemnt has a seperate table
	* 
	* get the value of the media from the bp_media buddyboss table
	*/
	public function get_bb_post_id( $media_id ) {
		global $wpdb;
		global $bp;

		$posts = $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT t.post_id
				FROM {$wpdb->postmeta} t
			   	WHERE 
					FIND_IN_SET(%s, t.meta_value) > 0 
					AND t.meta_key = 'bp_media_ids'
				",
				$media_id
			),
			ARRAY_A
		);

		if ( empty( $posts['post_id'] ) ) {
			return false;
		}

		return $posts['post_id'];
	}
}