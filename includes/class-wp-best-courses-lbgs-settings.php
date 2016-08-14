<?php

use best\kosice\Database;
use best\kosice\LogRequestType;
use best\kosice\LogTarget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//TODO: move to a more suitable location, e.g. to a namespace to be statically called from all other files
// Representation of the plugin for text translation purposes
const PLUGIN_NAME = 'wp-best-courses-lbgs';

//TODO: consider renaming to Settings and add namespace, should not cause collisions if within namespace
class wp_best_courses_lbgs_Settings {

    // Plugin option names without prefix
    const OPTION_NAME_HISTORY_DISPLAY_MAX_ROWS = 'history_max_displayed_rows';
    const OPTION_NAME_HISTORY_DISPLAY_SUCCESS = 'display_history_success';
    const OPTION_NAME_AUTOMATIC_REFRESH = 'automatic_refresh';

    // Plugin option defaults
    const OPTION_DEFAULT_HISTORY_DISPLAY_MAX_ROWS = 50;
    const OPTION_DEFAULT_HISTORY_DISPLAY_SUCCESS = true;
    const OPTION_DEFAULT_AUTOMATIC_REFRESH = true;

    /**
     * The single instance of wp_best_courses_lbgs_Settings.
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null;

    /**
     * The main plugin object.
     * @var     object
     * @access  public
     * @since   1.0.0
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

    public function __construct( $parent ) {
        $this->parent = $parent;

        $this->base = Database::OPTION_BASE_PREFIX;

        // Initialize settings
        add_action( 'init', array( $this, 'init_settings' ), 11 );

        // Register plugin settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Add settings page to menu
        add_action( 'admin_menu', array( $this, 'add_best_db_settings_page' ) );

        // Add settings link to plugins page
        add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file )
            , array( $this, 'add_settings_link' )
        );
    }

    /**
     * Initialize settings.
     */
    public function init_settings() {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_best_db_settings_page() {
        $page = add_menu_page(
            __( 'Správa BEST databázy', PLUGIN_NAME ),
            __( 'BEST DB admin', PLUGIN_NAME ),
            'manage_options',
            'best_db_settings',
            array( $this, 'settings_page' ),
            plugins_url( '../assets/images/BEST_DB_icon.png', __FILE__ ),
            110
        );
        //add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
    }

    /**
     * Load settings JS & CSS.
     */
    public function settings_assets() {

        // We're including the farbtastic script & styles here because they're needed for the colour picker
        // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
        // TODO REMOVE
        //wp_enqueue_style( 'farbtastic' );
        //wp_enqueue_script( 'farbtastic' );

        // We're including the WP media scripts here because they're needed for the image upload field
        // If you're not including an image upload then you can leave this function call out
        // TODO REMOVE
        //wp_enqueue_media();

        wp_register_script( $this->parent->_token . '-settings-js'
            , $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js'
            , array( 'farbtastic', 'jquery' ), '1.0.0' );
        wp_enqueue_script( $this->parent->_token . '-settings-js' );
    }

    /**
     * Add settings link to plugin list table.
     *
     * @param  array $links Existing links
     *
     * @return array        Modified links
     */
    public function add_settings_link( $links ) {
        $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' .
                         __( 'Settings', PLUGIN_NAME ) . '</a>';
        array_push( $links, $settings_link );

        return $links;
    }

    /**
     * Build settings fields to be registered in register_settings().
     * @see register_settings()
     * @return array Fields to be displayed on the settings page
     */
    private function settings_fields() {
        $settings['events'] = array(
            'title' => __( 'BEST events', PLUGIN_NAME ),
        );

        $settings['lbgs'] = array(
            'title' => __( 'Local BEST groups', PLUGIN_NAME ),
        );

        $settings['configuration'] = array(
            'title'       => __( 'Configuration', PLUGIN_NAME ),
            'description' => __( 'Plugin configuration section', PLUGIN_NAME ),
            'fields'      => array(
                array(
                    'id'          => self::OPTION_NAME_HISTORY_DISPLAY_MAX_ROWS,
                    'label'       => __( 'Displayed rows', PLUGIN_NAME ),
                    'description' => __( 'Maximum number of rows in the history (under all tabs) to be displayed at once' .
                                         '.', PLUGIN_NAME ),
                    'type'        => 'number',
                    'default'     => self::OPTION_DEFAULT_HISTORY_DISPLAY_MAX_ROWS,
                    'placeholder' => 0,
                ),
                array(
                    'id'          => self::OPTION_NAME_HISTORY_DISPLAY_SUCCESS,
                    'label'       => __( 'Display success', PLUGIN_NAME ),
                    'description' => __( 'Shows successful operations in the history table', PLUGIN_NAME ) . '.',
                    'type'        => 'checkbox',
                    'default'     => self::OPTION_DEFAULT_HISTORY_DISPLAY_SUCCESS,
                ),
                array(
                    'id'          => self::OPTION_NAME_AUTOMATIC_REFRESH,
                    'label'       => __( 'Automatic refresh', PLUGIN_NAME ),
                    'description' => __( 'Allows database to be updated in regular intervals', PLUGIN_NAME ) . '.',
                    'type'        => 'checkbox',
                    'default'     => self::OPTION_DEFAULT_AUTOMATIC_REFRESH,
                ),
            ),
        );

        $settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

        return $settings;
    }

    /**
     * Register plugin settings, adding all setting fields using the prepared array to the page.
     */
    public function register_settings() {
        if ( is_array( $this->settings ) ) {
            // Check posted/selected tab
            $current_section = '';
            if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
                $current_section = $_POST['tab'];
            } else if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
                $current_section = $_GET['tab'];
            }

            // Register each individual setting section
            foreach ( $this->settings as $section => $data ) {
                // Skip all non-current sections
                if ( $current_section && $current_section != $section ) {
                    continue;
                }

                // Add section displaying settings title and description to the page
                add_settings_section( $section
                    , null //$data['title']
                    , array( $this, 'echo_settings_section_description' )
                    , $this->parent->_token . '_settings' );

                // Adds all setting fields to the settings page
                if ( array_key_exists( 'fields', $data ) ) {
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
                        add_settings_field( $field['id']
                            , $field['label']
                            , array( $this->parent->admin, 'display_field' )
                            , $this->parent->_token . '_settings', $section
                            , array( 'field' => $field, 'prefix' => $this->base ) );
                    }

                    if ( ! $current_section ) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Echoes a settings section description if it exists.
     *
     * @param array $section section array containing 'id' key that references a setting
     */
    public function echo_settings_section_description( $section ) {
        $setting = $description = $this->settings[ $section['id'] ];
        if ( array_key_exists( 'description', $setting ) ) {
            $description = $this->settings[ $section['id'] ]['description'];
            echo '<p> ' . $description . '</p>' . "\n";
        }
    }

    /**
     * Load settings page content.
     */
    public function settings_page() {
        $tab = '';
        if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
            $tab = $_GET['tab'];
        }

        // Set the correct history table $target based on a current tab
        switch ( $tab ) {
            // By default, the events tab is active
            //TODO: find in the code below where this default is defined and use that condition instead
            default:
            case 'events':
                //Checks for the request for manually updating table
                if ( isset( $_POST['manually_update'] ) ) {
                    Database::refresh_db_best_events( LogRequestType::MANUAL );
                }
                $target = 'events_db';
                break;
            case 'lbgs':
                //Checks for the request for manually updating table
                if ( isset( $_POST['manually_update'] ) ) {
                    Database::refresh_db_best_lbgs( LogRequestType::MANUAL );
                }
                $target = 'lbgs_db';
                break;
            case 'configuration':
                $target = 'meta';
                break;
        }

        // Build page HTML
        $html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
            $html .= '<h2>' . __( 'Správa BEST databázy', PLUGIN_NAME ) . '</h2>' . "\n";

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
                    $html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' .
                             esc_html( $data['title'] ) . '</a>' . "\n";

                    ++ $c;
                }

                $html .= '</h2>' . "\n";
            }

