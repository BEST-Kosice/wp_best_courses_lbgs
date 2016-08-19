<?php

namespace best\kosice\best_courses_lbgs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main class of plugin wp_best_courses_lbgs.
 * Simple instantiation takes care of registering everything the plugin needs to work within the WordPress.
 *
 * @package best\kosice\best_courses_lbgs
 */
class Best_Courses_LBGS {

    /**
     * The single instance of best_courses_lbgs.
     * @var      Best_Courses_LBGS
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object.
     * @var     object
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * Constructor function.
     *
     * @since   1.0.0
     */
    public function __construct( $file = '', $version = '1.0.0' ) {
        $this->_version = $version;
        $this->_token   = PLUGIN_NAME;

        // Load plugin environment variables
        $this->file       = $file;
        $this->dir        = dirname( $this->file );
        $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

        $this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        // Register plugin lifecycle hooks
        register_activation_hook( $this->file, array( $this, 'install' ) );
        register_deactivation_hook( $this->file, array( $this, 'deactivation' ) );

        // Register periodic cron event
        add_action( 'best_courses_lbgs_cron_task', array( $this, 'cron_task' ) );

        // Register all shortcodes
        add_shortcode( 'best_events', array( $this, 'best_events_shortcode' ) );
        add_shortcode( 'best_lbgs', array( $this, 'best_lbgs_shortcode' ) );
        add_shortcode( 'best_lbgs_map', array( $this, 'best_lbgs_map_shortcode' ) );

        // Load frontend JS & CSS
        // TODO refactor
        //add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
        //add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

        // Load admin JS & CSS
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

        // Load API for generic admin functions
        if ( is_admin() ) {
            $this->admin = new Admin_API();
        }

        // Initialization event
        add_action( 'init', array( $this, 'initialize' ), 0 );

        // Build Settings instance
        $this->settings = Settings::instance( $this );
    } // End __construct()

