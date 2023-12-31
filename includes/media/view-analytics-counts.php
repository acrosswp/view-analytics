<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://acrosswp.com
 * @since      1.0.0
 *
 * @package    View_Analytics
 * @subpackage View_Analytics/public/partials
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
class View_Analytics_Public_Media_Count {

    /**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The ID of this media setting view.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $common;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      View_Analytics_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->loader = View_Analytics_Loader::instance();

		$this->common = View_Analytics_Media_Common::instance();

		$this->buddyboss();

		$this->buddypress();

	}


	/**
	 * Hook for BuddyBoss releate filter and action
	 */
	public function buddyboss() {
	
		/**
		 * For Media
		 */
		$this->loader->add_action( 'wp_ajax_media_get_media_description', $this, 'buddyboss_photo_view_count_login_user', -10 );
		$this->loader->add_action( 'wp_ajax_media_get_activity', $this, 'buddyboss_photo_view_count_login_user', -10 );

		/**
		 * For Video
		 */
		$this->loader->add_action( 'wp_ajax_video_get_video_description', $this, 'buddyboss_video_view_count_login_user', -10 );
		$this->loader->add_action( 'wp_ajax_video_get_activity', $this, 'buddyboss_video_view_count_login_user', -10 );

		/**
		 * For Document
		 */
		$this->loader->add_action( 'wp_ajax_document_get_document_description', $this, 'buddyboss_document_view_count_login_user', -10 );
		$this->loader->add_action( 'wp_ajax_document_get_activity', $this, 'buddyboss_document_view_count_login_user', -10 );
	}

	/**
	 * Hook for BuddyBoss releate filter and action
	 */
	public function buddypress() {

		/**
		 * For All Media Type
		 */
		$this->loader->add_action( 'get_template_part_attachments/single/view', $this, 'buddypress_media_view', 1000, 3 );
	}

	/**
	 * BuddyPress Media View
	 */
	public function buddypress_media_view( $slug, $name, $args ) {

		$medium      = bp_attachments_get_queried_object();

		/**
		 * Chech if the user is not login
		 */
		if ( is_user_logged_in() && ! empty( $medium->id ) ) {
			$this->update_view_count( $medium->id, $medium->id );
		}
	}

    /**
     * Count the number of users has view the media
     */
    public function buddyboss_photo_view_count_login_user() {
		$this->buddyboss_view_count_verification( 'bp_nouveau_media', 'photo' );
    }

	/**
     * Count the number of users has view the video
     */
    public function buddyboss_video_view_count_login_user() {
		$this->buddyboss_view_count_verification( 'bp_nouveau_video', 'video' );
    }


	/**
     * Count the number of users has view the video
     */
    public function buddyboss_document_view_count_login_user() {
		$this->buddyboss_view_count_verification( 'bp_nouveau_media', 'document' );
    }

	/**
	 * Verifying the nonce and then adding the media count
	 */
	public function buddyboss_view_count_verification( $key, $type ) {

		// Nonce check!
	    if ( $this->buddyboss_check_nonce( $key ) ) {

			/**
			 * Check if the attachment_id exits or not
			 */
			$check_variable = $this->buddyboss_check_variable();
			if ( ! empty( $check_variable ) ) {

				$media_owner_id = $this->common->table->get_bb_media_owner_id( $check_variable['media_id'], $type );

				$this->update_view_count( $check_variable['key_id'], $check_variable['hash_id'] ,$check_variable['media_id'], $check_variable['attachment_id'], $media_owner_id, $type );
			}
        }
	}

	/**
	 * Verifying the nonce and then adding the media count
	 */
	public function buddyboss_check_variable() {

		$media_id = $this->common->get_filter_post_value( 'id' );
		$attachment_id = $this->common->get_filter_post_value( 'attachment_id' );

		/**
		 * Check if the attachment_id exits or not
		 */
		if ( ! empty( $media_id ) && ! empty( $attachment_id ) ) {
			return array(
				'key_id' => $attachment_id,
				'hash_id' => '0',
				'media_id' => $media_id,
				'attachment_id' => $attachment_id,
			);
		}

		$action = $this->common->get_filter_post_value( 'action', FILTER_SANITIZE_STRING );

		if( 'video_get_activity' == $action ) {
			$media_id = $this->common->get_filter_post_value( 'video_id' );
			
			if ( ! empty( $media_id ) ) {
				/**
				 * Here this will work only for Image and Video 
				 * This function wont work if it's document because docuemnt has a seperate table
				 */
				$attachment_id = $this->common->get_bb_media_attachment_id( $media_id );

				/**
				 * if not empty
				 */
				if ( ! empty( $attachment_id ) ) {
					return array(
						'key_id' => $attachment_id,
						'hash_id' => '0',
						'media_id' => $media_id,
						'attachment_id' => $attachment_id,
					);
				}
			}
		}

		return false;
	}

	/**
	 * Verifying the nonce and then adding the media count
	 */
	public function buddyboss_check_nonce( $key ) {
		// Nonce check!
	    $nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	    if ( wp_verify_nonce( $nonce, $key ) ) {
			return true;
        }

		return false;
	}

	/**
	 * Update Media view count
	 */
	public function update_view_count( $key_id, $hash_id = '0', $media_id = 0, $attachment_id = 0, $media_owner_id = 0, $media_type = 'photo' ) {

		if ( $this->common->view_count_enable() ) {
			$current_user_id = get_current_user_id();

			$views = $this->common->table->get_details( $key_id );
			
			$components = $this->common->get_components( $media_id, $media_type );
	
			/**
			 * Check if empty
			 */
			if ( empty( $views ) ) {
				$id = $this->common->table->user_add( 
					$current_user_id,
					$key_id,
					$hash_id,
					$media_id,
					$attachment_id,
					$media_owner_id,
					$media_type,
					$components
				);

				/**
				 * Fire a hook when someone view media for the first time
				 */
				do_action( $this->common->create_hooks_key( 'view_media' ), $id, $key_id, $current_user_id, $media_owner_id, 0, 1 );

			} else {
				$id = $views['id'];

				/**
				 * Ref count
				 */
				$old_ref_count = empty( $views['ref_count'] ) ? 1 : absint( $views['ref_count'] );
				$ref_count = $old_ref_count + 1;
				do_action( $this->common->create_hooks_key( 'view_media' ), $id, $key_id, $current_user_id, $views['author_id'], $old_ref_count, $ref_count );

				/**
				 * Users list
				 */
				$users_list = empty( $views['users_list'] ) ? array() : maybe_unserialize( $views['users_list'] );
				$old_user_count = count( $users_list );

				if ( ! in_array( $current_user_id, $users_list ) ) {
					array_unshift( $users_list, $current_user_id );
				}
				$user_count = count( $users_list );

				do_action( $this->common->create_hooks_key( 'users_view_media' ), $id, $key_id, $current_user_id, $views['author_id'], $old_user_count, $user_count );

				/**
				 * update session count
				 */
				$session_count		= $this->common->table->user_get( $current_user_id, $key_id, true );
				$old_session_count	= absint( $views['session_count'] );
				$session_count		= empty( $session_count ) ? $old_session_count + 1 : $old_session_count;
				do_action( $this->common->create_hooks_key( 'sessions_view_media' ), $id, $key_id, $current_user_id, $views['author_id'], $old_session_count, $session_count );

				$this->common->table->user_update( 
					$id,
					$users_list,
					$user_count,
					$ref_count,
					$session_count,
					$current_user_id,
					$views,
					$components
				);
			}
		}
	}
}
