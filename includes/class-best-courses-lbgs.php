<?php

namespace best\kosice\best_courses_lbgs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main class of plugin best_courses_lbgs.
 *
 * @package best\kosice\best_courses_lbgs
 */
class best_courses_lbgs {

    /**
     * The single instance of best_courses_lbgs.
     * @var    best_courses_lbgs
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

        register_activation_hook( $this->file, array( $this, 'install' ) );

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

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

        // Build Settings instance
        if ( is_null( $this->settings ) ) {
            $this->settings = Settings::instance( $this );
        }
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
     * Load plugin localisation.
     *
     * @since   1.0.0
     */
    public function load_localisation() {
        load_plugin_textdomain( PLUGIN_NAME, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_localisation()

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
     * Main best_courses_lbgs Instance. When called for the first time during a single PHP run,
     * it should be called from a current file using __FILE__ as $file to initialize the script path.
     *
     * Ensures only one instance of best_courses_lbgs is loaded or can be loaded.
     *
     * @since   1.0.0
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
     * Installation. Runs on activation.
     *
     * @since   1.0.0
     */
    public function install() {
        $this->_log_version_number();
    } // End install()

    /**
     * Log the plugin version number.
     *
     * @since   1.0.0
     */
    private function _log_version_number() {
        update_option( $this->_token . '_version', $this->_version );
    } // End _log_version_number()

}
