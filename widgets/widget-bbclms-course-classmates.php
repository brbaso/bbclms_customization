<?php
/**
 * Course Classmates Widget
 *
 *
 * @package WordPress 
 * @category Widgets
 * @author Slobodan Brbaklic
 * Adjusted Course Participants Widget by BuddyBoss
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BBCLMS_Course_Classmates_Widget extends WP_Widget {
	
	protected $bbclms_classmates_widget_cssclass;
	protected $bbclms_classmates_widget_description;
	protected $bbclms_classmates_widget_idbase;
	protected $bbclms_classmates_widget_title;

	/**
	 * Constructor function.
	 * @since  1.1.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->bbclms_classmates_widget_cssclass = 'widget_learndash_course_classmates';
		$this->bbclms_classmates_widget_description = sprintf( __( 'Displays a list of learners in the same LearnDash Group, taking the current %s, with links to their profiles (if public).', 'bbclms' ), LearnDash_Custom_Label::label_to_lower( 'course' ) );
		$this->bbclms_classmates_widget_idbase = 'widget_learndash_course_participants';
		$this->bbclms_classmates_widget_title = sprintf( __( '(BBCLMS) - %s Classmates', 'bbclms' ), LearnDash_Custom_Label::get_label( 'course' ) );
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->bbclms_classmates_widget_cssclass, 'description' => $this->bbclms_classmates_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->bbclms_classmates_widget_idbase );

		/* Create the widget. */
		parent::__construct( $this->bbclms_classmates_widget_idbase, $this->bbclms_classmates_widget_title, $widget_ops, $control_ops );
	}

    /**
	 * Display the widget on the frontend.
	 * @since  1.0.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		global $current_user, $wpdb;
        $bp = buddypress();
		extract( $args );

		//Array of course participants user ids
		$learners = array();

		if ( ( is_singular( array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic') ) ) ) {
			if ( get_post_type() == 'sfwd-courses' ) {
				$course_id = get_the_ID();
			}

			if ( get_post_type() == 'sfwd-lessons' ) {
				$course_id = get_post_meta(get_the_ID(),'course_id',true);
			}

			if ( get_post_type() == 'sfwd-topic' ) {
				$lesson_id = get_post_meta(get_the_ID(),'lesson_id',true);
				$course_id = get_post_meta($lesson_id,'course_id',true);
			}

			//Select group id attached with course
			$sql        = "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE
						   meta_key = 'bp_course_attached' AND meta_value = {$course_id}";

			$group_id   = $wpdb->get_var( $sql );

		} else {
			$course_id = groups_get_groupmeta( bp_get_group_id(), 'bp_course_attached', true );
			$group_id = bp_get_group_id();
		}
		
		$post = get_post( $course_id );
		
		$the_group_leader =  bbclms_custom() -> functions -> bbclms_get_the_group_leader( $post, $current_user );
		//echo '<pre> The Group Leader: '; print_r( $current_user ); die;	
		
		$course_groups = learndash_get_course_groups( $post->ID, true );
		$all_group_leaders = learndash_all_group_leaders();
		
		$course_group_courses_ids = learndash_get_groups_courses_ids();
		$users_group_ids = learndash_get_users_group_ids( $current_user->ID );		
		
		// TODO find a better way to find Group and to be sure that we are in the right Group
		$user_course_group = array_merge(array_intersect($users_group_ids, $course_groups )) ;
		
		$group_id = $user_course_group[0];

		/* Select all users from course access list and add them to course participants list */
		$meta = get_post_meta( $course_id, '_sfwd-courses', true );
		if ( ! empty( $meta['sfwd-courses_course_access_list'] ) ) {
			$learners = explode( ',', $meta['sfwd-courses_course_access_list'] );
		}

        //group id will be empty if the course is not associated with any group
        if( $group_id ){
            /* Select all members from group attached with course and add them to course participants list */
            $sql            = "SELECT user_id FROM {$bp->groups->table_name_members} WHERE is_banned = 0 AND is_confirmed = 1 AND
                               group_id = {$group_id}";
            $group_members  = $wpdb->get_col( $sql );
        }

		if ( ! empty( $group_members ) ) {
			$learners = array_merge( $learners, $group_members );
		}
		


		$learners = learndash_get_groups_users( $group_id, true ); // learndash_get_groups_user_ids
		$current_user_id = $current_user -> ID;
		
		$learners_ids = learndash_get_groups_user_ids( $group_id, true ); // learndash_get_groups_user_ids
		$learners_ids = array_diff($learners_ids, array($current_user_id));
		


		if ( isset( $instance['title'] ) ) {
			$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );
		}
		if ( isset( $instance['limit'] ) && ( 0 < count( $instance['limit'] ) ) ) {
			$limit = intval( $instance['limit'] );
		}

		// Frontend Output
		echo $before_widget;		

		// Add actions for plugins/themes to hook onto.
		do_action( $this->bbclms_classmates_widget_cssclass . '_top' );

		$html = '';
		if( empty( $learners ) ) {
			$html .= '<p>' . __( 'There are no other learners currently taking this course. Be the first!', 'bbclms' ) . '</p>';			
		} else {
			
			/* Display the widget title if one was input */
			if ( $title ) { echo $before_title . $title . $after_title; }

			$list_class = 'list';
			$html .= '<ul class="learndash-course-classmates-list' . ' ' . $list_class . '">';

			// Begin templating logic.
			$tpl = '<li class="learndash-course-classmate fix %%CLASS%%">%%IMAGE%%%%TITLE%%</li>';
			$tpl = apply_filters( 'learndash_course_classmates_template', $tpl );

			$i = 0;
			
            $args = array(
                'include'   => $learners_ids,                
            );
            
                foreach( $learners as $l ){					
                
				if( in_array( $l -> ID, $learners_ids ) ){
					
					if ( function_exists('bp_is_active') ) {
						$l_avatar = bp_core_fetch_avatar ( array( 'item_id' => $l -> ID , 'type' => 'full', 'width' => '70', 'height' => '70' ) );
						$l_url = bp_core_get_user_domain( $l -> ID );
						$lrn = '<a href="' . $l_url . '">' . bp_core_get_user_displayname( $l -> ID ) . '</a>';  
					} else {
						$l_avatar = get_avatar( $the_group_leader -> ID, 70 );
						$lrn = get_the_author_meta( 'display_name', $l -> ID );
					}						
                  
						$i++;
						$class = $i <= $limit ? 'show' : 'hide';				
						
						$link = '<a class="debug" href="' . $l_url . '" title="' . __( 'View public learner profile', 'bbclms' ) . '">';						
						
						$image = '<figure itemprop="image">' . $l_avatar . '</figure>' . "\n";
						
						$image = $link . $image . '</a>';						
					   
						$learner_name = $link . bp_core_get_user_displayname( $l -> ID ) . '</a><a data-toggler="'.$l -> ID.'" class="form_toggler fa fa-envelope-o" href="#"  title="' . __( 'Send Message to classmate', 'bbclms' ) . '"></a>';
						
						$template = $tpl;
						$template = str_replace( '%%CLASS%%', $class, $template );
						$template = str_replace( '%%IMAGE%%', $image, $template );
						$template = str_replace( '%%TITLE%%', $learner_name, $template );

						$html .= $template;
						
						
						$html .= '<div id="form_toggler_'.$l -> ID.'" class="cassmate-msg-box" >';						
						$html .= '<div id="form_wrapper" >';
						$html .= '<h5 id="bbclms_private_message">' . __( 'Message', 'boss-learndash' ) . '</h5>';
						$html .= '<p>';
						$html .= '</p>';
						$html .= '<form name="contact-trainer" action="" method="post" class="contact-trainer" id="form_contact_trainer">';
						$html .= '<p class="form-row form-row-wide">';
						$html .= '<textarea class="bbclms-trainer-message" name="contact_message" placeholder="' . __( 'Enter your private message.', 'bbclms' ) . '"></textarea>';
						$html .= '</p>';
						$html .= '<p class="form-row">';
						$html .= '<input type="hidden" class="bbclms-trainer-msg-course-id" name="post_id" value="' . $post->ID . '" />';
						$html .= '<input type="hidden" class="bbclms-trainer-msg-sender-id" name="sender_id" value="' . $current_user->ID . '" />';
						$html .= '<input type="hidden" class="bbclms-trainer-msg-receiver-id" name="receiver_id" value="' . $l -> ID . '" />';
						$html .= '<input type="hidden" class="bbclms-trainer-msg-receiver-name" name="receiver_name" value="' . bp_core_get_user_displayname( $l -> ID ) . '" />';
						$html .= wp_nonce_field( 'message_trainer', 'bbclms-trainer_learndash_message_trainer_nonce', true, false );
						$html .= '<input type="submit" class="bbclms-send-message-widget" value="' . __( 'Send Message to ' . bp_core_get_user_displayname( $l -> ID ) , 'bbclms' ) . '" />';
						$html .= '</p>';
						$html .= '<div class="fix"></div>';
						$html .= '</form>';
						$html .= '</div>';
						
						$html .= '</div>';
					}
				}
                
                $html .= '</ul>';
                // Display a view all link if not all learners are displayed.
                if( $limit < count( $learners ) ) {
                    $html .= '<div class="learndash-view-all-participants"><a href="#">' . __( 'View all', 'bbclms' ) . '</a></div>';
                }

            

		}

		echo $html;

		// Add actions for plugins/themes to hook onto.
		do_action( $this->bbclms_classmates_widget_cssclass . '_bottom' );

		echo $after_widget;
	} // End widget()
    
	/**
	 * Method to update the settings from the form() method.
	 * @since  1.0.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and limit to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['limit'] = intval( $new_instance['limit'] );
		$instance['size'] = intval( $new_instance['size'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['display'] = esc_attr( $new_instance['display'] );

		return $instance;
	} // End update()

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 * @since  1.0.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
    public function form( $instance ) {

		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
						'title' => '',
						'limit' => 5,
						'size' => 50,
						'display' => 'list'
					);

		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title (optional):', 'bbclms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php _e( 'Number of Learners (optional):', 'bbclms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>

<?php
	} // End form()


	/**
	 * Get an array of the available display options.
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_display_options () {
		return array(
					'list' 			=> __( 'List', 'bbclms' ),
					'grid' 			=> __( 'Grid', 'bbclms' )
					);
	} // End get_display_options()
}