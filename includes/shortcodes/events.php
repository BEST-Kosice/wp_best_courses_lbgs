<?php

use best\kosice\best_courses_lbgs\BEST_Courses_LBGS;
use best\kosice\best_courses_lbgs\dao\DAO_Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BEST_Courses_LBGS::instance()->enqueue_styles();
BEST_Courses_LBGS::instance()->enqueue_scripts();

global $wpdb;
$data = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'best_events', ARRAY_A);

// TODO all operation make a as functions

$num_rows = $wpdb->num_rows;
    if ($data){
        $months_short = array('Jan', 'Feb', 'Mar', 'Ap', 'May', 'Jun', 'Jul', 'Au', 'Sep', 'Oct', 'Nov', 'Dec');
        $month_numbers = array('1.', '2.', '3.', '4.', '5.', '6.', '7.' ,'8.' ,'9.' , '10.', '11.', '12.');
        $tbody = '';
        for ($i = 0; $i < $num_rows; $i++){
            //data parsing
            //academic complexity
            $first_char = ( substr($data[$i]['acad_compl'], 0, 1));
            //place of event - $place[0] = city, $place[1] = state
            $place = explode(',', preg_replace(
                '/\(([a-zA-Z]+)\)/', ', $1', $data[$i]['place'])
            );

            //startdate = $dates[0] and enddate = $dates[1]
            $dates = explode('- ', $data[$i]['dates']);
            $startdate; $enddate; $year;
            for ($j = 0; $j < 12; $j++){
                $position = strpos($dates[1], $months_short[$j], 0);
                if ($position){

                    $enddate = substr($dates[1], 0,
                        strpos($dates[1], ' ', 0)
                    ) . '.' . $month_numbers[$j];
                    $startdate = substr($dates[0], 0,
                        strpos($dates[0], ' ', 0)
                    ) . '.';

                    if (strlen($dates[0]) > 3) {
                        $startdate .= $month_numbers[$j-1];
                    }
                    else {
                        $startdate .= $month_numbers[$j];
                    }
                    preg_match('/2[0-9]{3}/', $dates[1], $year);
                    break;
                }
            }

            //event duration in days
            // TODO calculate diff should be function

            $start = (strlen ($startdate) === 1) ? '0'.$startdate : $startdate;
            $end = (strlen ($startdate) === 1) ? '0'.$enddate : $enddate;
            $start_unix = strtotime($start.$year[0]);
            $end_unix = strtotime($end.$year[0]);
            $diff = (int)($end_unix - $start_unix) / (int)(24*60*60);

            //create a new table row
            $tbody .= '<tr>'
                . '<td><a href="' . $data[$i]['login_url'] . '">'
                    . str_replace('\\' ,'', $data[$i]['event_name'])
                . '</a></td>'
                . '<td>' . $data[$i]['event_type'] . '</td>'
                . '<td>' . $data[$i]['place'] . '</td>'
            //    . '<td>' . $data[$i]['app_deadline'] . '</td>'
                . '<td>' . $data[$i]['fee'] . '</td>'
                . '<td>' . $diff . ' dní</td>'
                . '<td>' . $startdate . $year[0]
                    . ' - '
                . $enddate . $year[0] . '</td>'
                . '<td>' . ($first_char === 'N' ? 'N/A' : $first_char) . '</td>'
            . '</tr>';
        }
        //echo the content of tbody
    }

    if ($data) :
    ?>
    <h2><?php echo __( 'BEST learning events', PLUGIN_NAME ) ?></h2>
    <table class="best-events js-best-events" data-sortable>
        <thead>
            <tr>
                <th id="name" width="10%">
                    <div>
                        <span><?php echo __( 'Name', PLUGIN_NAME ) ?></span>
                    </div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="type" width="15%">
                    <div><span><?php echo __( 'Type', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="place" width="10%">
                    <div><span><?php echo __( 'Place', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <?php
                /*
                    <th id="app_deadline" width="15%">
                        <div><span>Deadline prihlásenia</span></div>
                        <div class="fa fa-fw fa-sort"></div>
                        <div class="fa fa-caret-down"></div>
                        <div class="fa fa-caret-up"></div>
                    </th>
                */
                ?>
                <th id="fee" width="10%" data-sortable-type="numeric">
                    <div><span><?php echo __( 'Fee', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="duration" width="10%" data-sortable-type="numeric">
                    <div><span><?php echo __( 'Duration', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="dates" width="10%" data-sortable-type="interval">
                    <div><span><?php echo __( 'From - Till', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="acad_complaxity" width="10%">
                    <div><span><?php echo __( 'Complexity', PLUGIN_NAME ) ?></span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
            </tr>
        </thead>
        <tbody>
                <?php echo $tbody; ?>
        </tbody>
        <tfoot>
        <tr>
            <?php // TODO TRANSLATE ?>
            <th colspan="9">*Úroveň kurzu označuje mieru špecializácie daného kurzu na uvedenú tému.
                <br> Rozoznávajú sa úrovne základná (Basic, B) čo je všeobecný úvod k danej téme (viac
                interdisciplinárne) a pokročilá (Advanced, A) ktorá poskytuje užšie a hlbšie zameranie na danú tému.
                <br> N/A (not available) znamená, že úroveň nie je uvedená.
            </th>
        </tr>
        <?php
        // Displays the date of a last data refresh
        // TODO: check if it looks visually correct: a smaller text without a grey background could be less aggressive
        $dao = DAO_Events::instance()->get_time_of_most_recent_refresh();
        if ( $dao ):
            ?>
            <tr>
                <th colspan="9">
                    <?php
                        echo __( 'Last synchronisation', PLUGIN_NAME );
                        echo $dao;
                    ?>
                </th>
            </tr>
            <?php
        endif
        ?>
        </tfoot>
    </table>
<?php
else :
echo  __( 'More about courses you can find on', PLUGIN_NAME );
?>
<a href="https://best.eu.org/courses/welcome.jsp" target="_blank">https://best.eu.org/</a>
<?php
endif;
