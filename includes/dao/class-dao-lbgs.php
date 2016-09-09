<?php
/**
 * Created by PhpStorm.
 * User: scscgit
 * Date: 09.09.2016
 * Time: 22:14
 */

namespace best\kosice\best_courses_lbgs\dao;

use best\kosice\best_courses_lbgs\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DAO class for LBGS' operations using the database.
 *
 * <p>DAO classes should be used for all database operations,
 * database should never be queried directly from other classes.
 *
 * @package best\kosice\best_courses_lbgs\dao
 * @author  scscgit
 */
class DAO_LBGS {

    /**
     * The single instance of DAO_Events.
     * @var self
     */
    private static $_instance;

    /**
     * Constructor function with private access, singleton class.
     */
    private function __construct() {
    }

    /**
     * Return the instance of DAO_Events.
     *
     * @return self
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

}
