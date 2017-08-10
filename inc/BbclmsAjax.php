<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * The ajax response
 */

class BbclmsAjax {	
	
	private $dbhandle;
	private $functions;

	/**
	 * Initialize the class.	 
	 */
	public function __construct( BbclmsDB $dbhandle ) {
		$this -> dbhandle = $dbhandle;
		require_once BBCLMS_CUSTOMIZATION_PLUGIN_DIR . 'inc/BbclmsFunctions.php';
		$this -> functions = BbclmsFunctions::instance();			
	}
	
		
	/**
	 * Ajax handling for contact a Trainer(Group Leader) button
	 */
	public function bbclms_contact_trainer_ajax() {
		$msg_content = $_POST['content'];
		if ( empty( $msg_content ) ) {
			echo 'Failed';
			die();
		}
		$sender_id = $_POST['sender_id'];
		$reciever_id = $_POST['reciever_id'];
		$course_id = $_POST['course_id'];
		$subject = 'Regarding' .get_the_title($course_id);

		$args = array( 'recipients' => array($reciever_id), 'sender_id' => $sender_id, 'subject' => $subject, 'content' => $msg_content );		
		
		$msg_id = messages_new_message( $args );

		echo $msg_id;
		wp_die();
	}
	
	//*** NOT ACTIVE BUT COULD BE HANDY IN CASE WE WANT TO UPLOAD MANUALLY- ALSO ENABLE BUTTONS IN \wp-content\plugins\bbclms_customization\partials\BbclmsAdminView.php
	/**
	 * Ajax handling import Groups to Courses from CSV
	 */
	/*
	public function bbclms_groups_to_courses_csv($csv_file = '', $path = '') {
		
		// group_coms_id	group_title	courseid		
		$required_headers = [				
			'group_coms_id',
			'group_title',				
			'courseid',				
		];
		
		$settings = get_option( 'csv_files_settings' );
			
		$csv_file = $settings['csv_file1_name'].'.csv';
		
		$upload_dir = wp_upload_dir();

		$f = fopen( $upload_dir['basedir'].'/csv_user_imports/'.$csv_file, 'r');
		$first_line = fgets( $f ); //get first line of the CSV file
		$file_header = str_getcsv(trim($first_line), ',', "'"); //parse the contents to an array
		
		$html = '';

		//check the headers of the file
		if ( $file_header !== $required_headers) {		
			echo '<pre> ERROR - HEADERS ARE NOT PROPERLY DEFINED, THEY SHOULD BE: <br />'; 
				foreach($required_headers as $rh){
					echo '<span> '.$rh.' </span>';
				}
			echo '</pre>';			
			die;
		}
// echo '<pre> '; print_r( $settings); echo '</pre>';		

		if ( $f !== FALSE) {
			
			$html = '';
			
			while ( ($data = fgetcsv($f , 0, ",")) !== FALSE ) {

			//$lms_id
				$time =  date('Y-m-d-H-i-s'); //time();
				$coms_id = $data[0];
				$group_title = ($data[1]) ? $data[1] : 'Untitled GROUP - COMS ID: '.$coms_id;
				$course_id = $data[2];
				
				// check data//check the headers of the file
				if ( !$coms_id || !$course_id) {		
					echo '<pre> ERROR - record skipped: <br />';
					echo '<span> Time: '.$time.' </span>';
					$html .= '<pre> ERROR - record skipped: <br />';
					$html .= '<span> Time: '.$time.' </span>';
						foreach($data as $d){
							$value = ($d) ? $d : '"missing value"' ;						
							echo '<span> '.$value.' </span>';
							$html .= '<span> '.$value.' </span>';
						}
					echo '</pre>';					
					
					continue;
				} else {
					
					// first check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
					$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );
					
					if( !$check_group_exists ){
											
						
						$post_arr = array(
							'post_title'   => $group_title,
							'post_content' => $group_title,
							'post_status'  => 'publish',							
							'post_type'    => 'groups',
							'comment_status' => 'closed',   
							'ping_status' => 'closed',							
						);
						
						$lms_id = wp_insert_post( $post_arr );
						
						$this -> dbhandle -> bbclms_insert_group_lms_coms_ids( $coms_id, $lms_id );
						
						// assign course to group
						learndash_set_group_enrolled_courses( $lms_id, array( $course_id ) );
						
						echo 'Group LMS ID: '.$lms_id.' - CREATED, ';
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
						
						// update existing Group ID - possble to update Title , Course ID 
						echo 'Group LMS ID: '.$lms_id.' - UPDATED, ';
						$html .= 'Group LMS ID: '.$lms_id.' - UPDATED, Time: ';						
					}
					
					if (count($data)>1) {					  
							 
						$row_count = count($file_header);
						$i=1;
						echo '<span>Time : '.$time.'</span><br />';
						$html .= $time.' || '.PHP_EOL ;
						foreach( $data as $dat ){		
							$new_line = '';
							$phpeof_line = '';
							if( $i == $row_count){ $new_line = '<br />';  $phpeof_line = PHP_EOL; $i = 0; }
							echo '<span>'.$dat.' || </span>'.$new_line;
							$html .= $dat.' || '.$phpeof_line;
							
							$i++;		
						}
					}
				}
			} // END WHILE
			fclose($f);			
		}
		// Write to log file			
			$time =  date('Y-m-d-H-i-s'); //time();
			$log_path = $upload_dir['basedir'].'/csv_user_imports_logs/';
			
			$log_file = $log_path.$time.'_GROUPS_IMPORT.txt';		
			
			$log = file_put_contents($log_file, $html.PHP_EOL , FILE_APPEND | LOCK_EX);
	}
	*/
	
