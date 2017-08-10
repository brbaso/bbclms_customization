<?php
/**
 * Admin Page - we need it for some settings related to user import/cron job
 * 
 */  
class BbclmsAdminPage {	
	
	/**
     * A reference the class responsible for rendering the submenu page.
     *     
     */
    private $submenu_page ;
    
    public function __construct($submenu_page) {		
		$this->submenu_page = $submenu_page;               
    }
	
	/**
     * Adds a submenu for this plugin to the 'Tools' menu.
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_options_page' ) );		
		add_action( 'admin_init', array( $this,'settings_page_init' ) );
    }
	
	/**
     * Creates the submenu item and calls on the Submenu Page object to render
     * the actual contents of the page.
     */
    public function add_options_page() { 
        add_options_page(
            'Import Users/Cron',
            'Import Users/Cron',
            'manage_options',
            'import-users-cron-page',
            array( $this->submenu_page, 'display' )
        );
    }

	/**
     * Register settings, add sections and fields to settings page
     */
	public function settings_page_init() {
		 
		// register setting API
		register_setting( 'csv_files_settings', 'csv_files_settings' );
		
		// section
		add_settings_section( 'csv_files_section', 'Upload Files Data', array( $this, 'csv_files_section_callback' ), 'import-users-cron-page' );		
		
		// fields
		add_settings_field( 'csv_files_enable_cron', 'Enable CRON JOB Import', array( $this, 'csv_files_enable_cron_callback' ), 'import-users-cron-page', 'csv_files_section' );		
		add_settings_field( 'csv_files_folder_path', 'CSV Upload Folder Path<br />*(required), <br />default value: "csv_user_imports" ', array( $this, 'csv_files_folder_path_callback' ), 'import-users-cron-page', 'csv_files_section' );		
		add_settings_field( 'csv_file1_name', 'CSV FILE-1 Name', array( $this, 'csv_file1_name_callback' ), 'import-users-cron-page', 'csv_files_section' );
		add_settings_field( 'csv_file2_name', 'CSV FILE-2 Name', array( $this, 'csv_file2_name_callback' ), 'import-users-cron-page', 'csv_files_section' );
		add_settings_field( 'csv_file3_name', 'CSV FILE-3 Name', array( $this, 'csv_file3_name_callback' ), 'import-users-cron-page', 'csv_files_section' );
		
	}
	
	/**
     * Section callback
     */
	public function csv_files_section_callback() {
		echo 'Change fields bellow if necessary. Files must be in CSV format ! ( "csv_files_settings" option )';
	}	
	
	/**
     * Field callback
     */
	function csv_files_enable_cron_callback() {
		$settings = get_option( 'csv_files_settings' );
		if( $settings['csv_files_enable_cron'] ) { $checked = ' checked="checked" '; }
		echo '<input '.$checked.'  name="csv_files_settings[csv_files_enable_cron]" type="checkbox" id="csv_files_enable_cron" class="csv_files_enable_cron" value="1" />';
	}
	
	/**
     * Field callback
     */
	public function csv_files_folder_path_callback() {
		$settings = get_option( 'csv_files_settings' );		
		$csv_files_folder_path = $settings['csv_files_folder_path'];		
		if ( !$csv_files_folder_path ) {
			$csv_files_folder_path = 'csv_user_imports';
		}		
		echo '/wp-content/uploads/ <input type="text" name="csv_files_settings[csv_files_folder_path]" value="'.$csv_files_folder_path.'" /><br />';
	}
	
	/**
     * Field callback
     */
	public function csv_file1_name_callback() {
		$settings = get_option( 'csv_files_settings' );		
		$csv_file1_name = $settings['csv_file1_name'];		
		echo '/wp-content/uploads/'.$settings['csv_files_folder_path'].'/ <input type="text" name="csv_files_settings[csv_file1_name]" value="'.$csv_file1_name.'" /> .csv<br />';
	}
	
	/**
     * Field callback
     */
	public function csv_file2_name_callback() {
		$settings = get_option( 'csv_files_settings' );		
		$csv_file2_name = $settings['csv_file2_name'];		
		echo '/wp-content/uploads/'.$settings['csv_files_folder_path'].'/ <input type="text" name="csv_files_settings[csv_file2_name]" value="'.$csv_file2_name.'" /> .csv<br />';
	}
	
	/**
     * Field callback
     */
	public function csv_file3_name_callback() {
		$settings = get_option( 'csv_files_settings' );		
		$csv_file3_name = $settings['csv_file3_name'];		
		echo '/wp-content/uploads/'.$settings['csv_files_folder_path'].'/ <input type="text" name="csv_files_settings[csv_file3_name]" value="'.$csv_file3_name.'" /> .csv<br />';
	}	
	
}
