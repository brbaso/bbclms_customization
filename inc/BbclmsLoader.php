<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'BbclmsLoader' ) ):

	/**
	 * Class BbclmsLoader
	 */
	class BbclmsLoader {

		protected $hooks;

		/**
		 * empty constructor function to ensure a single instance
		 */
		public function __construct() {
			// leave empty, see singleton below
		}

		public static function instance() {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new BbclmsLoader;
				$instance -> setup();
			}

			return $instance;
		}

		/**
		 * setup all
		 */
		public function setup() {
			$this -> load_dependencies();
			$this -> hooks = new BbclmsHooks();
			$this -> remove_hooks();
			$this -> define_admin_hooks();
			$this -> define_public_hooks();
			$this -> run();
			global $bp;
		}

		/**
		 * Load the required
		 */
		private function load_dependencies() {
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsDB.php';
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsAjax.php';
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsFunctions.php';
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsHooks.php';
		}

		/**
		 * Remove Hooks we want to override
		 *
		 */
		private function remove_hooks() {
			if ( !is_user_logged_in() ) {
				return;
			}

			// remove Courses, My Courses from BP menus but only for Trainer ( Admin too ?)
			$current_user = wp_get_current_user();
			$current_user_roles = $current_user -> roles;

			$include = BUDDYPRESS_LEARNDASH_PLUGIN_DIR . 'includes/bp-learndash-loader.php';
			require( $include );
			$cl = BuddyPress_Learndash_Loader ::instance();

			if ( $current_user_roles[ 0 ] == 'group_leader' || $current_user_roles[ 0 ] == 'administrator' ) {
				remove_action( 'bp_setup_nav', array( $cl, 'bp_learndash_add_new_setup_nav' ), 100 );
				remove_action( 'bp_setup_admin_bar', array( $cl, 'bp_learndash_add_new_admin_bar' ), 90 );
			}

			remove_action( 'bp_members_directory_member_types', array( $cl, 'bp_learndash_members_directory' ) );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 */
		private function define_admin_hooks() {

			$plugin_functions = BbclmsFunctions ::instance();
			$plugin_db = new BbclmsDB();
			$plugin_ajax = new BbclmsAjax( $plugin_db );

			$actions_to_add = [ //add category filter to events admin list
				'restrict_manage_posts' => [ [ $plugin_functions, 'bbclms_events_category_filter' ] ], // register Buddy Panel menu for Trainer
				'after_setup_theme' => [ [ $plugin_functions, 'bbclms_register_bptrainer_menu' ] ],

				// remove admin bar profile 
				'admin_bar_menu' => [ [ $plugin_functions, 'bbclms_admin_bar_profile_remove', 999 ] ],

				// remove admin menu pages 
				//add_action( 'admin_menu', 'bbclms_remove_meta_boxes()' );	
				'admin_menu' => [ [ $plugin_functions, 'bbclms_remove_menu_pages' ], [ $plugin_functions, 'bbclms_remove_meta_boxes' ] ],

				//add_action("add_meta_boxes", "bbclms_add_assignment_update_meta_box");
				'add_meta_boxes' => [ [ $plugin_functions, 'bbclms_add_assignment_update_meta_box' ], ],

				// block admin areas for certain users
				'admin_init' => [ [ $plugin_functions, 'bbclms_blockusers_admin_areas' ] ],

				// admin scripts
				'admin_enqueue_scripts' => [ [ $plugin_functions, 'bbclms_admin_enqueue_styles' ], [ $plugin_functions, 'bbclms_admin_enqueue_scripts' ] ],

				// bbclms_students_to_courses_groups_csv
				'wp_ajax_bbclms_students_to_courses_groups_csv' => [ [ $plugin_ajax, 'bbclms_students_to_courses_groups_csv' ] ],

				// bbclms_groups_to_courses_csv
				'wp_ajax_bbclms_groups_to_courses_csv' => [ [ $plugin_ajax, 'bbclms_groups_to_courses_csv' ] ],

				// bbclms_trainers_to_groups_csv
				'wp_ajax_bbclms_trainers_to_groups_csv' => [ [ $plugin_ajax, 'bbclms_trainers_to_groups_csv' ] ],

				// bbclms_list_import_user_logs
				'wp_ajax_bbclms_list_import_user_logs' => [ [ $plugin_ajax, 'bbclms_list_import_user_logs' ] ],

				// delete_log_files
				'wp_ajax_bbclms_delete_log_files' => [ [ $plugin_ajax, 'bbclms_delete_log_files' ] ],

			];

			$filters_to_add = [

				// Remove row actions 
				//add_filter( 'post_row_actions', 'bbclms_remove_row_actions', 10, 1 );
				'post_row_actions' => [ [ $plugin_functions, 'bbclms_remove_row_actions', 10, 1 ] ],

				//add_filter('user_profile_update_errors', 'bbclms_check_user_assigned_groups', 10, 3);	
				'user_profile_update_errors' => [ [ $plugin_functions, 'bbclms_check_user_assigned_groups', 10, 3 ] ],

				//add_filter( 'tiny_mce_before_init', 'bbclms_formatTinyMCE' );
				//add_filter('tiny_mce_before_init', 'bbclms_mce_text_sizes');
				'tiny_mce_before_init' => [ [ $plugin_functions, 'bbclms_formatTinyMCE', 5 ], [ $plugin_functions, 'bbclms_mce_text_sizes' ] ],

				//add_filter( 'mce_buttons_2', 'bbclms_mce_buttons_2' );
				'mce_buttons_2' => [ [ $plugin_functions, 'bbclms_mce_buttons_2' ] ],

				//add_filter( 'mce_buttons_3', 'bbclms_mce_buttons_3' );
				'mce_buttons_3' => [ [ $plugin_functions, 'bbclms_mce_buttons_3' ] ],

			];

			$this -> hooks -> actions_to_add( $actions_to_add );
			$this -> hooks -> filters_to_add( $filters_to_add );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin and the widget.
		 *
		 */
		private function define_public_hooks() {

			$plugin_functions = BbclmsFunctions ::instance();
			$plugin_db = new BbclmsDB();
			$plugin_ajax = new BbclmsAjax( $plugin_db );


			$actions_to_add = [

				// register widget
				'widgets_init' => [ [ $plugin_functions, 'bbclms_register_widgets' ] ],

				// wp scripts
				'wp_enqueue_scripts' => [ [ $plugin_functions, 'bbclms_enqueue_styles' ], [ $plugin_functions, 'bbclms_enqueue_scripts' ] ],

				//add_action( 'bp_members_directory_member_types', array($this, 'bp_learndash_members_directory') );				
				'bp_members_directory_member_types' => [ [ $plugin_functions, 'bclms_bp_learndash_members_directory' ] ],

				//add_action( 'bp_actions', 'bbclms_rename_profile_tabs' );
				'bp_actions' => [ [ $plugin_functions, 'bbclms_rename_profile_tabs' ] ],

				//add_action( 'wp_head', 'bbclms_change_course_social_group_navigation',9 );
				'wp_head' => [ [ $plugin_functions, 'bbclms_change_course_social_group_navigation', 9 ] ],

				// add_action( 'delete_user', 'bbclms_clear_course_access_delete_user' );
				'delete_user' => [ [ $plugin_functions, 'bbclms_clear_course_access_delete_user' ] ],

				//add_action( 'learndash_assignment_uploaded', 'bbclms_learndash_assignment_uploaded' );
				'learndash_assignment_uploaded' => [ [ $plugin_functions, 'bbclms_learndash_assignment_uploaded' ] ],

				//  add_action( 'delete_post', 'bbclms_clear_course_groups_delete_group', 10 );
				'delete_post' => [ [ $plugin_functions, 'bbclms_clear_course_groups_delete_group', 10 ] ],

				// make sure CRON is called whenever WordPress loads

				//add_action('wp', 'bbclms_cron_csvimport_activation');
				'wp' => [
					[ $plugin_functions, 'bbclms_cron_csvimport_activation' ] ],

				'wp_ajax_bbclms_contact_trainer_ajax' => [ [ $plugin_ajax, 'bbclms_contact_trainer_ajax' ] ],

				'wp_ajax_nopriv_bbclms_contact_trainer_ajax' => [ [ $plugin_ajax, 'bbclms_contact_trainer_ajax' ] ] ];

			$filters_to_add = [
				// add_filter( 'cron_schedules', 'bbclms_cron_add_half_hour' );
				'cron_schedules' => [ [ $plugin_functions, 'bbclms_cron_add_half_hour' ] ],
			];

			$this -> hooks -> actions_to_add( $actions_to_add );
			$this -> hooks -> filters_to_add( $filters_to_add );
		}

		/**
		 * Run to execute all of the hooks with WordPress.
		 *
		 */
		public function run() {
			$this -> hooks -> run();
		}
	}

	BbclmsLoader ::instance();

endif;