        if ( $tab != 'configuration' ) {
            // Displaying number of entries in the table
            $table_name        = ( $tab == 'lbgs' ? Database::BEST_LBGS_TABLE : Database::BEST_EVENTS_TABLE );
            $table_row_entries = Database::count_db_table_rows( $table_name );
            $table_context     = $tab == 'lbgs'
                ? __( 'Aktuálny počet lokálnych BEST skupín v tabuľke', PLUGIN_NAME )
                : __( 'Aktuálny počet eventov v tabuľke', PLUGIN_NAME );
            $html .= '<p>' . $table_context . ': ' . $table_row_entries . '</p>';
        }

            // Setting fields and submit button
            $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

                // Get settings fields
                ob_start();
                settings_fields( $this->parent->_token . '_settings' );
                do_settings_sections( $this->parent->_token . '_settings' );
                $html .= ob_get_clean();

                if ( $tab == 'configuration' ) {
                    $html .= '<p class="submit">' . "\n";
                        $html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
                        $html .= '<input name="Submit" type="submit" class="button-primary" value="' .
                                 esc_attr__( 'Save Settings', PLUGIN_NAME ) . '" />' . "\n";
                    $html .= '</p>' . "\n";
                }
            $html .= '</form>' . "\n";

            $manual_update_button_text = $tab == 'lbgs'
                ? __( 'Update groups', PLUGIN_NAME )
                : __( 'Update events', PLUGIN_NAME );