	//*** NOT ACTIVE BUT COULD BE HANDY IN CASE WE WANT TO UPLOAD MANUALLY- ALSO ENABLE BUTTONS IN \wp-content\plugins\bbclms_customization\partials\BbclmsAdminView.php
	/**
	 * Ajax handling import Trainers from CSV
	 */
	/*
	public function bbclms_trainers_to_groups_csv($csv_file = '', $path = '') {
			//username	password	email	firstname	lastname	group_coms_id 
			$required_headers = [
				'username',
				'password',				
				'email',
				'firstname',	
				'lastname',	
				'group_coms_id'
			]; 			
			
			$settings = get_option( 'csv_files_settings' );
			
			$csv_file = $settings['csv_file2_name'].'.csv';
			
			$upload_dir = wp_upload_dir();

			$f = fopen( $upload_dir['basedir'].'/csv_user_imports/'.$csv_file, 'r');
			$first_line = fgets( $f ); //get first line of the CSV file
			$file_header = str_getcsv(trim($first_line), ',', "'"); //parse the contents to an array
			
			$html = '';


			//check the headers of the file
			if ( $file_header !== $required_headers) {
				echo '<pre> ERROR - HEADERS ARE NOT PROPERLY DEFINED, THEY SHOULD BE: <br />'; 
				foreach($required_headers as $rh){
					echo '<span> '.$rh.' </span>';
				}
				echo '</pre>';			
				die;				
			}
			
			if ( $f !== FALSE) {			
			
			while ( ($data = fgetcsv($f , 0, ",")) !== FALSE ) {

				//$lms_id
				$time =  date('Y-m-d-H-i-s'); //time();
				// get each user by username and see if exists 					
				$username = $data[0];
				$password = $data[1];
				$email_address =  $data[2];
				$firstname = ( $data[3] ) ? $data[3] : $username ;
				$lastname = ( $data[4] ) ? $data[4] : $username ;		
				$coms_id = $data[5];
				
				
				if ( !$coms_id ){
					echo '<pre> ERROR - COMS ID missing! ';
					echo '<span> Time: '.$time.' </span>';
					$html .= 'ERROR - COMS ID missing! ';
					$html .= '<span> Time: '.$time.' </span>';
					echo '</pre>';
					continue;
				}
				
				//  check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
				$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );				
				
				if ( !$check_group_exists ){
					echo '<pre> ERROR - Ther is no Group defined for COMS ID: '.$coms_id.'! ';
					echo '<span> Time: '.$time.' </span>';
					$html .= 'ERROR - Ther is no Group defined for COMS ID '.$coms_id.'!';
					$html .= '<span> Time: '.$time.' </span>';
					echo '</pre>';
					continue;
				}
				
				$lms_id = $check_group_exists -> lms_id;
				
				// check username, email, password existence and coms ID existance
				if ( !$username || !$email_address || !$password ) {		
					echo '<pre> ERROR - record skipped: <br />';
					echo '<span> Time: '.$time.' </span>';
					$html .= '<pre> ERROR - record skipped: <br />';
					$html .= '<span> Time: '.$time.' </span>';
						foreach($data as $d){
							$value = ($d) ? $d : '"missing value"' ;						
							echo '<span> '.$value.' </span>';
							$html .= '<span> '.$value.' </span>';
						}
					echo '</pre>';			
					continue;					
				}
				
				// check if User exists
				if( !username_exists( $username ) ) {
					
					// new user, lets create it:					
					$user_id = wp_create_user ( $username, $password, $email_address );
					
					echo 'TEACHER: '.$username.', ID: '.$user_id.' - CREATED, ';
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
					
					//BuddyPress course group $lms_id
					// get ld groups course
					$ld_group_courses = $this -> functions -> bbclms_get_group_courses( $lms_id );
					foreach( $ld_group_courses as $lc ){						
						$bp_course_group = get_post_meta( $lc -> ID, 'bp_course_group', true );						
						if($bp_course_group){
							groups_join_group( $bp_course_group, $user_id );
						}						
					}
					
					// enroll Group Leader to the GROUP using lms_id of the group
					learndash_set_administrators_group_ids( $user_id, array( $lms_id ) );
					
					// enroll Group Leader to Group courses:
					//learndash_user_set_enrolled_courses( $user_id, $user_courses_new );
					
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
						echo 'Email sent to user, ';
						$html .= 'Email sent to user, ';
					} else {
						echo '<pre> ERROR - mail function failed: <br />';
						$html .= '<pre> ERROR - mail function failed: <br />';
					}					
					// END mail to user
					
				} else{
					
					$user = get_user_by( 'login', $username );
					
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
					
					//BuddyPress course group $lms_id
					// get ld groups course
					$ld_group_courses = $this -> functions -> bbclms_get_group_courses( $new_lms_id );

					foreach( $ld_group_courses as $lc ){						
						$bp_course_group = get_post_meta( $lc -> ID, 'bp_course_group', true );
					
						if($bp_course_group){
							groups_join_group( $bp_course_group, $user -> ID );
						}
	
					if( $old_lms_id != $new_lms_id ){						
						$this -> functions -> bbclms_learndash_set_administrators_group_ids( $user -> ID, array( $new_lms_id ) );
					}					
					
					echo 'Username '.$username.' In Use, ID: '.$user -> ID.' - UPDATED, ';
					$html .= 'Username: '.$username.' In Use, ID: '.$user -> ID.' - UPDATED, Time: ';					
				}
				
				if (count($data)>1) {					  
					 
					$row_count = count($file_header);
					$i=1;
					echo '<span>Time : '.$time.'</span><br />';
					$html .= $time.' || '.PHP_EOL ;
					foreach( $data as $dat ){		
						$new_line = '';
						$phpeof_line = '';
						if( $i == $row_count){ $new_line = '<br />';  $phpeof_line = PHP_EOL; $i = 0; }
						echo '<span>'.$dat.' || </span>'.$new_line;
						$html .= $dat.' || '.$phpeof_line;						
						$i++;	
					}
				}
			} // END WHILE
				fclose($f);
			}
			
			// Write to log file			
			$time =  date('Y-m-d-H-i-s'); //time();
			$log_path = $upload_dir['basedir'].'/csv_user_imports_logs/';
			
			$log_file = $log_path.$time.'_TEACHERS_IMPORT.txt';		
			
			$log = file_put_contents($log_file, $html.PHP_EOL , FILE_APPEND | LOCK_EX);
			die;	
	}
	*/	
	
