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
class View_Analytics_Public_Profile_Count {

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

		$this->common = View_Analytics_Profile_Common::instance();

	}

	/**
	 * This function is run when someone visit the member profile page
	 */
	public function home_content() {

		$current_user_id = get_current_user_id();
		$displayed_user_id = bp_displayed_user_id();

		/**
		 * Check if both are not empty
		 */
		if ( ! empty( $current_user_id ) && ! empty( $displayed_user_id ) ) {
			$this->update_view_count( $displayed_user_id, $current_user_id );
		}
	}

	/**
	 * Update Media view count
	 */
	public function update_view_count( $author_id, $current_user_id ) {

		if ( $this->common->view_count_enable() ) {

			$bp = buddypress();
			$profile_slug = $bp->displayed_user->userdata->user_login;
			$components = $this->common->get_components( $profile_slug, $bp->default_component );

			$views = $this->common->table->get_details( $author_id );

			if( empty( $views ) ) {
				$this->common->table->user_add( $author_id, $current_user_id, $components, 1 );
			} else {
				
				$id = $views['id'];

				/**
				 * Ref count
				 */
				$ref_count = empty( $views['ref_count'] ) ? 1 : absint( $views['ref_count'] ) + 1;

				/**
				 * Users list
				 */
				$users_list = empty( $views['users_list'] ) ? array() : maybe_unserialize( $views['users_list'] );
				if ( ! in_array( $current_user_id, $users_list ) ) {
					array_unshift( $users_list, $current_user_id );
				}

				/**
				 * Users view count
				 */
				$user_count = count( $users_list );

				/**
				 * update session count
				 */
				$session_count = $this->common->table->user_get( $current_user_id, $author_id, true );
				$session_count = empty( $session_count ) ? absint( $views['session_count'] ) + 1 : absint( $views['session_count'] );

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
