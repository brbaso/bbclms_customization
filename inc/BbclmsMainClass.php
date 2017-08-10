<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'BbclmsBPMainClass' ) ):

/** 
 *  Bbclms cusomization Plugin  Main Class
 * 
 */
class BbclmsMainClass {	
	
    private $includes = [
		'BbclmsDB',
        'BbclmsLoader'        
    ];
	
    private $admin_includes = [	
		'BbclmsAdminPage',        
		'BbclmsFileUploadSettingsPage'        
    ];
	
	 private $partials = [
		'BbclmsAdminView',        
		'BbclmsFileUploadSettingsAdminView'        
    ];

    public $bp_enabled = false;	    

	public $functions;

    public $plugin_dir = '';
    public $plugin_url = '';
	
    public $includes_dir = '';
    public $includes_url = '';

	public $partials_dir = '';
    public $partials_url = '';
	
    public $assets_dir = '';
    public $assets_url = '';
	
	public $vendor_dir = '';
    public $vendor_url = '';
      
	/**
	 * Main BbclmsMainClass Instance.
	 *	 
	 */
	public static function instance(){
		// Store the instance locally to avoid private static replication
		static $instance = null;

		//Check the buddypress group component is active
		$active_component = get_option('bp-active-components');

		if ( ! isset ( $active_component['groups'] ) ) {
			$instance = new BbclmsMainClass;
			add_action( 'admin_notices', array( $instance, 'show_notices_bp' ) );

			//No need to go further until group component has not active
			return $instance;
		}
		
		if ( !class_exists( 'SFWD_LMS' ) ) {
			$instance = new BbclmsMainClass;
			add_action( 'admin_notices', array( $instance, 'show_notices_ld' ) );

			//No need to go further until group component has not active
			return $instance;
		}

		// Only run these methods if they haven't been run previously
		if ( null === $instance ) {
			$instance = new BbclmsMainClass;
			$instance -> init();
		}

		// Always return the instance
		return $instance;
	}
	
	private function __construct() { /* Do nothing here */ }

	/**
     * Setup.
     *
     */
    private function init() {
		global $bp;
		
        // Bbclms Customization root directory
        $this->file          = BBCLMS_CUSTOMIZATION_PLUGIN_FILE;
        $this->basename      = plugin_basename( $this->file );
        $this->plugin_dir    = BBCLMS_CUSTOMIZATION_PLUGIN_DIR;
        $this->plugin_url    = BBCLMS_CUSTOMIZATION_PLUGIN_URL;

        // Includes
        $this->includes_dir = $this->plugin_dir . 'inc';
        $this->includes_url = $this->plugin_url . 'inc';
		
		// Partials
        $this->partials_dir = $this->plugin_dir . 'partials';
        $this->partials_url = $this->plugin_url . 'partials';
		
		// Vendor
        $this->vendor_dir = $this->plugin_dir . 'vendor';
        $this->vendor_url = $this->plugin_url . 'vendor';		
		
		$this -> functions = $this -> load_functions();
		$this -> load_main();		
		$this -> load_admin();				
		$this -> load_file_upload_admin();
		$this -> load_csvimport();		
    }	

		
	/**
     * Load CSV Import class.
     *
     */
    function load_csvimport() {
        require $this->vendor_dir. '/autoload.php';
		require $this->includes_dir. '/BbclmsLdImportProcess.php';
		$dbhandle = new BbclmsDB();		
		$csvimport = new BbclmsLdImportProcess( $dbhandle, $this -> functions );
        return $csvimport;
    }
	
    /**
     * Include required admin files.
     *
     */
    public function load_admin() {
        $this->do_includes($this->admin_includes);		
        $this->do_include_partials($this -> partials);		
		$dbhandle = new BbclmsDB();
		
		// Settings page - cron import ..
		$admin_view = new BbclmsAdminView($dbhandle, $this -> functions);
		$admin =  new BbclmsAdminPage( $admin_view );
		return $admin -> init();
			
    }
	
	/**
     * Include required File Upload Settings admin files.
     *
     */
    public function load_file_upload_admin() {
        $this->do_includes($this->admin_includes);		
        $this->do_include_partials($this -> partials);		
		$dbhandle = new BbclmsDB();	
		
		// LearnDash File Uploads Options page - file types, size, max # of uploads ...
		$admin_view = new BbclmsFileUploadSettingsAdminView($dbhandle, $this -> functions);
		$admin =  new BbclmsFileUploadSettingsPage( $admin_view );
		return $admin -> init();		
    }

    /**
     * Include required files.
     *
     */
    public function load_main() {
        $this->do_includes($this->includes);
        return BbclmsLoader::instance();
    }
	
	/**
     * Load Functions.
     *
     */
	public function load_functions() {				
        require_once( $this->includes_dir . '/BbclmsFunctions.php' );
        return BbclmsFunctions::instance();
    }  

    /**
     * Include required array of files in the includes directory
     *
     */
    public function do_includes( $includes = [] ) {
        foreach ($includes as $include) {			
            require_once( $this->includes_dir . '/' . $include . '.php' );
        }
    }
	
	/**
     * Include required array of files in the partials directory
     *
     */
	public function do_include_partials( $partials = [] ) {
        foreach ($partials as $partial) {
			require_once( $this->partials_dir . '/' . $partial . '.php' );
        }
    } 	
	
	/**
	 * Show notices when buddypress component is not enable
	 *
	 * @since 1.1
	 */
	public function show_notices_bp() {
		echo '<div class="error">';
		echo '<p>' . sprintf( __( '<strong>BBCLMS CUSTOMIZATION</strong> requires User Groups component to be enabled. Please enable it in your <a href="%s">BuddyPress Settings</a>.', 'buddypress-learndash' ), admin_url( 'admin.php?page=bp-components' ) ) . '</p>';
		echo '</div>';
	}
	
	/**
	 * Show notices when learndash component is not enable
	 *
	 * @since 1.1
	 */
	public function show_notices_ld() {
		echo '<div class="error">';
		echo '<p>' .__( '<strong>BBCLMS CUSTOMIZATION</strong> requires LearnDash plugin to be enabled. Please enable it.' ) . '</p>';
		echo '</div>';
	}
}
endif;