	//*** NOT ACTIVE BUT COULD BE HANDY IN CASE WE WANT TO UPLOAD MANUALLY- ALSO ENABLE BUTTONS IN \wp-content\plugins\bbclms_customization\partials\BbclmsAdminView.php
	/**
	 * Ajax handling import Students from CSV
	 */
	/*
	public function bbclms_students_to_courses_groups_csv($csv_file = '', $path = '') {
			//$required_headers = array(username	password	email	firstname	lastname	courseid	group_coms_id ); 
			$required_headers = [
				'username',
				'password',				
				'email',
				'firstname',	
				'lastname',				
				'courseid',
				'group_coms_id'
			];			
			
			$settings = get_option( 'csv_files_settings' );
			
			$csv_file = $settings['csv_file3_name'].'.csv';
			
			$upload_dir = wp_upload_dir();

			$f = fopen( $upload_dir['basedir'].'/csv_user_imports/'.$csv_file, 'r');
			$first_line = fgets( $f ); //get first line of the CSV file
			$file_header = str_getcsv(trim($first_line), ',', "'"); //parse the contents to an array
			
			$html = '';

			//check the headers of the file
			if ( $file_header !== $required_headers) {
				echo '<pre> ERROR - HEADERS ARE NOT PROPERLY DEFINED, THEY SHOULD BE: <br />'; 
				foreach($required_headers as $rh){
					echo '<span> '.$rh.' </span>';
				}
				echo '</pre>';			
				die;				
			}
					

			if ( $f !== FALSE) {				
				while ( ($data = fgetcsv($f , 0, ",")) !== FALSE ) {
					
					// get each user by username and see if exists 					
					$username = $data[0];
					$password = $data[1];
					$email_address =  $data[2];
					$firstname = $data[3];
					$lastname = $data[4];
					$course_id =  $data[5];
					$coms_id =  $data[6];
					
					if ( !$coms_id ){
						echo '<pre> ERROR - COMS ID missing! ';
						echo '<span> Time: '.$time.' </span>';
						$html .= 'ERROR - COMS ID missing! ';
						$html .= '<span> Time: '.$time.' </span>';
						echo '</pre>';
						continue;
					}
					
					if ( !$course_id) {		
						echo '<pre> ERROR - Course ID missing! ';
						echo '<span> Time: '.$time.' </span>';
						$html .= 'ERROR - Course ID missing! ';
						$html .= '<span> Time: '.$time.' </span>';
						echo '</pre>';
						continue;
					}
					
					// check if course exists in the system and it is published					
					$courses = $this -> dbhandle -> bbclms_get_all_courses();

					if( !in_array( $course_id, $courses ) ){
						echo '<pre> ERROR - Course not defiend ID:'.$course_id.'! ';
						echo '<span> Time: '.$time.' </span>';
						$html .= 'ERROR - Course not defiend ID:'.$course_id.'! ';
						$html .= '<span> Time: '.$time.' </span>';
						echo '</pre>';
						continue;						
					}
					
					//  check if group is already in system - check against coms_id in 'bbclms_wplms_coms_ids' table					
					$check_group_exists = $this -> dbhandle -> bbclms_check_group_exists_coms_id( $coms_id );				
					
					if ( !$check_group_exists ){
						echo '<pre> ERROR - There is no Group defined for COMS ID: '.$coms_id.'! ';
						echo '<span> Time: '.$time.' </span>';
						$html .= 'ERROR - There is no Group defined for COMS ID '.$coms_id.'!';
						$html .= '<span> Time: '.$time.' </span>';
						echo '</pre>';
						continue;
					}
					
					$lms_id = $check_group_exists -> lms_id;
					
					// check username, email, password existence and coms ID existance
					if ( !$username || !$email_address || !$password ) {		
						echo '<pre> ERROR - record skipped: <br />';
						echo '<span> Time: '.$time.' </span>';
						$html .= '<pre> ERROR - record skipped: <br />';
						$html .= '<span> Time: '.$time.' </span>';
							foreach($data as $d){
								$value = ($d) ? $d : '"missing value"' ;						
								echo '<span> '.$value.' </span>';
								$html .= '<span> '.$value.' </span>';
							}
						echo '</pre>';			
						continue;					
					}
					
					
					if ( !username_exists( $username ) ) {
						// new user, lets create it:
						//create,slobodan,testbrbaso@gmail.com,0,slobodan,Slobodan,Brbaklic,Student
						$user_id = wp_create_user ( $username, $password, $email_address );
						
						echo 'USER: '.$username.', ID: '.$user_id.' - CREATED, ';
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
						
						//enroll the user here						
						$meta = $this -> functions -> bbclms_ld_update_course_access( $user_id, $course_id );						
	
						// let's assign user to a LD group 
						//$course_groups = $this -> functions -> bbclms_get_group_leaders_groups( 18 );
						$this -> functions -> bbclms_learndash_set_users_group_ids( $user_id, array($lms_id) );
						
						
						//BuddyPress						
						// find if the Course is assigned to BP Group
						$bp_course_group = get_post_meta( $course_id, 'bp_course_group', true );
						
						if($bp_course_group){
							
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
						echo 'Email sent to user, ';
						$html .= 'Email sent to user, ';
					} else {
						echo '<pre> ERROR - mail function failed: <br />';
						$html .= '<pre> ERROR - mail function failed: <br />';
					}
					// END mail to user
					
					} else {
						
						$user = get_user_by( 'login', $username );
						//$user = get_user_by( 'email', $email_address );
						
						echo 'Username '.$username.' In Use, ID: '.$user -> ID.' - UPDATED, ';
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
						} else{
							
						}
						
						//BuddyPress general group						
						$public_groups = $this -> dbhandle -> bbclms_get_bp_groups();
						foreach( $public_groups as $pg ){
							groups_join_group( $pg -> id , $user_id );
						}	
					}
					
					$time =  date('Y-m-d-H-i-s'); //time();
					
					if (count($data)>1) {					  
					 
						$row_count = count($file_header);
						$i=1;
						echo '<span>Time : '.$time.'</span><br />';
						$html .= $time.' || '.PHP_EOL ;
						foreach( $data as $dat ){		
							$new_line = '';
							$phpeof_line = '';
							if( $i == $row_count){ $new_line = '<br />';  $phpeof_line = PHP_EOL; $i = 0; }
							echo '<span>'.$dat.' || </span>'.$new_line;
							$html .= $dat.' || '.$phpeof_line;
							
							$i++;		
						}
					}
	
				  //}
				} // END WHILE
				
				fclose($f);
			}			
			// Write to log file			
			$time =  date('Y-m-d-H-i-s'); //time();
			$log_path = $upload_dir['basedir'].'/csv_user_imports_logs/';
			
			$log_file = $log_path.$time.'_STUDENTS_IMPORT.txt';		
			
			$log = file_put_contents($log_file, $html.PHP_EOL , FILE_APPEND | LOCK_EX);
			die;
		}
		*/
	
	/**
	 * Ajax show Import Logs
	 */
	public function bbclms_list_import_user_logs() {
		$upload_dir = wp_upload_dir();
				
		$directory = $upload_dir['basedir'].'/csv_user_imports_logs/';
		$phpfiles = glob($directory . "*.txt");
		
		if( $phpfiles ){
		foreach($phpfiles as $phpfile){
			echo '<a href='.$upload_dir['baseurl'].'/csv_user_imports_logs/'.basename($phpfile).' target="_blank">'.basename($phpfile).'</a><br />';
		}
		} else{
			echo 'No files to show';
		}
		wp_die();
	}
	
	/**
	 * Delete ALL Import Logs
	 */
	public function bbclms_delete_log_files() {
		$upload_dir = wp_upload_dir();
				
		$directory = $upload_dir['basedir'].'/csv_user_imports_logs/';
		$phpfiles = glob($directory . "*.txt");	
		$counter = 0;	
		foreach($phpfiles as $phpfile){
			if(is_file($phpfile)){						
				unlink( $phpfile ); // delete file			
				$counter ++;
			}
		}
		echo $counter.' files removed from '.$upload_dir['baseurl'];
		wp_die();
	}
}
