<?php
/**
 * Created by PhpStorm.
 * User: scscgit
 * Date: 09.09.2016
 * Time: 15:12
 */

namespace best\kosice\best_courses_lbgs\dao;

use best\kosice\best_courses_lbgs\Database;
use best\kosice\best_courses_lbgs\LogTarget;
use best\kosice\best_courses_lbgs\TableName;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DAO class for Events' operations using the database.
 *
 * <p>TODO: consider:
 * - making DAO singletons, private constructors etc., but compatible with unit tests? (Singletons can cause problems?)
 * - making abstract dao, common for all dao classes
 * - making a caching functionality so that it keeps track of database "changes" that invalidate the cache,
 *   returning a cached query result value when there were no changes and there were subsequent similar requests.
 * - what should be the grouping criteria of dao methods? DAO_Events and DAO_LBGS can cause conflict even in the
 *   get_time_of_most_recent_refresh() function, considering it can actually target LBGS too.
 *
 * <p>DAO classes should be used for all database operations,
 * database should never be queried directly from other classes.
 *
 * @package best\kosice\best_courses_lbgs\dao
 * @author  scscgit
 */
class DAO_Events {

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

    /**
     * @param $log_target string|null the event target to get the most recent refresh, null to use all tables,
     *                    use enum LogTarget
     *
     * @return string|null date of the last refresh of the selected target, null if there were no update
     */
    public function get_time_of_most_recent_refresh( $log_target = LogTarget::EVENTS ) {
        $log_target = esc_sql( $log_target );

        if ( $log_target ) {
            $and_target = "target = '$log_target'";
        } else {
            $and_target = '';
        }

        $last_time_query = Database::get_simple_select_query(
            TableName::HISTORY,
            "max(time)",
            $and_target,
            null,
            1 );

        if ( $and_target ) {
            $and_target = "and $and_target";
        }
        $last_successful_refresh_row = Database::get_single_row(
            TableName::HISTORY,
            "time = ($last_time_query)$and_target and error_message is null" );

        if ( ! $last_successful_refresh_row
             || count( $last_successful_refresh_row ) == 0
             || ! $last_successful_refresh_row[0]['time']
        ) {
            return null;
        }

        return $last_successful_refresh_row[0]['time'];
    }

}
