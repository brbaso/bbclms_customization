<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Database operations
 */
 
class BbclmsDB {
	
	/***
	 * Initialize the class.
	 */
	public function __construct() {	
	
		global $wpdb;		
		$this -> db = $wpdb;		
		$this -> postmeta_table = $this->db->prefix . 'postmeta';
		$this -> posts_table =  $this->db->prefix . 'posts';	
		$this -> options_table =  $this->db->prefix . 'options';	
	}
	
	/***
	 * check if Group is already in system  - check against coms_id in 'bbclms_wplms_coms_ids' table
	 * returns object of coms_id/ lms_id pair if exists
	 */
	public function bbclms_check_group_exists_coms_id( $coms_id = 0 ){
		$result = '';
		if( !$coms_id ){
			return $result;
		}
		
		$sql_str = $this-> db -> prepare("SELECT * FROM ". $this -> db -> prefix."wplms_coms_ids WHERE coms_id = %d ", $coms_id );
		//get_var
		$result = $this-> db -> get_row( $sql_str );
		
		return $result;		
	}
	
	/***
	 * insert new pair of coms_id and lms_id  - in 'bbclms_wplms_coms_ids' table
	 */
	public function bbclms_insert_group_lms_coms_ids( $coms_id = 0, $lms_id = 0 ){
		$result = '';
		if( !$coms_id || !$lms_id ){
			return $result;
		}
		
		$sql_str = $this-> db -> prepare("INSERT INTO ".$this -> db -> prefix."wplms_coms_ids (coms_id,lms_id) VALUES (%d,%d)", $coms_id, $lms_id );
		$result = $this-> db -> query( $sql_str );
		
		return $result;			
	}
	
	/***
	 * delete pair of coms_id and lms_id  - in 'bbclms_wplms_coms_ids' table
	 */
	public function bbclms_delete_group_lms_coms_ids( $lms_id = 0 ){
		$result = '';
		if( !$lms_id ){
			return $result;		
		}	
		
		$sql_str = $this-> db -> prepare("DELETE FROM ".$this -> db -> prefix."wplms_coms_ids WHERE lms_id = %d ", $lms_id );
		$result = $this -> db -> query( $sql_str );
	
		return $result;		
	}
	
	/***
	 * get courses
	 */
	public function bbclms_get_all_courses( $args = array() ){
		
		$defaults = array(
			'post_type'			=>	'sfwd-courses',
			'fields'			=>	'ids',
			'nopaging'			=>	true,
			'post_status'      => 'publish'
			
		);

		$course_query_args = wp_parse_args( $args, $defaults );
		//error_log('course_query_args<pre>'. print_r( $course_query_args, true) .'</pre>');

		$course_query = new WP_Query( $course_query_args );
		
		if ( ( isset( $course_query -> posts ) ) && ( !empty( $course_query -> posts ) ) ) {
			$course_ids = $course_query -> posts;
		}
		
		return $course_ids;				
	}
	
	/***
	 * get bp groups
	 */
	public function bbclms_get_bp_groups( $status = 'public' ){
		
		$sql_str = $this -> db -> prepare("SELECT * FROM ". $this -> db-> prefix."bp_groups WHERE status = %s ", $status );		
		$bp_groups = $this -> db -> get_results( $sql_str );
		
		return $bp_groups;		
	}
	
	/**
	 * Get group courses
	 * 
	 */
	public function bbclms_get_all_group_courses( $group_id ){		
		
		$courses = array();
		
		if ( !empty( $group_id ) ) {				
			$course_ids = learndash_group_enrolled_courses( $group_id );				
		
			$count_course_ids = count($course_ids);
			$placeholders = array_fill(0, $count_course_ids, '%d');	
			$placeholders_implode = implode(',', $placeholders); // %d,%d			
			
			$query = "SELECT * FROM ". $this-> db ->posts ." WHERE ID IN(".$placeholders_implode.") AND post_status = 'publish'";				
			$courses = $this -> db -> get_results( $this -> db ->prepare( $query, $course_ids ) );
		}
		
		return $courses;
	}
	
	/**
	 * Get all group leaders groups
	 * 
	 */
	public function bbclms_get_all_group_leaders_groups( $user_id ) {
		
		$groups = array();
		
		if(empty($user_id)){
			$user = wp_get_current_user();
			$user_id = $user -> ID;
		}
		
		if ( !empty( $user_id ) ) {		
			
			$sql_str = $this -> db -> prepare( "SELECT usermeta.meta_value as group_ids FROM ". $this -> db -> usermeta ." as usermeta INNER JOIN ". $this -> db -> posts ." as posts ON posts.ID=usermeta.meta_value WHERE  user_id = %d  AND meta_key LIKE %s AND posts.post_status = 'publish'", $user_id, 'learndash_group_leaders_%');
			$group_ids = $this -> db -> get_col( $sql_str );

			$count_group_ids = count($group_ids);
			$placeholders = array_fill(0, $count_group_ids, '%d');	
			$placeholders_implode = implode(',', $placeholders); // %d,%d			
			
			$query = "SELECT * FROM ". $this -> db -> posts ." WHERE ID IN(".$placeholders_implode.") AND post_status = 'publish'";			
			$groups = $this -> db -> get_results( $this -> db -> prepare( $query, $group_ids ) );			
		
		}
		return $groups;	
	}
	
}
