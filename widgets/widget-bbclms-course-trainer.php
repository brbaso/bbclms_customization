<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Course Trainer Widget
 *
 * A Course Trainer Widget widget to display Course Trainer(Group Leader) Contact.
 *
 * @package WordPress
 * @subpackage BBCLMS Customization
 * @category Widgets
 * @author Slobodan Brbaklic
 * @since 1.0.0
 * 
 */
 
class BBCLMS_Course_Trainer_Widget extends WP_Widget {
	
	protected $bbclms_trainer_widget_cssclass;
	protected $bbclms_trainer_widget_description;
	protected $bbclms_trainer_widget_idbase;
	protected $bbclms_trainer_widget_title;

	/**
	 * Constructor function.
	 * @since  1.1.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->bbclms_trainer_widget_cssclass = 'widget_course_trainer widget_course_trainer_contact';
		$this->bbclms_trainer_widget_description = sprintf( __( 'This widget will output details about %s teacher', 'bbclms' ), LearnDash_Custom_Label::get_label( 'course' ) );
		$this->bbclms_trainer_widget_idbase = 'widget_course_trainer';
		$this->bbclms_trainer_widget_title =  sprintf( __( '(BBCLMS) - %s Trainer', 'bbclms' ), LearnDash_Custom_Label::get_label( 'course' ) );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->bbclms_trainer_widget_cssclass, 'description' => $this->bbclms_trainer_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->bbclms_trainer_widget_idbase );

		/* Create the widget. */
        parent::__construct( $this->bbclms_trainer_widget_idbase, $this->bbclms_trainer_widget_title, $widget_ops, $control_ops );
	} // End __construct()

	/**
	 * Display the widget on the frontend.
	 * @since  1.1.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );
        if( ( is_singular( array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic') ) ) || groups_get_groupmeta( bp_get_group_id(), 'bp_course_attached', true )) {
            /* Before widget (defined by themes). */
            echo $before_widget;

            /* Display the widget title if one was input (before and after defined by themes). */
            if ( $title ) { echo $before_title . $title . $after_title; }

            /* Widget content. */
            // Add actions for plugins/themes to hook onto.
            do_action( $this->bbclms_trainer_widget_cssclass . '_top' );
   
            $this->load_component( $instance );   

            // Add actions for plugins/themes to hook onto.
            do_action( $this->bbclms_trainer_widget_cssclass . '_bottom' );

            /* After widget (defined by themes). */
            echo $after_widget;
        }

	} // End widget()

	/**
	 * Method to update the settings from the form() method.
	 * @since  1.1.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	} // End update()

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 * @since  1.1.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
    public function form( $instance ) {

		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
						'title' => ''
					);

		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title (optional):', 'bbclms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>

<?php
	} // End form()
	/**
	 * Load the output.
	 * @param  array $instance.
	 * @since  1.1.0
	 * @return void  
	 */
	protected function load_component ( $instance ) {
		if ( is_user_logged_in() ) {
			
			$course_by_text = __( 'Your Trainer', 'bbclms' );
			$text = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance );
			
			$queried_object = get_queried_object();
			
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
			} else {
				 $course_id = groups_get_groupmeta( bp_get_group_id(), 'bp_course_attached', true );
			}

			$post = get_post( $course_id );
			
			global $current_user;
			wp_get_current_user();

			$the_group_leader =  bbclms_custom() -> functions -> bbclms_get_the_group_leader( $post, $current_user );
			
		
			
			if ( function_exists('bp_is_active') ) {
				$author_avatar = bp_core_fetch_avatar ( array( 'item_id' => $the_group_leader -> ID , 'type' => 'full', 'width' => '70', 'height' => '70' ) );
				$author_url = bp_core_get_user_domain( $the_group_leader -> ID );
				$author = '<a href="' . $author_url . '">' . bp_core_get_user_displayname( $the_group_leader -> ID ) . '</a>';  
			} else {
				$author_avatar = get_avatar( $the_group_leader -> ID, 70 );
				$author = get_the_author_meta( 'display_name', $the_group_leader -> ID );
			}
			
			if ( $the_group_leader ) {
			?>
			<header id="<?php $id = uniqid('trainer-widget-'); echo $id; ?>">
				<span>
					<p><?php echo apply_filters( 'bb_course_by_text', $course_by_text ); ?></p>
					<p><?php echo $author; ?></p>
				</span>
				<?php echo $author_avatar; ?>
			</header>
			<div class="authors-word">
				 <?php echo get_the_author_meta( 'description', $the_group_leader -> ID ); ?> 
			</div>
			<footer>          
				<?php

				if ( bp_is_active( 'messages' ) ) {
					if( ! is_user_logged_in() ){
						return;
					}
					
					$html = '';	
					$html .= '<div id="form_wrapper" >';
					$html .= '<h3 id="bbclms_private_message">' . __( 'Send Private Message', 'boss-learndash' ) . '</h3>';
					$html .= '<p>';
					$html .= '</p>';
					$html .= '<form name="contact-trainer" action="" method="post" class="contact-trainer" id="form_contact_trainer">';
					$html .= '<p class="form-row form-row-wide">';
					$html .= '<textarea class="bbclms-trainer-message" name="contact_message" placeholder="' . __( 'Enter your private message.', 'bbclms' ) . '"></textarea>';
					$html .= '</p>';
					$html .= '<p class="form-row">';
					$html .= '<input type="hidden" class="bbclms-trainer-msg-course-id" name="post_id" value="' . $post->ID . '" />';
					$html .= '<input type="hidden" class="bbclms-trainer-msg-sender-id" name="sender_id" value="' . $current_user->ID . '" />';
					$html .= '<input type="hidden" class="bbclms-trainer-msg-receiver-id" name="receiver_id" value="' . $the_group_leader -> ID . '" />';
					$html .= '<input type="hidden" class="bbclms-trainer-msg-receiver-name" name="receiver_name" value="' . bp_core_get_user_displayname( $the_group_leader -> ID ) . '" />';
					
					$html .= wp_nonce_field( 'message_trainer', 'bbclms-trainer_learndash_message_trainer_nonce', true, false );
					$html .= '<input type="submit" class="bbclms-send-message-widget" value="' . __( 'Send Message to Trainer', 'bbclms' ) . '" />';
					$html .= '</p>';
					$html .= '<div class="fix"></div>';
					$html .= '</form>';
					$html .= '</div>';
				}  

				echo $html;			              
				?>
			</footer>
			<?php
			}
		}
	} // End load_component()
} // End Class
?>