    /**
     * Load frontend CSS.
     *
     * @since   1.0.0
     */
    public function enqueue_styles() {
        wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(),
            $this->_version );
        wp_enqueue_style( $this->_token . '-frontend' );
    } // End enqueue_styles()

    /**
     * Load frontend Javascript.
     *
     * @since   1.0.0
     */
    public function enqueue_scripts() {
        wp_register_script( $this->_token . '-sortable', esc_url( $this->assets_url ) . 'js/lib/sortable.min.js' );

        if ( ! wp_script_is( 'jquery', 'registered' ) ) {
            wp_register_script( $this->_token . '-jquery', esc_url( $this->assets_url ) . 'js/lib/jquery-1.7.min.js' );
            wp_enqueue_script( $this->_token . '-jquery' );
        }

        wp_register_script( $this->_token . '-stackable', esc_url( $this->assets_url ) . 'js/lib/stackable.min.js' );
        wp_register_script( $this->_token . '-frontend',
            esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js' );
        wp_register_script( $this->_token . '-snap.svg', esc_url( $this->assets_url ) . 'js/lib/snap.svg-min.js' );
        wp_register_script( $this->_token . '-map',
            esc_url( $this->assets_url ) . 'js/map' . $this->script_suffix . '.js' );

        wp_enqueue_script( $this->_token . '-sortable' );
        wp_enqueue_script( $this->_token . '-stackable' );
        wp_enqueue_script( $this->_token . '-frontend' );
        wp_enqueue_script( $this->_token . '-snap.svg' );
        wp_enqueue_script( $this->_token . '-map' );
    } // End enqueue_scripts()

    /**
     * Load admin CSS.
     *
     * @since   1.0.0
     */
    public function admin_enqueue_styles() {
        wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(),
            $this->_version );
        wp_enqueue_style( $this->_token . '-admin' );
    } // End admin_enqueue_styles()

    /**
     * Load admin Javascript.
     *
     * @since   1.0.0
     */
    public function admin_enqueue_scripts() {
        wp_register_script( $this->_token . '-admin',
            esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ),
            $this->_version );
        wp_enqueue_script( $this->_token . '-admin' );
    } // End admin_enqueue_scripts()

    /**
     * Load plugin textdomain.
     *
     * @since   1.0.0
     */
    public function load_plugin_textdomain() {
        $domain = PLUGIN_NAME;

        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_plugin_textdomain()

    /**
     * Main best_courses_lbgs Instance. When called for the first time during a single PHP execution,
     * it should be called from a current file using __FILE__ as $file to initialize the script path.
     *
     * Ensures only one instance of best_courses_lbgs is loaded or can be loaded.
     *
     * @since  1.0.0
     * @return self main instance
     */
    public static function instance( $file = '', $version = '1.0.0' ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $file, $version );
        }

        return self::$_instance;
    } // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __wakeup()

    /**
     * This function removes registered WP Cron events by a specified event name.
     * Source: <https://wordpress.org/support/topic/wp_unschedule_event-and-wp_clear_scheduled_hook-do-not-clear-events>
     *
     * Alternatives that were tried first, but were not working correctly:
     * wp_clear_scheduled_hook('best_courses_lbgs_cron_task');
     * wp_unschedule_event(wp_next_scheduled('best_courses_lbgs_cron_task'),'best_courses_lbgs_cron_task');
     *
     * @param $event_name string registered name of the scheduled cron event
     */
    public static function unschedule_cron_events_by_name( $event_name ) {
        $cron_events = _get_cron_array();
        foreach ( $cron_events as $n_timestamp => $arr_event ) {
            if ( isset( $cron_events[ $n_timestamp ][ $event_name ] ) ) {
                unset( $cron_events[ $n_timestamp ] );
            }
        }
        _set_cron_array( $cron_events );
    } // End unschedule_cron_events_by_name()

    /**
     * Cron periodical (hourly) event of this plugin.
     * Is also run in the plugin activation event.
     *
     * List of actions:
     * 1. Refreshes BEST database tables (if not disabled by the user)
     */
    function cron_task() {
        // If the user did not explicitly disable automatic refresh, the database gets refreshed
        if ( get_option( Database::OPTION_BASE_PREFIX . Settings::OPTION_NAME_AUTOMATIC_REFRESH
            , Settings::OPTION_DEFAULT_AUTOMATIC_REFRESH )
        ) {
            Database::refresh_db_best_events( LogRequestType::AUTOMATIC );
            Database::refresh_db_best_lbgs( LogRequestType::AUTOMATIC );
        }
    } // End cron_task()

    /**
     * Plugin installation event. Runs on activation.
     *
     * List of actions:
     * 1. Stores the plugin version number as an option.
     * 2. Schedules cron events
     * 3. Attempts to upgrade the database and then creates any missing SQL tables
     * 4. Runs the cron event to (initially) refresh the BEST database to the most current version
     *
     * @since   1.0.0
     */
    public function install() {
        update_option( $this->_token . '_version', $this->_version );

        wp_schedule_event( time(), 'hourly', 'best_courses_lbgs_cron_task' );

        // Attempts to upgrade the database version before creating any missing tables
        Database::upgrade_database();
        Database::create_all_tables();

        $this->cron_task();
    } // End install()

    /**
     * Plugin deactivation event.
     *
     * List of actions:
     * 1. Removes cron scheduling
     */
    public function deactivation() {
        $this->unschedule_cron_events_by_name( 'best_courses_lbgs_cron_task' );
    } // End deactivation()

    /**
     * Runs a PHP code in a file and instead of displaying the resulting HTML page, only returns it as a string.
     * Source: <http://stackoverflow.com/questions/1683771/execute-a-php-file-and-return-the-result-as-a-string>
     *
     * @param $php_file string PHP file to be run
     *
     * @return string PHP result as HTML, that is supposed to be displayed in the browser
     */
    public static function run_php_file_for_html( $php_file ) {
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include( $php_file );
        $returned = ob_get_clean();

        return $returned;

        //Alternative, that can be tested for possible higher performance:
        //ob_start();
        //get_template_part('my_form_template');
        //return ob_get_clean();
    } // End run_php_file_for_html()

    /**
     * Shortcodes for inserting PHP files into any WP page.
     *
     * Important points:
     * - Remember to use return and not echo,
     * anything that is echoed will be output to the browser, but it won't appear in the correct place on the page.
     * - Take caution when using hyphens in the name of your shortcodes.
     *
     * Reference: <https://codex.wordpress.org/Shortcode_API>
     */

    /**
     * Register shortcode [best_events]
     */
    public function best_events_shortcode() {
        return $this->run_php_file_for_html( 'shortcodes/events.php' );
    }

    /**
     * Register shortcode [best_lbgs]
     */
    public function best_lbgs_shortcode() {
        return $this->run_php_file_for_html( 'shortcodes/local-best-groups.php' );
    }

    /**
     * Register shortcode [best_lbgs_map]
     */
    public function best_lbgs_map_shortcode() {
        return $this->run_php_file_for_html( 'shortcodes/lbgs-clickable-map.php' );
    }

    /**
     * Add custom buttons to the TinyMCE editor using javascript.
     */
    public function wptuts_add_buttons( $plugin_array ) {
        $plugin_array['wptuts'] = $this->assets_url . 'js/shortcode.min.js';

        return $plugin_array;
    }

    /**
     * Register custom buttons in the TinyMCE editor.
     */
    public function wptuts_register_buttons( $buttons ) {
        array_push( $buttons, 'events', 'lbgs', 'lbgs_map' );

        return $buttons;
    }

    /**
     * Initialization event, gets executed each time a page loads.
     *
     * List of actions:
     * 1. Handle localization
     * 2. Upgrades the database to the newest version
     * 3. Applying TinyMCE filters to add new buttons.
     * 4. Registers a custom post request_type for BEST events
     *    Reference: <http://www.wpbeginner.com/wp-tutorials/how-to-create-custom-post-types-in-wordpress/>
     *    (Is not currently being used for anything and may be removed later)
     */
    public function initialize() {
        // Load plugin localization
        $this->load_plugin_textdomain();

        // Always attempt to upgrade the database version
        Database::upgrade_database();

        // Add new shortcode buttons to the text editor
        add_filter( "mce_external_plugins", array( $this, "wptuts_add_buttons" ) );
        add_filter( 'mce_buttons', array( $this, 'wptuts_register_buttons' ) );

        register_post_type( 'best-events',
            // Custom post request_type options
            array(
                'labels'  => array(
                    'name'          => __( 'BEST Events' ),
                    'singular_name' => __( 'BEST Event' )
                ),
                'public'  => true,
                //'has_archive'     => true,

                // Hiding from the user administration view
                'show_ui' => false,

                'capability_type' => 'page',
                'hierarchical'    => false,
                //'hierarchical'    => true,

                // Rewriting path in the address bar
                'rewrite'         => array(
                    'slug'       => '/',
                    'with_front' => true
                ),

                'supports' => array(
                    //'title',
                    //'editor',
                    //'custom-fields',
                ),
            )
        );
    } // End initialize()

}
