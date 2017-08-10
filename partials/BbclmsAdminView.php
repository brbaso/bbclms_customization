<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * The admin page view
 */

class BbclmsAdminView {
	
	
	private $dbhandle;
	private $functions;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 * @param      object    $dbhandle      Object.
	 * @param      object    $functions      Object.
	 */
	public function __construct( BbclmsDB $dbhandle, $functions ) {
		$this -> dbhandle  =  $dbhandle;
		$this -> functions = $functions;
	}	
	
	/**
	 * Render the page.	 
	 */
	public function display(){		
		
		$courses = $this -> functions -> bbclms_ld_items_list( array('post_type' => 'groups') ); // sfwd-lessons , sfwd-topic, sfwd-courses
		$_items  = $courses;
		$settings =  get_option( 'csv_files_settings' );				
		global $submenu;
		$completed = get_option( 'bbclms_csv_import_completion' );
		?>
		<div class="wrap">
			<div class="wrap-left" style="width:49%; float:left;">
			<h2>Import Users/Cron-Job Options</h2>
			<form action="options.php" method="POST">
				<?php settings_fields( 'csv_files_settings' ); ?>
				<?php do_settings_sections( 'import-users-cron-page' ); ?>
				<?php submit_button(); ?>
			</form>
			</div>
			<div class="wrap-right" style="">
				<!-- ENABLE THIS FOR MANUAL IMPORT - ALSO ENABLE FUNCTIONS IN \wp-content\plugins\bbclms_customization\inc\BbclmsAjax.php
				<a id="manual_import_groups" class="manual_import button button-primary">Manual Import GROUPS</a>
				<a id="" class="manual_import button button-primary manual_import_trainers" >Manual Import TRAINERS</a>
				<a id="" class="manual_import button button-primary manual_import_students" >Manual Import STUDENTS</a>
				-->
			
				<?php					
					//echo $completed ;
					if( $completed == 0 ){
				?>
				<div id="process_all" class="process_all"> 
					<a href="#" id="process_all_imports" class="process_all_imports button button-primary cansee" >Process all Imports</a>				
				</div>
				<?php
				}
				?>
				<div id="manual_import_response" class="manual_import_response">
				<?php					
					//echo $completed ;
					if( $completed == 1 ){
						$r = '<div>';
						$r .= '<strong>Import process in progress</strong>';
						$r .= '<br />You are allowed to navigate from this page.';
						$r .= '<p><a href="#" id="check_process_all_imports" class="process_all_imports button button-primary" >Check if Process Completed</a>	</p>';
						$r .= '</div>';
						echo $r ;
					}
				?>
				</div>				
			</div>	
			<div id="log_files" class="log_files"> 
				<h2>Log files</h2>
				<?php
				
				?>
				<a id="list_log_files" class="list_log_files button button-primary">Show Import Logs</a>
				<a id="delete_log_files" class="delete_log_files button button-primary">Delete Log Files</a><span>!! Warning - this will DELETE  ALL log files from SERVER. CANNOT BE UNDONE !!</span>
				
				<div id="log_files_response" class="log_files_response"> </div>
			</div>
			<style>
			.delete_log_files {
				margin-left: 100px !important;
				margin-right: 10px !important;
			}
			.manual_import, .manual_import_trainers, .manual_import_students {
				opacity: 0.3;
				display: none !important;
			}
			
			.log_files_response{
				margin-top: 30px;
				margin-bottom: 30px;
				/*overflow: auto;*/
				background-color: #fff;
				max-height: 300px;
				min-height: 80px;
			}
			.log_files {
				padding: 50px 0 0 20px;
				overflow: scroll;
				background-color: #fff;
				max-height: 250px;
				min-height: 100px;
			}
			.wrap-right{
				width: 49%;
				float: left;
				padding: 50px 0 0 20px;
				overflow: auto;
				background-color: #fff;
				max-height: 300px;
				min-height: 80px;
			}
			.delete_log_files.button-primary {
				background: #febf0d;
				border-color: #febf0d;
				-webkit-box-shadow: none;
				box-shadow: none;
				color: #fff;
				text-decoration: none;
				text-shadow: none;
				margin-bottom: 20px;
			}
			.manual_import.button-primary {
				background: #479608;
				border-color: #479608;
				-webkit-box-shadow: none;
				box-shadow: none;
				color: #fff;
				text-decoration: none;
				text-shadow: none;
				margin-bottom: 20px;
			}
			</style>
			
		</div>
		<div style="clear:both;"></div>
		<div class="uk-grid">
		<h2>Published Groups with Courses:</h2>
		<div class="uk-width-1-4">
			<div class="uk-grid">
			
				<table class="widefat fixed" cellspacing="0">
					<thead>
						<tr>							
							<th id="columnname" class="manage-column column-columnname" scope="col">Group ID: </th>
							<th id="columnname" class="manage-column column-columnname " scope="col">Group Title:</th> 
							<th id="columnname" class="manage-column column-columnname " scope="col">Course ID:</th> 
							<th id="columnname" class="manage-column column-columnname " scope="col">Course Title:</th>
						</tr>
					</thead>
					<tbody>							
						<?php
						$i = 0;
						foreach( $_items as $item ){					
						
						$group_course = $item -> group_courses[0];
						$group_course_id = $group_course -> ID;
						$group_course_title = $group_course -> post_title;
						?>
											
						<tr class="alternate group_users">
							<td class="check-column" >
							<?php echo $item -> ID ?><br />
							<span>
							<a href="#" id="toggle_group_users_view_<?php echo $item -> ID  ?>" class="toggle_group_users_view" data-groupid="<?php echo $item -> ID  ?>">SHOW <?php echo $item -> ID  ?> GROUP USERS</a>
							</span>
							</td>
							<td class="column-columnname"><?php echo $item -> post_title ?></td>
							<td class="column-columnname"><?php echo $group_course_id ?></td>
							<td class="column-columnname"><?php echo $group_course_title ?>	</td>							
						</tr>						
						<tr  class="toggle_group_users"   style="display:none;">
							<td class="" colspan="4">
								<?php learndash_group_user_list( $item -> ID ) ?>
							</td>
						</tr>					
						<?php																	
						$i++; 
						}								
						?>								
					</tbody>
				</table>
			</div>			
		</div>
	</div>			
	<?php
	}	
}
?>