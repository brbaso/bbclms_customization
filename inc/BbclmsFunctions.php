<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if( !class_exists('BbclmsFunctions') ):

	/**
	 *
	 * BbclmsFunctions
	 * ********************
	 *
	 *
	 */
    class BbclmsFunctions {		
		
        /**
         * empty constructor function to ensure a single instance
         */
        public function __construct(){
            // leave empty, see singleton below
        }

        public static function instance(){

            static $instance = null;
	
            if(null === $instance){
                $instance = new BbclmsFunctions;
                $instance->setup();
            }
            return $instance;
        }

        /**
         * setup all
         */
        public function setup(){
			global $bp;			
			$this->load_dependencies();
			$this -> dbhandle = new BbclmsDB;			
        }
		
		/**
		 * Load the required
		 */
		public function load_dependencies() {		
					 
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsDB.php';
			require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsAjax.php';			
		}
		
		/**
		 * Get group leader for student and course
		 *	
		 */
		public function bbclms_get_the_group_leader( $post = null, $current_user = null ) {
			
			if( ! $post ){
				return;
			}
			
			if( ! $current_user){
				return;
			}
			
			if( ! is_user_logged_in() ){
				return;
			}
			
			$course_groups = learndash_get_course_groups( $post->ID, true );
			$all_group_leaders = learndash_all_group_leaders();
			
			$course_group_courses_ids = learndash_get_groups_courses_ids();
			$users_group_ids = learndash_get_users_group_ids( $current_user->ID );			
			
			// TODO find a better way to find Group and to be sure that we are in the right Group ??
			$user_course_group = array_merge(array_intersect($users_group_ids, $course_groups ));
			
			$group_id = ($user_course_group) ? $user_course_group[0] : 0;
			
			$the_group_leader = [];
			
			foreach ($all_group_leaders as $gl){
				
				if(learndash_is_group_leader_of_user( $gl -> ID, $current_user->ID )){					
					$leader_groups = learndash_get_administrators_group_ids( $gl -> ID );
					
					if( in_array( $group_id, $leader_groups ) ){
						$the_group_leader = $gl;
					}
				}				
			}			
			return $the_group_leader;			
		}
		
		/**
		 * Get group courses
		 * 
		 */
		public function bbclms_get_group_courses( $group_id = '' ){			
			$courses = $this -> dbhandle -> bbclms_get_all_group_courses( $group_id );			
			return $courses;
		}
				
		/**
		 * Get group leaders groups
		 * 
		 */
		public function bbclms_get_group_leaders_groups( $user_id = 0 ) {
			$groups = $this -> dbhandle -> bbclms_get_all_group_leaders_groups( $user_id );
			return $groups;
		}

		/**
		 * Get Users/Students of a Group Leader/Trainer by groups
		 * 
		 * returns array/object of users
		 */
		public function bbclms_get_group_leader_groups_users( $user_id = 0, $by_group = false, $totals_only = false ) {
	
			if ($by_group == false) {
				if ($totals_only == true) {
					$user_ids =  array();
				} else {
					$user_ids = array();
				}
			} else {
				if ($totals_only == true) {
					$user_ids = array();
				} else {
					$user_ids =  array();
				}
			}
		
			if ( empty( $user_id ) ) {
				// If the current user is not able to be determined. Then abort. 
				if ( !is_user_logged_in() ) return;
				
				$user_id = get_current_user_id();
			}
			
			if ( learndash_is_group_leader_user( $user_id ) ) {
				
				$group_ids = learndash_get_administrators_group_ids( $user_id );
				 
				if ( !empty( $group_ids ) ) {
					
					foreach ( $group_ids as $group_id ) {
						$group_user_ids = learndash_get_groups_user_ids( $group_id );						
						
						if ($by_group == true) {
							if ( $totals_only == true) {
								$user_ids[$group_id] = count( $group_user_ids );
							} else {
								$user_ids[$group_id] = $group_user_ids;
							}
						} else {
							if ( $totals_only == true) {
								$user_ids += count( $group_user_ids );
							} else {
								$user_ids = array_merge( $user_ids, $group_user_ids );
							}
						}
					}
				}
			}
			
			if ( !empty( $user_ids ) ) {
				if ($by_group == false) {
					$user_ids = array_unique( $user_ids );
				}
			}
			
			if ($totals_only == true) {
					$results = $user_ids ;
				} else {
					$results = [];
					$user_ids_by_group = $user_ids;
					foreach($user_ids_by_group as $key => $arr){
						
						$args = array(
							'include' => $arr,
							//'include' => array(3),
							//'fields' => array('user_login') 
						);
						$results[$key] = get_users( $args );							
					}
				}

			return $results;			
		}
		
		/**
		 * Boss header nav > Dashboard
		 */
		public function bbclms_header_dashboard_subnav_links() { 
			if( !current_user_can( 'group_leader' ) &  !current_user_can( 'subscriber' ) ){
			?>
			<div class="ab-sub-wrapper">
				<ul class="ab-submenu">
					<li>

						<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
						<a href="<?php echo admin_url( 'admin.php?page=boss_options' ); ?>"><?php _e( 'Boss Options', 'boss' ); ?></a>
						<a href="<?php echo admin_url( 'customize.php' ); ?>"><?php _e( 'Customize', 'boss' ); ?></a>
						<a href="<?php echo admin_url( 'widgets.php' ); ?>"><?php _e( 'Widgets', 'boss' ); ?></a>
						<a href="<?php echo admin_url( 'nav-menus.php' ); ?>"><?php _e( 'Menus', 'boss' ); ?></a>
						<a href="<?php echo admin_url( 'themes.php' ); ?>"><?php _e( 'Themes', 'boss' ); ?></a>
						<?php endif; ?>

						<?php if ( current_user_can( 'activate_plugins' ) ): ?>
						<a href="<?php echo admin_url( 'plugins.php' ); ?>"><?php _e( 'Plugins', 'boss' ); ?></a>
						<?php endif; ?>
						
							<a href="<?php echo admin_url( 'profile.php' ); ?>"><?php _e( 'Profile', 'boss' ); ?></a>
						
						
					</li>
				</ul>
			</div>
			<?php
			}					
		}
		
		/**
		 * Get ALL the courses for a user from both the $courses_registered and  the course_progress.
		 *
		 * @param  int 	$user_id $user_id 
		 * @return array     	 array of ojects
		 *
		 */	 		 
		public function bbclms_get_all_user_courses( $user_id ) {
	
			$courses_registered = ld_get_mycourses( $user_id ); 

			$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
			$course_progress = empty( $usermeta ) ? array() : $usermeta;
			 
			// We want to make sure we show ALL the courses from both the $courses_registered and 
			// the course_progress. Also we want to run through WP_Query so we can ensure they still 
			// exist as valid posts AND we want to sort these alphs by title
			$courses_registered = array_merge( $courses_registered, array_keys($course_progress));
			if ( !empty( $courses_registered ) ) {
				$course_total_query_args = array(
					'post_type'			=>	'sfwd-courses',
					//'fields'			=>	'ids',
					'nopaging'			=>	true,
					'orderby'			=>	'title',
					'order'				=>	'ASC',
					'post__in'			=>	$courses_registered
				);
				
				$course_total_query = new WP_Query( $course_total_query_args );
				
				if ( ( isset( $course_total_query->posts ) ) && ( !empty( $course_total_query->posts ) ) ) {
					$courses_registered = $course_total_query->posts;
				}
			}			
			
			return $courses_registered;
		}

		/**
		 * Register the widgets.
		 * 		 
		 */
		public function bbclms_register_widgets() {
			
			// Widget List (key => value is filename => widget class).
			$widget_list =  array( 	'course-progress' 	=> 'Course_Progress', 'course-trainer' 	=> 'Course_Trainer', 'course-classmates' => 'Course_Classmates' );
			
			foreach ( $widget_list as $key => $value ) {
				
				if ( file_exists( BBCLMS_CUSTOMIZATION_PLUGIN_DIR . '/widgets/widget-bbclms-' . esc_attr( $key ) . '.php' ) ) {
					
					require_once( BBCLMS_CUSTOMIZATION_PLUGIN_DIR . '/widgets/widget-bbclms-' . esc_attr( $key ) . '.php' );
					register_widget( 'BBCLMS_' . $value . '_Widget' );
				}
			} // End For Loop
		} // End register_widgets()		
		
		/**
		 * Add events_category filter to admin events list.
		 *
		 */		
		public function bbclms_events_category_filter() {
			global $typenow;
			
			$taxonomy = 'events_category';
			if( $typenow == 'events' ){
			
				$filters = array($taxonomy);
				foreach ($filters as $tax_slug) {
					$tax_obj = get_taxonomy($tax_slug);
					$tax_name = $tax_obj->labels->name;
					$terms = get_terms($tax_slug);
					
					echo '<select name="'.$tax_slug.'" id="'.$tax_slug.'" class="'.postform.'">';
					echo '<option value="">Show All '.$tax_name.'</option>';					
					foreach ($terms as $term){
						$selected = ($_GET[$tax_slug] == $term->slug) ? ' selected="selected"' : '';
						echo '<option value="'. $term->slug.'"'.$selected.' >' . $term->name .'</option>'; 
					}
					echo '</select>';			
				}
			}
		}
		
		/**
		 * Prevents user update and throw admin error in case user is member of multiple groups with the same course
		 *
		 */		
		public function bbclms_check_user_assigned_groups( $errors, $update, $user ) {	
			
			if( !$user ){
				$user = get_userdata( $_POST['user_id'] );
			}		
			
			$all_user_group_ids = learndash_get_users_group_ids( $user -> ID );
			
			foreach( $all_user_group_ids as $ugid ){
			
				// get group course
				$group_enrolled_courses[$ugid] = learndash_group_enrolled_courses( $ugid )[0];
				$i++;
			}
			
			$counts = array_count_values($group_enrolled_courses);
			$filtered_group_enrolled_courses = array_filter($group_enrolled_courses, function ($value) use ($counts) {
				return $counts[$value] > 1;
			});

			if( !empty( $filtered_group_enrolled_courses ) ){
				$errors->add('multi_groups_same_course_error',__('<strong>ERROR:</strong> User is assigned to Multiple Groups which enrolled to the Course. <a href="#learndash_user_groups-'.$user -> ID.'"> please correct </a>'));
			}
		}		
				
		/**
		 * Register Buddy Panel menu for Trainer
		 *
		 */
		public function bbclms_register_bptrainer_menu() {
			register_nav_menu( 'left-panel-menu-trainer'	, __( 'BuddyPanel-Trainer', 'boss' ) );
		}
		
		/**
		 * block admin areas for certain users
		 *
		 */
		public function bbclms_blockusers_admin_areas() {
			global $pagenow;
			if ( is_admin() ){
				if ( current_user_can( 'subscriber' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {			
					wp_redirect( home_url() );
					exit;
				} elseif ( current_user_can( 'group_leader' ) &&  $pagenow == 'profile.php' ){
					wp_redirect( home_url() );
					exit;
				}
			} 
		}
		
		/**
		 * removes admin bar profilemenu for certain users
		 *
		 */
		public function bbclms_admin_bar_profile_remove() {
			global $wp_admin_bar;		
			if( current_user_can( 'group_leader' ) ||  current_user_can( 'subscriber' ) ){	
				$wp_admin_bar->remove_node('edit-profile');				
			}
		}
		
		/**
		 * changes course social group navigation
		 *
		 */		
		public function bbclms_change_course_social_group_navigation(){
					
			if ( ! bp_is_group() || bp_action_variable(0) == 'step' ) {
				return;
			}			
			
			$nav_items = buddypress() -> groups -> nav -> get();
			$args = [];			
			
			foreach($nav_items as $key => $val){				
				$parent_slug =  ( isset( buddypress()-> groups-> nav -> get($key) -> parent_slug ) ) ? buddypress()-> groups-> nav -> get($key) -> parent_slug : '' ;
				
				if( $parent_slug ){					
					$group_id = groups_get_id( $parent_slug );						
					$course_id = groups_get_groupmeta( $group_id, 'bp_course_attached' );
					
					if( $key == $parent_slug.'/home'){						
						$text = ($course_id) ? __( 'Course Social Group', 'bbclms' )  : __( 'Social Group', 'bbclms' ) ;						
						$args = [
							'name' => __( $text, 'bbclms' ),					
						];
						buddypress()->groups->nav->edit_nav( $args, 'home', $parent_slug );				
					
					} elseif($key == $parent_slug.'/members'){
						$text = ($course_id) ? __( 'Course Social Group Members', 'bbclms' )  : __( 'Social Group Members', 'bbclms' ) ;
						$args = [
							'name' => __( $text, 'bbclms' ),					
						];
						buddypress()->groups->nav->edit_nav( $args, 'members', $parent_slug );
						
					} elseif($key == $parent_slug.'/forum'){
						$text = ($course_id) ? __( 'Course Forum', 'bbclms' )  : __( 'Forum', 'bbclms' ) ;
						$args = [
							'name' => __( $text, 'bbclms' ),					
						];
						buddypress()->groups->nav->edit_nav( $args, 'forum', $parent_slug );
						
					} elseif($key == $parent_slug.'/experiences'){						
						
						$course_slug = get_post_field( 'post_name', get_post($course_id) );						
						$args = [
							'name' => __( 'Course', 'bbclms' ),
							'link' => site_url().'/courses/'.$course_slug
						];
						buddypress()->groups->nav->edit_nav( $args, 'experiences', $parent_slug );
					}					
				}			   
			}
		}	
		
		/**
		 * change profile tabs names
		 *
		 */
		public function bbclms_rename_profile_tabs() {	
			
			buddypress()->members->nav->edit_nav( array( 'name' => __( 'My Social Groups', 'bbclms' ) ), 'groups' );		  
		}		
		
		/**
		 * removes admin menu pages for certain users
		 *
		 */
		public function bbclms_remove_menu_pages() {
			if( current_user_can( 'group_leader' ) ||  current_user_can( 'subscriber' ) ){
				remove_menu_page('profile.php');
				remove_menu_page('vc-welcome');		
			}
		}		
		
		/**
		 * die handler, used for bad assignments upload
		 *
		 */
		public function bbclms_wp_die_handler( $message, $title = '', $back_link = '', $args = array() ) {		
			?>
			<?php get_header(); ?>

				<div id="primary" class="site-content page-full-width">
					<div id="content" role="main">

						<article id="post-0" class="post error404 no-results not-found">
							<header class="entry-header">
								<h2 class="entry-title">
									<span><?php echo $title; ?></span>   
								</h2>
							</header>

							<div class="entry-content">					
								<p class="message-p">	<?php echo $message;?> !</p>
								<?php echo $back_link; ?>
								
							</div><!-- .entry-content -->
						</article><!-- #post-0 -->

					</div><!-- #content -->
				</div><!-- #primary -->
			<style>
			.back-to-lesson-link{
				font-size: 20px;
				height: auto;
				line-height: 48px;
				min-width: 200px;
				padding: 0 15px 0 15px;
				width: auto;
			}
			.message-p {
				margin-left: 0 !important;
				padding-left: 0 !important;
				margin-top: 40px !important;
				font-size: 24px !important;
				line-height: 1.4 !important;
			}
			.allowed-extensions{
				font-size: 15px !important;
			}
			</style>
			<?php get_footer(); ?>
			<?php
			die();
		}
		
		/**
		 * check assignments upload for file type, size and max allowed uploads
		 *
		 */		
		public function bbclms_learndash_assignment_uploaded($assignment_post_id, $assignment_meta = '' ){			
			
			// get assignment meta
			$assignment_meta = get_post_meta($assignment_post_id);	
			extract($assignment_meta); //gives -> $file_name[0], $file_link[0], $user_name[0], $disp_name[0], $file_path[0], $user_id[0], $lesson_id[0], $course_id[0],, $lesson_title[0], $lesson_type[0]
			
			// get File Upload options
			$ld_file_upload_settings = get_option('ld_file_upload_settings');
			extract($ld_file_upload_settings); // gives -> $allowed_file_types, $allowed_file_size, $max_number_of_uploads
			
			// find file size 
			$filesize = filesize( rawurldecode( $file_path[0]) );	
			$bytes = number_format($filesize / 1048576, 2) . ' MB';  // 0.01 MB
			
			// find file extension
			$ext = pathinfo( rawurldecode( $file_path[0]), PATHINFO_EXTENSION );
			$allowed_exts = explode( ',', $allowed_file_types );
			
			// find back_link	
			$type = ( $lesson_type[0] == 'sfwd-lessons' ) ? 'lessons' : 'topic';
			$slug = get_post_field( 'post_name', $lesson_id[0] );
			$back_url = site_url().'/'.$type.'/'.$slug ;	
			$back_link = '<a   href="'.$back_url.'" class="back-btn btn back-to-lesson-link"><i class="fa fa-angle-left"></i> Back to: <strong>'.$lesson_title[0].'</strong></a>';
			
			// find all user assignements, published
			$students_assignements = $this -> bbclms_get_user_assignments_list( $user_id[0] );
			
			// compare all assignments for the given lesson/topic and count total
			$count_assignments = 0 ;
			foreach( $students_assignements as $sa ){
				if( $sa -> lesson_id == $lesson_id[0] ){
					$count_assignments ++;
				}
			}	

			// throw error if max number of uploads reached			
			if( $max_number_of_uploads && $count_assignments > $max_number_of_uploads ){
				
				wp_delete_post( $assignment_post_id, true );				
				$this -> bbclms_wp_die_handler('Only '.$max_number_of_uploads.' uploads allowed', 'Max number of uploads reached', $back_link );		
			}	

			// check if file extension is allowed and throw an error if not			
			if( !empty( $allowed_file_types ) && !in_array( $ext, $allowed_exts ) ) {
				
				wp_delete_post( $assignment_post_id, true );		
				$this -> bbclms_wp_die_handler('File extension ".'.$ext.'" not allowed. <br /> Allowed extensions: <span class="allowed-extensions">'.$allowed_file_types.'</span>', 'File extension not allowed', $back_link );
				
			}
			
			// check for file size and throw an error if allowed size exceeded
			if( $allowed_file_size & $bytes > $allowed_file_size ){
				
				wp_delete_post( $assignment_post_id, true );		
				$this -> bbclms_wp_die_handler('File size of '.$bytes.' exceeds '.$allowed_file_size.' MB allowed.', 'File too big', $back_link );		
			}
		}
		
		/**
		 * override for learndash_mark_complete function - \wp-content\plugins\sfwd-lms\includes\course\ld-course-progress.php
		 * we need a way to change a bit the assignment upload form ...
		 */
		public function bbclms_learndash_mark_complete( $post ) {
			
			// get File Upload options
			$ld_file_upload_settings = get_option('ld_file_upload_settings');
			extract($ld_file_upload_settings); // gives -> $allowed_file_types, $allowed_file_size, $max_number_of_uploads
			
			$allowed_file_types = ( $allowed_file_types ) ? 'Allowed file types : '.$allowed_file_types : '' ;
			$allowed_file_size = ( $allowed_file_size ) ? 'Allowed file size max: '.$allowed_file_size.' MB' : '' ;
			$max_number_of_uploads = ( $max_number_of_uploads ) ? 'Max number of uploads: '.$max_number_of_uploads : '' ;
			
			if ( lesson_hasassignments( $post ) ) {
				$ret = '
						<table id="leardash_upload_assignment">
							<tr>
							<u>' . __( 'Upload Assignment', 'learndash' ) . "</u>
							</tr>
							<tr>
								<td>
								<span style='color: rgba(246, 169, 22, 0.98);line-height: 1.4;display: block;'>".$allowed_file_types."<br/>".$allowed_file_size."<br />".$max_number_of_uploads."</span>
									<form name='uploadfile' id='uploadfile_form' method='POST' enctype='multipart/form-data' action='' accept-charset='utf-8' >
										<input type='file' name='uploadfiles[]' id='uploadfiles' size='35' class='uploadfiles' />
										<input type='hidden' value='" . $post->ID . "' name='post'/>
										<input type='hidden' name='uploadfile' value='". wp_create_nonce( 'uploadfile_'. get_current_user_id() .'_'. $post->ID ) ."'  />
										<input class='button-primary' type='submit' id='uploadfile_btn' value='" . __( 'Upload', 'learndash' ) . "'  />
									</form>
								</td>
							</tr>
						</table>
						";
				return $ret;

			} else {
				return learndash_mark_complete( $post );
			}
			
		}
		
		/**
		 * get assignements for a specific user
		 *
		 */
		public function bbclms_get_user_assignments_list( $user_id = '', $published = true ) {
			
			$user_q = '';
			$status_q = '';
			
			if( $user_id ){
				$user_q = '&author='.$user_id.''; 
			}
			
			if( $published ){
				$status_q = '&post_status=publish'; 
			}
			
			$posts = get_posts( 'post_type=sfwd-assignment&posts_per_page=-1'.$user_q.$status_q );

			if ( ! empty( $posts ) ) {
				foreach( $posts as $key => $p ) {
					$meta = get_post_meta( $p->ID, '', true );

					foreach( $meta as $meta_key => $value ) {

						if ( is_string( $value ) || is_numeric( $value ) ) {
							$posts[ $key ]->{$meta_key} = $value;
						} else if ( is_string( $value[0] ) || is_numeric( $value[0] ) ) {
							$posts[ $key ]->{$meta_key} = $value[0];
						}
						
						if ( $meta_key == 'file_path' ) {
							$posts[ $key ]->{$meta_key} = rawurldecode( $posts[ $key ]->{$meta_key} );
						}
					}
				}
			}			
			return $posts;
		}
		
		/**
		 * Add BP member types, Student and Trainer
		 *
		 */
		public function bclms_bp_learndash_members_directory(){
            ?>
            <li id="members-group_leader"><a href="<?php site_url(); ?>bpe-group_leader"><?php printf( __( 'Trainers <span>%s</span>', 'buddypress-learndash' ), bp_learndash_members_count_by_type('group_leader') ); ?></a></li>
            <li id="members-student"><a href="<?php site_url(); ?>bpe-student"><?php printf( __( 'Students <span>%s</span>', 'buddypress-learndash' ), bp_learndash_members_count_by_type('student') ); ?></a></li>
        <?php
        }
		
		/**
		 * Add  Assignment update meta 
		 * 
		 */		
		public function bbclms_add_assignment_update_meta_box(){
			if ( current_user_can( 'group_leader' ) ) {	
				add_meta_box("assignment_update_meta_box", "Update", array($this, 'bbclms_assignment_update_meta_box_markup' ), "sfwd-assignment", "side", "high", null);
			}			
		}
		
		/**
		 * Assignment update meta box markup
		 * 
		 */
		public function bbclms_assignment_update_meta_box_markup(){
			echo '<input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">';
		}		
		
		/**
		 * Remove metaboxes for group leader - disable trash in edit view
		 * 
		 */		
		public function bbclms_remove_meta_boxes() {
			if ( current_user_can( 'group_leader' ) ) {		
				remove_meta_box( 'submitdiv', 'sfwd-assignment', 'side' );		
				remove_meta_box( 'authordiv', 'sfwd-assignment', 'normal' );		
			}
		}		
		
		/**
		 * Remove some row actions for group leader - disable trash
		 * 
		 */		
		public function bbclms_remove_row_actions( $actions ){
			if( current_user_can('group_leader') ){
				if( get_post_type() === 'sfwd-assignment' ){				
					unset( $actions['editinline'] );
					unset( $actions['duplicate_post'] );
					unset( $actions['trash'] );
					unset( $actions['inline hide-if-no-js'] );
				}			
			}
			return $actions;
		}
		
		/**
		 * Wrapper for LD function \wp-content\plugins\sfwd-lms\includes\course\ld-course-functions.php
		 *
		 */
		public function bbclms_ld_update_course_access( $user_id, $course_id, $remove = false ) {
			$meta = ld_update_course_access( $user_id, $course_id, $remove );
			return $meta;
		}
		
		/**
		 * Clear course user access meta on user delete - it is quite weird that it remains there after user deleted ???
		 *
		 */
		public function bbclms_clear_course_access_delete_user( $user_id ) {
			
			// get all users courses and then loop to update course access.
			$user_enrolled_courses_ids = learndash_user_get_enrolled_courses( $user_id );
			foreach( $user_enrolled_courses_ids as $course_id ){
				ld_update_course_access( $user_id, $course_id, true ); // true to remove ...
			}
		}

		/**
		 * Clear course groups enrolled - it is quite weird that it remains there after group deleted ???
		 *
		 */
		public function bbclms_clear_course_groups_delete_group( $group_id ) {			
			if( !$group_id ){
				return; 
			}			
			if ( 'groups' == get_post_type( $group_id ) ){
				
				// get all groups courses and then loop to update course group access.
				$group_enrolled_courses_ids = learndash_group_enrolled_courses( $group_id );			    
				foreach( $group_enrolled_courses_ids as $course_id ){					
					ld_update_course_group_access( $course_id, $group_id, true ); // true to remove ...
				}
				
				// also remove group coms_id/lms_id pair form table
				$this -> dbhandle -> bbclms_delete_group_lms_coms_ids( $group_id );
			}
		}
		
		/**
		 * Wrapper for LD function \wp-content\plugins\sfwd-lms\includes\ld-groups.php -> learndash_set_users_group_ids( $user_id , $user_groups_new  );
		 *
		 */
		public function bbclms_learndash_set_users_group_ids( $user_id = 0, $user_groups_new = array() ) {			
			
			if ( !empty($user_id ) ) {
		
				$user_groups_old = learndash_get_users_group_ids( $user_id, true );

				$user_groups_intersect = array_intersect( $user_groups_new, $user_groups_old );

				$user_groups_add = array_diff( $user_groups_new, $user_groups_intersect );			
		

				if ( !empty( $user_groups_add ) ) {
					foreach ( $user_groups_add as $group_id ) {
						ld_update_group_access( $user_id, $group_id, false);
					}
				}
			
				// Finally clear our cache for other services 
				$transient_key = "learndash_user_groups_" . $user_id;
				delete_transient( $transient_key );
			}			
		}
		
		/**
		 * Wrapper for LD function \wp-content\plugins\sfwd-lms\includes\ld-groups.php -> learndash_set_administrators_group_ids( $user_id,  
		 * $leader_groups_new = array() )
		 *
		 */
		public function bbclms_learndash_set_administrators_group_ids( $user_id, $leader_groups_new = array() ) {
			global $wpdb;

			if ( !empty($user_id ) ) {
				
				$leader_groups_old = learndash_get_administrators_group_ids( $user_id, true );
				$leader_groups_intersect = array_intersect( $leader_groups_new, $leader_groups_old );

				$leader_groups_add = array_diff( $leader_groups_new, $leader_groups_intersect );
				if ( !empty( $leader_groups_add ) ) {
					foreach ( $leader_groups_add as $group_id ) {
						ld_update_leader_group_access( $user_id, $group_id, false);
					}
				}				
			}
			return array();	
		}
		
		/**
		 * Wrapper for LD function \wp-content\plugins\sfwd-lms\includes\ld-groups.php
		 *
		 */
		public function bbclms_ld_update_group_access( $user_id, $group_id, $remove = false ) {
			ld_update_group_access( $user_id, $group_id, $remove );
		}
		
		/**
		 * Set HTML to wp_mail
		 */
		public function set_html_content_type() {
			return 'text/html';
		}
		
		/**
		 * CSV Import - sending mail to users
		 *
		 */
		public function bbclms_user_account_activation( $subject = '', $to = '', $headers = '', $data = array(), $html_mail = true ) {
			
			$sent = 0;
			
			if( !$subject || !$to || !$headers || !$data ){
				return $sent;
			}
			
			if( $html_mail ){
				add_filter( 'wp_mail_content_type', array($this,'set_html_content_type') );	
			}			
			
			$template ='<table>
							<tr>
								<td colspan="2" >Congratulations '.$data['name'].', your account has been activated:</td>					
							</tr>
							<tr>
								<td>Username: </td>
								<td>'.$data['username'].'</td>
							</tr>
							
							<tr>
								<td>Password: </td>
								<td>'.$data['password'].'</td>
							</tr>
							<tr>
								<td>Email: </td>
								<td>'.$data['email_address'].'</td>
							</tr>
							<tr>
								<td>Please click on the link to login: </td>
								<td>'.site_url().'</td>
							</tr>
							<tr>
								<td colspan="2">Contact Us: +61 2 9299 4999</td>
													   
							</tr>
							<tr>
								<td colspan="2" ><a href="' . site_url() . '"><img src="' . site_url() . '/wp-content/uploads/2016/03/bbc-logo.png" width="150" height="90" alt="logo" /></a></td>										
							</tr>
						</table>';
						
			$sent = wp_mail( $to, $subject, $template, $headers );
			
			if( $html_mail ){
				remove_filter( 'wp_mail_content_type',  array($this,'set_html_content_type') );
			}

			return $sent;			
		}
		
		/**
		 * Wp CRON - add custom interval - half hour
		 *
		 */
		public function bbclms_cron_add_half_hour( $schedules ) {
			// Adds once every minute to the existing schedules.
			$schedules['every_half_hour'] = array(
				//'interval' => 1800,
				'interval' => 300,
				'display' => __( 'Once Every Half Hour' )
			);
			return $schedules;
		}

		/**
		 * Wp CRON - create a scheduled event (if it does not exist already)
		 *
		 */
		public function bbclms_cron_csvimport_activation() {
			if( !wp_next_scheduled( 'mycronjob_csvimport' ) ) {  
			   wp_schedule_event( time(), 'every_half_hour', 'mycronjob_csvimport' );  
			}
		}
		
		
		/**
		 * Wp CRON - add custom interval - for testing
		 *
		 */
		public function bbclms_cron_add_three_minutes( $schedules ) {
			// Adds once every minute to the existing schedules.
			$schedules['every_three_minutes'] = array(
				'interval' => 180,
				'display' => __( 'Once Every Three Minutes' )
			);
			return $schedules;
		}		

		/**
		 * Wp CRON - create a scheduled event (if it does not exist already) - testing
		 *
		 */
		public function bbclms_cronstarter_activation() {
			if( !wp_next_scheduled( 'mycronjob' ) ) {  
			   wp_schedule_event( time(), 'every_three_minutes', 'mycronjob' );  
			}
		}		

		/**
		 * Wp CRON - function we'd like to call with our cron job - testing
		 *
		 */
		public function bbclms_testmail_repeat_function() {				
			
			$recepients = 'brbaso@gmail.com';
			$subject = 'Testing WP Cron Job - real GoDaddy Unix Cron';
			$message = 'PLUgin - This is from real GoDaddy Unix Cron Job, pointing to WP Scheduled events';
			//$message = 'This is from WP pseudo Cron Job';	
			// let's send it 
			mail($recepients, $subject, $message);
		}
		
		/**
		 * Get published courses array of objects
		 * @param  array 		$args  array of posts arguments
		 * @param  bool 		$course_groups  if true returns courses with groups associated
		 * @return string     	 lesson id
		 */
		public function bbclms_ld_items_list( $args = array() ) {			
			
			$attr_defaults = array(
				'num' => '-1', 
				'post_type' => 'sfwd-courses', 
				'post_status' => 'publish', 
				'order' => 'ASC', 
				'orderby' => 'ID', 
				'mycourses' => false, 
				'post__in'	=> null,

				'meta_key' => '', 
				'meta_value' => '', 
				'meta_compare' => '',
				
				'tag' => '', 
				'tag_id' => 0, 
				'tag__and' => '', 
				'tag__in' => '', 
				'tag__not_in' => '', 
				'tag_slug__and' => '', 
				'tag_slug__in' => '', 

				'cat' => '', 
				'category_name' => 0, 
				'category__and' => '', 
				'category__in' => '', 
				'category__not_in' => '', 

				'tax_compare' => 'AND',
				'categoryselector' => '', 
				
				'author__in' => '', 
				'col' => '',
				'progress_bar' => false,
				'array' => false,
			);

			$post_type_slug = 'course';
			$post_type_Class = 'LearnDash_Settings_Courses_Taxonomies';
			
			$attr = wp_parse_args( $args, $attr_defaults );
			
			if ( ( isset( $attr['post_type'] ) ) && ( !empty( $attr['post_type'] ) ) ) {	
				if ( $attr['post_type'] == 'sfwd-lessons' ) {
					$post_type_slug = 'lesson';
					$post_type_Class = 'LearnDash_Settings_Lessons_Taxonomies';
				} else if ( $attr['post_type'] == 'sfwd-topic' ) {
					$post_type_slug = 'topic';
					$post_type_Class = 'LearnDash_Settings_Topics_Taxonomies';
				}
			}
			
			if ( !empty( $post_type_slug ) ) {
				$attr_defaults = array_merge(
					$attr_defaults,
					array(
						$post_type_slug . '_categoryselector' => '',
						$post_type_slug . '_cat' => '',
						$post_type_slug . '_category_name' => '',
						$post_type_slug . '_category__and' => '',
						$post_type_slug . '_category__in' => '',
						$post_type_slug . '_category__not_in' => '',
			
						$post_type_slug . '_tag' => '',
						$post_type_slug . '_tag_id' => '',
						$post_type_slug . '_tag__and' => '',
						$post_type_slug . '_tag__in' => '',
						$post_type_slug . '_tag__not_in' => '',
						$post_type_slug . '_tag_slug__and' => '',
						$post_type_slug . '_tag_slug__in' => '',
					)
				);
			}
			
			
			extract( $attr );			
			global $post;
			
			$filter = array(
				'post_type' => $post_type, 
				'post_status' => $post_status, 
				'posts_per_page' => $num, 
				'order' => $order, 
				'orderby' => $orderby
			);
			
			
			$items = get_posts( $filter );
			if( !$attr['post_type'] == 'groups' ){				
				return $items;
			} else {					
				foreach($items as &$it){
					$group_courses = $this ->  bbclms_get_group_courses( $it -> ID );
					$it -> group_courses = $group_courses;
				}				
				return $items;				
			}
		}
	
		/**
		 * Get LESSON EVENTS
		 * 
		 */
		/* NOT ACTIVE ANYMORE
		public function bbclms_get_lesson_events( $lesson_id ) {
			global $wpdb;

			$events = array();
			
			if ( !empty( $lesson_id ) ) {		
								
				$sql_str = "SELECT post_id FROM ". $wpdb->postmeta ." WHERE meta_key = 'event_group_leader_group_course_lesson' AND meta_value = %d ";
				$event_ids = $wpdb->get_col(  $wpdb->prepare( $sql_str, $lesson_id ) );
				
				$count_event_ids = count($event_ids);
				$placeholders = array_fill(0, $count_event_ids, '%d');	
				$placeholders_implode = implode(',', $placeholders); // %d,%d
				
				$query ="SELECT * FROM ". $wpdb->posts ." as posts 
						INNER JOIN ". $wpdb->prefix ."event_hours as event_hours 
						ON posts.ID = event_hours.event_id 
						WHERE posts.ID IN(".$placeholders_implode.") 
						AND posts.post_status = 'publish'";
				
				$events = $wpdb->get_results( $wpdb->prepare( $query, $event_ids) );
				
			}			

			$data = array();
			foreach ($events as $item) {
				$key = $item -> ID; //
				if (!isset($data[$key])) {
					$data[$key] = array();
				}

				$data[$key][] = $item;
			}

			return $data ;				
		}
		*/
		
		/**
		 * Get lesson id of resource
		 *
		 * @since 2.1.0
		 * 
		 * @param  int 		$id  post id of resource
		 * @return string     	 lesson id
		 */
		public function bbclms_learndash_get_lesson_id( $id = null ) {
			global $post;

			if ( empty( $id ) ) {
				if ( ! is_single() || is_home() ) {
					return false;
				}

				$id = $post->ID;
			}

			return get_post_meta( $id, 'lesson_id', true );
		}
				
		/**
		 * Get LearnDash setting for a post
		 * 
		 * @since 2.1.0
		 * 
		 * @param  id|obj $post    
		 * @param  string $setting 
		 * @return string value for requested setting
		 */
		public function bbclms_learndash_get_setting( $post, $setting = null ) {

			if ( is_numeric( $post ) ) {
				$post = get_post( $post );
			} else {
				if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
					return null;
				}
			}
			
			if ($post instanceof WP_Post) {

				if ( $setting == 'lesson' ) {
					return $this -> bbclms_learndash_get_lesson_id( $post->ID ); 
				}
				

				if ( $setting == 'course' ) {
					return get_post_meta( $post->ID, 'course_id', true ); 
				}

				$meta = get_post_meta( $post->ID, '_' . $post->post_type, true );
				if ( ( !empty( $meta ) ) && ( is_array( $meta ) ) ) {
					if ( empty( $setting ) ) {
						$settings = array();
						foreach ( $meta as $k => $v ) {
							$settings[ str_replace( $post->post_type.'_', '', $k ) ] = $v;
						}
						return $settings;
					} else {
						if ( isset( $meta[ $post->post_type.'_'.$setting ] ) ) {
							return $meta[ $post->post_type.'_'.$setting ]; 
						} else {
							return ''; 
						}
					}
				} else {
					return ''; 
				}
			}
		}	
		
		/**
		 * force advanced TinyMCE
		 *		 
		 */	
		//add_filter( 'tiny_mce_before_init', 'bbclms_formatTinyMCE' );
		public function bbclms_formatTinyMCE( $in ) {
			$in['wordpress_adv_hidden'] = FALSE;
			return $in;
		}
		
		/**
		 * tinymce add buttons 2
		 *		 
		 */		
		//add_filter( 'mce_buttons_2', 'bbclms_mce_buttons_2' );
		public function bbclms_mce_buttons_2( $buttons ) {
			$buttons[] = 'paste';
			$buttons[] = 'underline';
			//$buttons[] = 'redo';
			//$buttons[] = 'link';
			//$buttons[] = 'unlink';
			$buttons[] = 'image';
			//$buttons[] = 'formatselect';
			$buttons[] = 'fontselect';
			$buttons[] = 'fontsizeselect';
			$buttons[] = 'styleselect';
			//$buttons[] = 'forecolor';
			$buttons[] = 'backcolor';
			$buttons[] = 'forecolorpicker';
			$buttons[] = 'backcolorpicker';
			//$buttons[] = 'charmap';
			$buttons[] = 'anchor';

			return $buttons;
		}
		
		/**
		 * tinymce add buttons 3
		 *		 
		 */	
		//add_filter( 'mce_buttons_3', 'bbclms_mce_buttons_3' );
		public function bbclms_mce_buttons_3( $buttons ) {	
			$buttons[] = 'superscript';
			$buttons[] = 'subscript';
			$buttons[] = 'cut';
			$buttons[] = 'copy';
			$buttons[] = 'searchreplace';
			$buttons[] = 'insertdatetime';
			$buttons[] = 'advimage';
			$buttons[] = 'emotions';
			$buttons[] = 'media';

			return $buttons;
		}

		/**
		 *  Assigns customize_text_sizes() to "tiny_mce_before_init" filter
		 *		 
		 */		
		//add_filter('tiny_mce_before_init', 'bbclms_mce_text_sizes');
		public function bbclms_mce_text_sizes($initArray){
		   $initArray['fontsize_formats'] = "10px 11px 12px 13px 14px 15px 16px 17px 18px 19px 20px 21px 22px 23px 24px 25px 26px 27px 28px 29px 30px 32px 48px";
		   return $initArray;
		}		
		
		/**
		 * Register the stylesheets -admin
		 *
		 * @since    1.0.0
		 */
		public function bbclms_admin_enqueue_styles() {
			wp_enqueue_style( 'bbclms', BBCLMS_CUSTOMIZATION_PLUGIN_URL . 'css/bbclms-admin.css', array(), '1.0.0', 'all' );			
		}
		
		/**
		 * Register the JavaScript - admin 
		 *
		 * @since    1.0.0
		 */
		public function bbclms_admin_enqueue_scripts() {		
			wp_enqueue_script( 'bbclmsadmin', BBCLMS_CUSTOMIZATION_PLUGIN_URL . 'js/bbclms.js', array( 'jquery' ), '1.0.0', false );			
		}
		
		/**
		 * Register the stylesheets
		 *
		 * @since    1.0.0
		 */
		public function bbclms_enqueue_styles() {
			//wp_enqueue_style( 'bbclms', BBCLMS_CUSTOMIZATION_PLUGIN_URL . 'css/bbclms.css', array(), '1.0.0', 'all' );
			//wp_enqueue_style( 'bbclmsuikit', BBCLMS_CUSTOMIZATION_PLUGIN_URL . 'css/uikit.css', array(), '1.0.0', 'all' );
		}
		
		/**
		 * Register the JavaScript 
		 *
		 * @since    1.0.0
		 */
		public function bbclms_enqueue_scripts() {		
			wp_enqueue_script( 'bbclms', BBCLMS_CUSTOMIZATION_PLUGIN_URL . 'js/bbclms.js', array( 'jquery' ), '1.0.0', false );			
			wp_localize_script( 'bbclms', 'ajax_url', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}


    }
    BbclmsFunctions::instance();

endif;
