<?php

use best\kosice\Database;

if ( ! defined( 'ABSPATH' ) ) exit;

class wp_best_courses_lbgs_Settings {

	/**
	 * The single instance of wp_best_courses_lbgs_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'wpt_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_best_db_settings_page' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}
	
	public function add_best_db_settings_page(){
	    $page = add_menu_page(
		    __( 'Správa BEST databázy', 'wp-best-courses-lbgs' ) , 
			__( 'BEST DB admin', 'wp-best-courses-lbgs' ) , 
			'manage_options' , 
			'best_db_settings',
			array( $this, 'settings_page' ),
			plugins_url( 'images/BEST_DB_icon.png', __FILE__ ),
			110
		);
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
/*	public function add_menu_item () {
		$page = add_options_page( 
			__( 'Správa BEST databázy', 'wp-best-courses-lbgs' ) , 
			__( 'BEST DB admin', 'wp-best-courses-lbgs' ) , 
			'manage_options' , 
			'best_db_settings',
			array( $this, 'settings_page' ),
			'dashicons-admin-customizer',
			110
		);
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}
*/

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		// TODO REMOVE
		//wp_enqueue_style( 'farbtastic' );
    	//wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
		// TODO REMOVE
		//wp_enqueue_media();

    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'wp-best-courses-lbgs' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['events'] = array(
			'title'					=> __( 'BEST eventy', 'wp-best-courses-lbgs' ),
			'description'			=> __( 'These are fairly standard form input fields.', 'wp-best-courses-lbgs' ),
			'fields'				=> array(
				array(
					'id' 			=> 'text_field',
					'label'			=> __( 'Some Text' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This is a standard text field.', 'wp-best-courses-lbgs' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'wp-best-courses-lbgs' )
				),
				array(
					'id' 			=> 'password_field',
					'label'			=> __( 'A Password' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This is a standard password field.', 'wp-best-courses-lbgs' ),
					'type'			=> 'password',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'wp-best-courses-lbgs' )
				),
				array(
					'id' 			=> 'secret_text_field',
					'label'			=> __( 'Some Secret Text' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'wp-best-courses-lbgs' ),
					'type'			=> 'text_secret',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'wp-best-courses-lbgs' )
				),
				array(
					'id' 			=> 'text_block',
					'label'			=> __( 'A Text Block' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This is a standard text area.', 'wp-best-courses-lbgs' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text for this textarea', 'wp-best-courses-lbgs' )
				),
				array(
					'id' 			=> 'single_checkbox',
					'label'			=> __( 'An Option', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'wp-best-courses-lbgs' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'select_box',
					'label'			=> __( 'A Select Box', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'A standard select box.', 'wp-best-courses-lbgs' ),
					'type'			=> 'select',
					'options'		=> array( 'drupal' => 'Drupal', 'joomla' => 'Joomla', 'wordpress' => 'WordPress' ),
					'default'		=> 'wordpress'
				),
				array(
					'id' 			=> 'radio_buttons',
					'label'			=> __( 'Some Options', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'A standard set of radio buttons.', 'wp-best-courses-lbgs' ),
					'type'			=> 'radio',
					'options'		=> array( 'superman' => 'Superman', 'batman' => 'Batman', 'ironman' => 'Iron Man' ),
					'default'		=> 'batman'
				),
				array(
					'id' 			=> 'multiple_checkboxes',
					'label'			=> __( 'Some Items', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'You can select multiple items and they will be stored as an array.', 'wp-best-courses-lbgs' ),
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'square' => 'Square', 'circle' => 'Circle', 'rectangle' => 'Rectangle', 'triangle' => 'Triangle' ),
					'default'		=> array( 'circle', 'triangle' )
				)
			)
		);

