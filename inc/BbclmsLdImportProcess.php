<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class BbclmsLdImportProcess extends WP_Background_Process {	

	/**
	 * @var string
	 */
	protected $action = 'ld_import_process';
	
	/**
	 * @var object 
	 */
	protected $dbhandle;
	protected $functions;
	
	public function __construct( BbclmsDB $dbhandle, BbclmsFunctions $functions ) {
		
		parent::__construct();
				
		$this -> dbhandle = $dbhandle;		
		$this -> functions = $functions;	
		
		add_action( 'wp_ajax_process_all_imports', array( $this, 'ajax_process_handler' ) );
		add_action( 'wp_ajax_nopriv_process_all_imports', array( $this, 'ajax_process_handler' ) );
		
		add_action( 'wp_ajax_check_process_all_imports', array( $this, 'ajax_check_process_handler' ) );
		add_action( 'wp_ajax_check_process_all_imports', array( $this, 'ajax_check_process_handler' ) );
		
		add_action ( 'mycronjob_csvimport',  array( $this, 'process_handler') );
		
		// clear hook CRON depending on csv files settings
		$csv_files_settings = get_option( 'csv_files_settings');			
					
		if ( !isset($csv_files_settings['csv_files_enable_cron']) ) {				
			wp_clear_scheduled_hook( 'mycronjob_csvimport' );		
		}		
	}
	
	/**
	 * Get settings
	 */
	public function get_csv_files_settings() {
		$settings = get_option( 'csv_files_settings' );
		return $settings;
	}
	
	/**
	 * Ajax Check Process handler
	 */
	public function ajax_check_process_handler() {
		
		$r = '';
		$completed = get_option( 'bbclms_csv_import_completion' );
		
		if( $completed == 0 ){
			$r = 'completed';
		} else{
			$r = 'not_completed';
		}
		
		echo $r ;
		wp_die();		
	}
	
	/**
	 * Ajax Process handler
	 */
	public function ajax_process_handler() {
		$r = '';
		$r .= '<div>';
		$r .= '<strong>Import process in progress</strong><br />';
		$r .= '<br />You are allowed to navigate from this page.';
		$r .= '<p><a href="#" id="check_process_all_imports" class="process_all_imports button button-primary" >Check if Process Completed</a>	</p>';
		$r .= '</div>';
		
		echo $r;
		update_option( 'bbclms_csv_import_completion', 1 );	
		$this -> handle_all();		
		wp_die();
	}
	
	/**
	 * Process handler
	 */
	public function process_handler() {

			$completed = get_option( 'bbclms_csv_import_completion' );
			
			if( $completed != 0 ){
				$this -> cancel_process();
				update_option( 'bbclms_csv_import_completion', 1 );
			}
					
			$csv_files_settings = $this -> get_csv_files_settings();			
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file1_name']);
			//sleep(2);
			$this -> handle_all();		
	}
	
	/**
	 * Handle all 
	 */
	protected function handle_all( ) {
		
		$csv_files_settings = $this -> get_csv_files_settings();		
		$bbclms_batch_file_to_import = get_option( 'bbclms_batch_file_to_import' );
		
		$path = $csv_files_settings['csv_files_folder_path'];
		
		$required_headers = [];
		$items = [];
		$file = '';
		
		// Write to log file
		$upload_dir = wp_upload_dir();			
		$time =  date('Y-m-d-H-i-s'); //time();
		$log_path = $upload_dir['basedir'].'/csv_user_imports_logs/';
		
		// here we have three files to process, one after another ...
		if ( $bbclms_batch_file_to_import === false || $bbclms_batch_file_to_import == $csv_files_settings['csv_file1_name'] || $bbclms_batch_file_to_import == '' ) {
			
			// call first file data to handle - csv_file1_name
			$file = $csv_files_settings['csv_file1_name'].'.csv';			
			
			// TODO - make required headers defined in settings page ...
			$required_headers = [				
				'group_coms_id',
				'group_title',				
				'courseid',				
			];				
			update_option( 'groups_import_log_file', $log_path.$time.'_GROUPS_IMPORT.txt' );			
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file1_name']);
			
		} elseif( $bbclms_batch_file_to_import == $csv_files_settings['csv_file2_name'] ){
			
			// call first file data to handle - csv_file2_name
			$file = $csv_files_settings['csv_file2_name'].'.csv';			
			
			// TODO - make required headers defined in settings page ... username	password	email	firstname	lastname	group_coms_id
			$required_headers = [
				'username',
				'password',				
				'email',
				'firstname',	
				'lastname',	
				'group_coms_id'
			]; 
//echo '<pre> '; print_r( $required_headers ); echo '</pre>'; die;	
			update_option( 'groups_import_log_file', $log_path.$time.'_TEACHERS_IMPORT.txt' );
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file2_name']);
			
		} elseif(  $bbclms_batch_file_to_import == $csv_files_settings['csv_file3_name'] ){
			
			// call first file data to handle - csv_file3_name
			$file = $csv_files_settings['csv_file3_name'].'.csv';			
			
			// TODO - make required headers defined in settings page ...
			$required_headers = [
				'username',
				'password',				
				'email',
				'firstname',	
				'lastname',				
				'courseid',
				'group_coms_id'
			];
					
			update_option( 'groups_import_log_file', $log_path.$time.'_STUDENTS_IMPORT.txt' );		
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file3_name']);
		}
		
		$items = $this -> get_csv_file_data( $file, $path, $required_headers );		

		if( !empty( $items ) ){
			foreach ( $items as $item ) {
				$this -> push_to_queue( $item );
			}
			$this -> save()-> dispatch();
		}		
	}
	
	/**
	 * Get csv file  data
	 *
	 * @return array
	 */
	protected function get_csv_file_data( $file = '', $path = '', $required_headers = array() ) {
		
		$items = [];		
		
		if( !$path ){
			// error path to csv files not defined
			add_action( 'admin_notices', array( $this, 'csv_upload_folder_not_exists' ) );
			return $items;
		}			

		$f = $this -> f_handle( $path, $file, $required_headers );			
		
		if( is_resource($f) ){	
			while ( ($data = fgetcsv($f , 0, ",")) !== FALSE ) {
				$items[] = $data;
			} // END WHILE
		fclose($f);	
		}
		
		return $items;
	}
	
	/**
	 * Process csv file 1 data GROUPS TO COURSES
	 *
	 * @return array
	 */
	protected function process_csv_file1_data( $data ) {
	
		$html = '';
		$time =  date('Y-m-d-H-i-s'); //time();
		$coms_id = $data[0];
		$group_title = ($data[1]) ? $data[1] : 'Untitled GROUP - COMS ID: '.$coms_id;
		$course_id = $data[2];
		
		// check data//check the headers of the file
		if ( !$coms_id || !$course_id) {		
			
			$html .= '<pre> ERROR - record skipped: <br />';
			$html .= '<span> Time: '.$time.' </span>';
				foreach($data as $d){
					$value = ($d) ? $d : '"missing value"' ;
					$html .= '<span> '.$value.' </span>';
				}		
			$this->log( 'ERROR - record skipped, missing value' );
			//continue;
			return $html;
		} else {
			
			// first check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
			$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );
			
			if( !$check_group_exists ){
				
				/* add new group and write new coms_id/lms_id to table  */			
				$post_arr = array(
					'post_title'   => $group_title,
					'post_content' => $group_title,
					'post_status'  => 'publish',							
					'post_type'    => 'groups',
					'comment_status' => 'closed',   
					'ping_status' => 'closed'					
				);
				
				$lms_id = wp_insert_post( $post_arr );
				$this -> dbhandle -> bbclms_insert_group_lms_coms_ids( $coms_id, $lms_id );
				
				// assign course to group
				learndash_set_group_enrolled_courses( $lms_id, array( $course_id ) );				
				$html .= 'Group LMS ID: '.$lms_id.' - CREATED, Time: ';
					
			} else{
				
				$lms_id = $check_group_exists -> lms_id;
				
				// update Groupe Title, Content
				$group_title_update = array(
					'ID'           => $lms_id,
					'post_title'   => $group_title,
					'post_content' => $group_title,
				);

				// Update the post into the database
				wp_update_post( $group_title_update );
				
				// if course ID updated - here we need to go through the system and Update Courses, Users too ...				
				$group_courses = learndash_group_enrolled_courses( $lms_id );
				$old_course_id = $group_courses[0];
				$new_course_id = $course_id;		

				if( $old_course_id != $new_course_id ){
					learndash_set_group_enrolled_courses( $lms_id,  array( $new_course_id ) );
				}		
				
				$html .= 'Group LMS ID: '.$lms_id.' - UPDATED, Time: ';						
			}
			
			if (count($data)>1) {					 
				
				$i=1;				
				$html .= $time.' || '.PHP_EOL ;
				foreach( $data as $dat ){		
					$new_line = '';
					$phpeof_line = '';								
					$html .= $dat.' || '.$phpeof_line;					
					$i++;
				}				
			}
			
		}
		return $html;
	}
	
	/**
	 * Process csv file 2 data TRAINER  - GROUPS
	 *
	 * @return array
	 */
	protected function process_csv_file2_data( $data ) {
	
		$html = '';
		$time =  date('Y-m-d-H-i-s'); //time();
		
		// get each user by username and see if exists 					
		$username = $data[0];
		$password = $data[1];
		$email_address =  $data[2];
		$firstname = ( $data[3] ) ? $data[3] : $username ;
		$lastname = ( $data[4] ) ? $data[4] : $username ;		
		$coms_id = $data[5];		
		
		if ( !$coms_id ){			
			$html .= 'ERROR - COMS ID missing! ';
			$html .= '<span> Time: '.$time.' </span>';
			$this->log( 'ERROR - COMS ID missing!' );
			//continue;
			return $html;
		}

		//  check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
		$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );				
		
		if ( !$check_group_exists ){			
			$html .= 'ERROR - There is no Group defined for COMS ID '.$coms_id.'!';
			$html .= '<span> Time: '.$time.' </span>';
			$this->log( 'ERROR - There is no Group defined for COMS ID' );
			//continue;
			return $html;
		}
		
		$lms_id = $check_group_exists -> lms_id;
		
		// check username, email, password existence and coms ID existance
		if ( !$username || !$email_address || !$password ) {		
			
			$html .= '<pre> ERROR - record skipped: <br />';
			$html .= '<span> Time: '.$time.' </span>';
				foreach($data as $d){
					$value = ($d) ? $d : '"missing value"' ;						
					echo '<span> '.$value.' </span>';
					$html .= '<span> '.$value.' </span>';
				}
			$this->log( 'ERROR - record skipped' );	
			//continue;
			return $html;			
		}
		
		// check if User exists
		if( !username_exists( $username ) ) {
			
			// new user, lets create it:					
			$user_id = wp_create_user ( $username, $password, $email_address );		
			
			$html .= 'TEACHER: '.$username.', ID: '.$user_id.' - CREATED, Time: ';
			
			// udate user with more data
			wp_update_user(
				array(
				'ID'       => $user_id,
				'nickname' => $email_address,
				'display_name' => $firstname.' '.$lastname,
				'first_name' => $firstname,
				'last_name' =>$lastname,
				'role' => 'group_leader'
				)
			);
			
			// bbp_keymaster
			$user = get_user_by( 'login', $username );
			$user -> add_role( 'bbp_keymaster' );					
			
			// BP set additional x-profile data for a user
			$name = $firstname.' '.$lastname;
			$bio = 'Please add a few lines of your Bio here';
			xprofile_set_field_data('Name', $user_id,  $name);
			xprofile_set_field_data('Bio', $user_id,  $bio);
			
			//BuddyPress general group					
			$public_groups = $this -> dbhandle -> bbclms_get_bp_groups();
			foreach( $public_groups as $pg ){
				groups_join_group( $pg -> id , $user_id );
			}
			
			$user_courses_new = array();
			
			//BuddyPress course group $lms_id
			// get ld groups course
			$ld_group_courses = $this -> functions -> bbclms_get_group_courses( $lms_id );
			foreach( $ld_group_courses as $lc ){						
				$bp_course_group = get_post_meta( $lc -> ID, 'bp_course_group', true );						
				if($bp_course_group){
					groups_join_group( $bp_course_group, $user_id );
				}
				$user_courses_new[] = $lc -> ID;
			}
			
			// enroll Group Leader to the GROUP using lms_id of the group
			learndash_set_administrators_group_ids( $user_id, array( $lms_id ) );
			
			// enroll Group Leader to Group courses:
			learndash_user_set_enrolled_courses( $user_id, $user_courses_new );
			
			// SEND MAIL TO USER:
			$subject = 'Your account activation';
			$to = $email_address;					
			$headers = 'From: BBC LMS DEVELOP SITE!! <elearning@bridgebc.edu.au>' . "\r\n\\";
			
			$data_to_send = array();
			
			$data_to_send = [
				'name' => $name,
				'username' => $username,
				'password' => $password,
				'email_address' => $email_address
			];
			
			$sent = $this -> functions -> bbclms_user_account_activation( $subject, $to, $headers, $data_to_send );
			
			if( $sent ) {
				$html .= 'Email sent to user, ';
			} else {
				$html .= '<pre> ERROR - mail function failed: <br />';
			}
			// END mail to user
			
		} else {
			
			$user = get_user_by( 'login', $username );
			
			// udate user with more data
			wp_update_user(
				array(
				'ID'       => $user -> ID,
				//'nickname' => $email_address,
				'display_name' => $firstname.' '.$lastname,
				'first_name' => $firstname,
				'last_name' =>$lastname,
				//'role' => 'group_leader'
				)
			);
			
			// bbp_keymaster
			$user -> add_role( 'bbp_keymaster' );	
			
			$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );			
			$old_lms_id = learndash_get_administrators_group_ids( $user -> ID );			
			$new_lms_id = $check_group_exists -> lms_id ;
			
			//BuddyPress general group				
			$public_groups = $this -> dbhandle -> bbclms_get_bp_groups();
			foreach( $public_groups as $pg ){
				groups_join_group( $pg -> id , $user -> ID );
			}				
			
			$user_courses_new = array();
			//BuddyPress course group $lms_id
			// get ld groups course
			$ld_group_courses = $this -> functions -> bbclms_get_group_courses( $new_lms_id );
			foreach( $ld_group_courses as $lc ){						
				$bp_course_group = get_post_meta( $lc -> ID, 'bp_course_group', true );
				if($bp_course_group){
					groups_join_group( $bp_course_group, $user -> ID );
				}	
				$user_courses_new[] = $lc -> ID;
			}
			
			if( $old_lms_id != $new_lms_id ){				
				$this -> functions -> bbclms_learndash_set_administrators_group_ids( $user -> ID, array( $new_lms_id ) );
			}
			
			// enroll Group Leader to Group courses:
			learndash_user_set_enrolled_courses( $user -> ID, $user_courses_new );
			
			$html .= 'Username: '.$username.' In Use, ID: '.$user -> ID.' - UPDATED, Time: ';			
		}
		
		if (count($data)>1) {			 
			
			$i=1;			
			$html .= $time.' || '.PHP_EOL ;
			foreach( $data as $dat ){		
				$new_line = '';
				$phpeof_line = '';								
				$html .= $dat.' || '.$phpeof_line;						
				$i++;	
			}
		}
		return $html;	
	}
	
	/**
	 * Process csv file 3 data STUDENT TO COURSES - GROUPS
	 *
	 * @return array
	 */
	protected function process_csv_file3_data( $data ) {
		
		$html = '';
		
		// get each user by username and see if exists 					
		$username = $data[0];
		$password = $data[1];
		$email_address =  $data[2];
		$firstname = $data[3];
		$lastname = $data[4];
		$course_id =  $data[5];
		$coms_id =  $data[6];

		
		if ( !$coms_id ){			
			$html .= 'ERROR - COMS ID missing! ';
			$html .= '<span> Time: '.$time.' </span>';			
			$this->log( 'ERROR - COMS ID missing' );
			//continue;
			return $html;
		}
		
		if ( !$course_id) {				
			$html .= 'ERROR - Course ID missing! ';
			$html .= '<span> Time: '.$time.' </span>';
			$this->log( 'ERROR - Course ID missing' );
			//continue;
			return $html;
		}
		
		// check if course exists in the system and it is published					
		$courses = $this -> dbhandle -> bbclms_get_all_courses();

		if( !in_array( $course_id, $courses ) ){			
			$html .= 'ERROR - Course not defiend ID:'.$course_id.'! ';
			$html .= '<span> Time: '.$time.' </span>';			
			$this->log( 'ERROR - Course not defiend ID' );
			//continue;	
			return $html;
		}
		
		//  check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
		$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );				
		
		if ( !$check_group_exists ){			
			$html .= 'ERROR - There is no Group defined for COMS ID '.$coms_id.'!';
			$html .= '<span> Time: '.$time.' </span>';			
			$this->log( 'ERROR - There is no Group defined for COMS ID' );
			//continue;
			return $html;
		}
		
		$lms_id = $check_group_exists -> lms_id;
		
		// check username, email, password existence and coms ID existance
		if ( !$username || !$email_address || !$password ) {			
			$html .= '<pre> ERROR - record skipped: <br />';
			$html .= '<span> Time: '.$time.' </span>';
				foreach($data as $d){
					$value = ($d) ? $d : '"missing value"' ;						
					echo '<span> '.$value.' </span>';
					$html .= '<span> '.$value.' </span>';
				}			
			$this->log( 'ERROR - record skipped' );			
			//continue;
			return $html;
		}
		
		if ( !username_exists( $username ) ) {
			
			// new user, lets create it:			
			$user_id = wp_create_user ( $username, $password, $email_address );			
			$html .= 'USER: '.$username.', ID: '.$user_id.' - CREATED, Time: ';
			
			// udate user with more data
			wp_update_user(
				array(
				'ID'       => $user_id,
				'nickname' => $email_address,
				'display_name' => $firstname.' '.$lastname,
				'first_name' => $firstname,
				'last_name' =>$lastname,
				'role' => 'subscriber'
				)
			);
			
			// BP set additional x-profile data for a user
			$name = $firstname.' '.$lastname;
			$bio = 'Please add a few lines of your Bio here';
			xprofile_set_field_data('Name', $user_id,  $name);
			xprofile_set_field_data('Bio', $user_id,  $bio);
			
			// try to enroll the user here			
			$meta = $this -> functions -> bbclms_ld_update_course_access( $user_id, $course_id );			

			// let's assign user to a LD group 			
			$this -> functions -> bbclms_learndash_set_users_group_ids( $user_id, array($lms_id) );			
			
			//BuddyPress			
			// find if the Course is assigned to BP Group
			$bp_course_group = get_post_meta( $course_id, 'bp_course_group', true );			
			if($bp_course_group){
				//groups_join_group( $bp_course_group, $user_id );				
			} else{				
			}
			
			//BuddyPress general group 						
			$public_groups = $this -> dbhandle -> bbclms_get_bp_groups();
			foreach( $public_groups as $pg ){
				groups_join_group( $pg -> id , $user_id );
			}

			// SEND MAIL TO USER:
			$subject = 'Your account activation';
			$to = $email_address;					
			$headers = 'From: BBC LMS DEVELOP SITE!! <elearning@bridgebc.edu.au>' . "\r\n\\";
			
			$data_to_send = array();
			
			$data_to_send = [
				'name' => $name,
				'username' => $username,
				'password' => $password,
				'email_address' => $email_address
			];
			
			$sent = $this -> functions -> bbclms_user_account_activation( $subject, $to, $headers, $data_to_send );
			
			if( $sent ) {				
				$html .= 'Email sent to user, ';
			} else {				
				$html .= '<pre> ERROR - mail function failed: <br />';
			}
			// END mail to user
		} else {
			
			$user = get_user_by( 'login', $username );	
			
			$html .= 'Username: '.$username.' In Use, ID: '.$user -> ID.' - UPDATED, Time: ';									
			$user_id = $user -> ID;
			
			// get each user by username and see if exists 					
			$username = $data[0];
			$password = $data[1];
			$email_address =  $data[2];
			$firstname = $data[3];
			$lastname = $data[4];
			$course_id =  $data[5];
			$group_id =  $data[6];
			
			// udate user with more data
			wp_update_user(
				array(
				'ID'       => $user_id,
				//'nickname' => $email_address,
				'display_name' => $firstname.' '.$lastname,
				'first_name' => $firstname,
				'last_name' =>$lastname,
				//'role' => 'subscriber'
				)
			);
			
			// BP set additional x-profile data for a user
			$name = $firstname.' '.$lastname;
			$bio = 'Please add a few lines of your Bio here';
			xprofile_set_field_data('Name', $user -> ID,  $name);
			xprofile_set_field_data('Bio', $user -> ID,  $bio);
			
			// enroll the user here			
			$meta = $this -> functions -> bbclms_ld_update_course_access( $user -> ID, $course_id );			
			$this -> functions -> bbclms_learndash_set_users_group_ids( $user -> ID, array( $lms_id ) );			
			
			//BuddyPress						
			// find if the Course is assigned to BP Group
			$bp_course_group = get_post_meta( $course_id, 'bp_course_group', true );			
			if($bp_course_group){
				//groups_join_group( $bp_course_group, $user -> ID );				
			} else{
				
			}
			
			//BuddyPress general group						
			$public_groups = $this -> dbhandle -> bbclms_get_bp_groups();
			foreach( $public_groups as $pg ){
				groups_join_group( $pg -> id , $user_id );
			}
		}
		
		$time =  date('Y-m-d-H-i-s'); 		
		if (count($data)>1) {		 
			
			$i=1;			
			$html .= $time.' || '.PHP_EOL ;
			foreach( $data as $dat ){		
				$new_line = '';
				$phpeof_line = '';								
				$html .= $dat.' || '.$phpeof_line;				
				$i++;
			}
		}		
		return $html;
	}
	
	
	/**
	 * Task
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		
		$bbclms_batch_file_to_import = get_option( 'bbclms_batch_file_to_import' );
		$csv_files_settings = $csv_files_settings = $this -> get_csv_files_settings();	 
		
		global $wpdb;
		
		$bbclmsdfg = ($bbclms_batch_file_to_import) ? $bbclms_batch_file_to_import : 'nothing' ;
		$wpdb->insert( 
			'atest_batch', 
			array( 
				'time_created' => $bbclmsdfg, 
				'item_added' => serialize( $item )
			), 
			array( 
				'%s', 
				'%s' 
			) 
		);
			
		if ( $bbclms_batch_file_to_import === false || $bbclms_batch_file_to_import == $csv_files_settings['csv_file1_name'] || $bbclms_batch_file_to_import == '' ){	
		
			$html = $this -> process_csv_file1_data( $item );			
			
		} elseif( $bbclms_batch_file_to_import == $csv_files_settings['csv_file2_name'] ){
			
			$html = $this -> process_csv_file2_data( $item );			
			
		} elseif(  $bbclms_batch_file_to_import == $csv_files_settings['csv_file3_name'] ){
			
			$html = $this -> process_csv_file3_data( $item );			
			
		}
		$this->log( ' Task function says: '.$html );
		
		$log_file = get_option( 'groups_import_log_file ');		
		$log = file_put_contents($log_file, $html.PHP_EOL , FILE_APPEND | LOCK_EX);
		
		return false;
	}	
	
	/**
	 * Complete processes and trigger next file import
	 *	 
	 */	
	protected function complete() {
		
		parent::complete();
		sleep(1);
		
		$bbclms_batch_file_to_import = get_option( 'bbclms_batch_file_to_import' );
		$csv_files_settings = $csv_files_settings = $this -> get_csv_files_settings();
		
		if ( $bbclms_batch_file_to_import === false || $bbclms_batch_file_to_import == $csv_files_settings['csv_file1_name'] || $bbclms_batch_file_to_import == '' ){
			
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file2_name']);
			sleep(2);
			$this -> handle_all();			
			
		} elseif( $bbclms_batch_file_to_import == $csv_files_settings['csv_file2_name'] ){
			
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file3_name']);
			sleep(2);
			$this -> handle_all();			
			
		} elseif(  $bbclms_batch_file_to_import == $csv_files_settings['csv_file3_name'] ){
			
			update_option( 'bbclms_batch_file_to_import', $csv_files_settings['csv_file1_name']);		
			update_option( 'bbclms_csv_import_completion', 0 );	
			
		} 		
	}
	
	/**
	 * Open and check csv files headers
	 *
	 * @return array
	 */
	protected function f_handle( $path, $file, $required_headers ) {
		
		$items = [];
		$upload_dir = wp_upload_dir();
		
		$f = fopen( $upload_dir['basedir'].'/'.$path.'/'.$file, 'r');
		
		if ( $f === FALSE) {
			// error file not exists
			add_action( 'admin_notices', array( $this, 'file_not_exists' ) );
			return $items;	
		}
		
		$first_line = fgets( $f ); //get first line of the CSV file
		$file_header = str_getcsv( trim($first_line), ',', "'" ); //parse the contents to an array		
		
		//check the headers of the file
		if ( $file_header !== $required_headers ) {		
						
			$message = '<pre> ERROR - HEADERS ARE NOT PROPERLY DEFINED, THEY SHOULD BE: ';
			foreach($required_headers as $rh){
				$message .= $rh.', ';
			}
			
			$this->log( $message );
			add_action( 'admin_notices', array( $this, 'file_headers_wrong' ) );
			return  $items;
		}
			
		return $f;
	}
			
	/**
	 * Log
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		error_log( $message );
	}
	
	/**
	 * Admin notice 
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function file_headers_wrong() {
		echo '<div class="error">';
		echo '<p><strong>ERROR </strong> - HEADERS ARE NOT PROPERLY DEFINED</p>';
		echo '</div>';
	}
	/**
	 * Admin notice 
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function csv_upload_folder_not_exists() {
		echo '<div class="error">';
		echo '<p><strong>CSV Upload Folder </strong> does not exist</p>';
		echo '</div>';
	}

	/**
	 * Admin notice 
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function file_not_exists() {
		echo '<div class="error">';
		echo '<p><strong>CSV File </strong> does not exist</p>';
		echo '</div>';
	}

}
