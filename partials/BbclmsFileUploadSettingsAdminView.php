<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * File Upload options view
 */

class BbclmsFileUploadSettingsAdminView{
	
	
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
		?>
		<div class="wrap">
			<div class="wrap-left">
			<h1>Learndash File Upload Settings</h1>
			<form action="options.php" method="POST">
				<?php settings_fields( 'ld_file_upload_settings' ); ?>
				<?php do_settings_sections( 'learndash-lms/file-upload-settings' ); ?>
				<?php submit_button(); ?>
			</form>
			</div>
		</div>					
	<?php
	}
}
?>