            // Form for manual database update
            if($tab != 'configuration') {
                $html .= '<form method="post">' . "\n";
                    $html .= '<p class="submit">' . "\n";
                        $html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
                        $html .= '<input name="manually_update" type="submit" class="button-primary" value="' .
                                 esc_attr( $manual_update_button_text ) .
                                 '" />' . "\n";
                    $html .= '</p>' . "\n";
                $html .= '</form>' . "\n";
            }

        // Displays an updates history table
        $html .= '<hr />';
        $html .= $this->updates_history_table( $target, 'history_table' );

        $html .= '</div>' . "\n";

        echo $html;
    }

    /**
     * A table consisting of history of recent updates.
     *
     * @param $target string|null operation target that should be displayed in the table, null for all targets
     * @param $html_class string class used for the < table > tag
     *
     * @return string HTML code of < table > tag
     */
    private function updates_history_table( $target = null, $html_class = null ) {
        global $wpdb;
        $table_name = esc_sql( $wpdb->prefix . Database::BEST_HISTORY_TABLE );

        $history_max_displayed_rows = get_option(
            Database::OPTION_BASE_PREFIX . self::OPTION_NAME_HISTORY_DISPLAY_MAX_ROWS,
            self::OPTION_DEFAULT_HISTORY_DISPLAY_MAX_ROWS );
        $display_history_success    = get_option(
            Database::OPTION_BASE_PREFIX . self::OPTION_NAME_HISTORY_DISPLAY_SUCCESS,
            self::OPTION_DEFAULT_HISTORY_DISPLAY_SUCCESS );

        $history_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE target LIKE %s "
                . ( $display_history_success ? "" : "AND error_message IS NOT null " )
                . "ORDER BY time DESC LIMIT %d"
                , $target == null ? '%' : $target
                , $history_max_displayed_rows
            ), ARRAY_A
        );

        $html = '';
        if ( $history_rows === null ) {
            $html .= "<p>" . __( 'Problem with history table', PLUGIN_NAME ) . ": {$wpdb->last_error}"
                     . "<br/>" . __( 'The requested query was', PLUGIN_NAME ) . ": {$wpdb->last_query}</p>";
            Database::log_error( LogTarget::META, LogRequestType::AUTOMATIC
                , 'Displaying history table', $wpdb->last_query, $wpdb->last_error );
        }

        $html .= '<table';
        if ( $html_class != null ) {
            $html .= " class=\"$html_class\"";
        } else {
            // Default table if no class is used
            $html .= " border=\"1\" align=\"center\"";
        }
        $html .= '><tr>';

        $html .= '<th>' . __( 'Čas aktualizácie', PLUGIN_NAME ) . '</th>';
        $html .= '<th>' . __( 'Typ aktualizácie', PLUGIN_NAME ) . '</th>';
        $html .= '<th>' . __( 'Operation', PLUGIN_NAME ) . '</th>';
        $html .= '<th>' . __( 'Result', PLUGIN_NAME ) . '</th>';
        $html .= '</tr>';

        foreach ( $history_rows as $history ) {
            $html .= '<tr>';

            // Time
            $html .= '<td>' . $history['time'] . '</td>';

            // Request type
            $request_type = $history['request_type'];
            switch ( $request_type ) {
                case LogRequestType::AUTOMATIC:
                    $request_type = _x( 'Automatic', 'update type', PLUGIN_NAME );
                    break;
                case LogRequestType::MANUAL:
                    $request_type = _x( 'Manual', 'update type', PLUGIN_NAME );
                    break;
            }
            $html .= '<td>' . $request_type . '</td>';

            // Operation
            $html .= '<td>' . $history['operation'] . '</td>';

            // Result
            $error_message = $history['error_message'];
            $html .= '<td title="' . esc_attr( $history['attempted_request'] ) . '">' .
                     ( $error_message == null
                         ? __( 'OK', PLUGIN_NAME )
                         // Attempts to translate the error message
                         : __( $error_message, PLUGIN_NAME )
                     ) . '</td>';

            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Main wp_best_courses_lbgs_Settings instance.
     *
     * Ensures only one instance of wp_best_courses_lbgs_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @see wp_best_courses_lbgs()
     * @return wp_best_courses_lbgs_Settings main instance
     */
    public static function instance( $parent ) {
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
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
    } // End __wakeup()
}