		$settings['lbgs'] = array(
			'title'					=> __( 'Lokálne BEST skupiny', 'wp-best-courses-lbgs' ),
			'description'			=> __( 'These are some extra input fields that maybe aren\'t as common as the others.', 'wp-best-courses-lbgs' ),
			'fields'				=> array(
				array(
					'id' 			=> 'number_field',
					'label'			=> __( 'A Number' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'wp-best-courses-lbgs' ),
					'type'			=> 'number',
					'default'		=> '',
					'placeholder'	=> __( '42', 'wp-best-courses-lbgs' )
				),
				array(
					'id' 			=> 'colour_picker',
					'label'			=> __( 'Pick a colour', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'wp-best-courses-lbgs' ),
					'type'			=> 'color',
					'default'		=> '#21759B'
				),
				array(
					'id' 			=> 'an_image',
					'label'			=> __( 'An Image' , 'wp-best-courses-lbgs' ),
					'description'	=> __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'wp-best-courses-lbgs' ),
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'multi_select_box',
					'label'			=> __( 'A Multi-Select Box', 'wp-best-courses-lbgs' ),
					'description'	=> __( 'A standard multi-select box - the saved data is stored as an array.', 'wp-best-courses-lbgs' ),
					'type'			=> 'select_multi',
					'options'		=> array( 'linux' => 'Linux', 'mac' => 'Mac', 'windows' => 'Windows' ),
					'default'		=> array( 'linux' )
				)
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
     * Load settings page content
     * @return void
     */
    public function settings_page() {
        $tab = '';
        if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
            $tab .= $_GET['tab'];
        }

        switch ( $tab ) {
            // By default, the events tab is active
            //TODO: find in the code below where this default is defined and use that condition instead
            default:
            case 'events':
                $target = 'events_db';
                break;
            case 'lbgs':
                $target = 'lbgs_db';
                break;
        }

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Správa BEST databázy' , 'wp-best-courses-lbgs' ) . '</h2>' . "\n";

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			// HTML form with submit buttons
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'wp-best-courses-lbgs' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";

        // Displays a history updates table
        $html .= '<hr />';
        $html .= $this->updates_history_table( $target );

        //TODO remove: for debugging purposes we are also displaying meta table here
        $html .= '<hr />';
        $html .= $this->updates_history_table( 'meta' );

        $html .= '</div>' . "\n";

        echo $html;
	}

    /**
     * A table consisting of history of recent updates.
     *
     * @param $target string|null operation target that should be displayed in the table, null for all targets
     * @param $number_of_rows int maximum number of displayed rows
     * @param $html_class string class used for the <table> tag
     *
     * @return string HTML code of <table> tag
     */
    private function updates_history_table( $target = null, $number_of_rows = 50, $html_class = null ) {
        global $wpdb;
        $table_name  = esc_sql( "{$wpdb->prefix}best_history" );
        $historyRows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE target LIKE %s ORDER BY time DESC LIMIT %d"
                , $target == null ? '%' : $target
                , $number_of_rows
            ), ARRAY_A
        );

        //TODO consider logging error of the table select query in the table itself?
        $html = '';
        if ( $historyRows === null ) {
            $html .= "<p>Problem with history table: {$wpdb->last_error}".
                     "<br/>The requested query was: {$wpdb->last_query}</p>";
        }

        $html .= '<table';
        if ( $html_class != null ) {
            $html .= " class=\"$html_class\"";
        } else {
            //Default table if no class is used
            $html .= " border=\"1\" align=\"center\"";
        }
        $html .= '><tr>';
        //TODO translate later on
        $html .= '<th>Čas aktualizácie</th>';
        $html .= '<th>Typ aktualizácie</th>';
        $html .= '<th>Cieľ operácie</th>';
        $html .= '<th>Operácia</th>';
        $html .= '<th>Akcia</th>';
        $html .= '<th>Výsledok</th>';
        $html .= '</tr>';

        foreach ( $historyRows as $history ) {
            $html .= '<tr>';
            $html .= '<td>' . $history['time'] . '</td>';
            $html .= '<td>' . $history['request_type'] . '</td>';
            $html .= '<td>' . $history['target'] . '</td>';
            $html .= '<td>' . $history['operation'] . '</td>';
            $html .= '<td>' . $history['attempted_request'] . '</td>';
            $error_message = $history['error_message'];
            $html .= '<td>' . ( $error_message == null ? 'OK' : $history['error_message'] ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

	/**
	 * Main wp_best_courses_lbgs_Settings Instance
	 *
	 * Ensures only one instance of wp_best_courses_lbgs_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see wp_best_courses_lbgs()
	 * @return wp_best_courses_lbgs_Settings main instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
