<?php
// echo the shortcode
// tu príde tabuľka kurzov
//
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_best_courses_lbgs()->enqueue_styles();
wp_best_courses_lbgs()->enqueue_scripts();

// TODO add translation string
?>

<?php
    global $wpdb;

    $data = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'best_events', ARRAY_A);

    $num_rows = $wpdb->num_rows;
    if ($data){
        $months_short = array('Jan', 'F', 'Mar', 'Ap', 'May', 'Jun',
                              'Jul', 'Au', 'S', 'O', 'N', 'D');
        $month_numbers = array('1.','2.','3.','4.','5.','6.',
                               '7.','8.','9.','10.','11.','12.');
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

                    if (strlen($dates[0]) > 2)
                        $startdate .= $month_numbers[$j-1];
                    else
                        $startdate .= $month_numbers[$j];
                    preg_match('/2[0-9]{3}/', $dates[1], $year);
                    break;
                }
            }
            //event duration in days
            $date1 = new DateTime(
                preg_replace('/([0-9]+)-([0-9]+)\.([0-9]+)/',
                             '$1-$3-$2', $year[0] . '-' . $startdate));
            $date2 = new DateTime(
                preg_replace('/([0-9]+)-([0-9]+)\.([0-9]+)/',
                             '$1-$3-$2', $year[0] . '-' . $enddate));
            $diff = $date1->diff($date2);

            //create a new table row
            $tbody .= '<tr>'
                . '<td><a href="' . $data[$i]['login_url'] . '">'
                    . str_replace('\\' ,'', $data[$i]['event_name'])
                . '</a></td>'
                . '<td>' . $data[$i]['event_type'] . '</td>'
                . '<td>' . $data[$i]['place'] . '</td>'
            //    . '<td>' . $data[$i]['app_deadline'] . '</td>'
                . '<td>' . $data[$i]['fee'] . '</td>'
                . '<td>' . $diff->days . ' dní</td>'
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
    <h2>BEST learning events</h2>
    <p>Pre zobrazenie stránky prihlásenia na daný kurz klikni na názov kurzu.</p>
    <table class="best-events js-best-events" data-sortable>
        <thead>
            <tr>
                <th id="name" width="10%">
                    <div><span>Názov</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="type" width="15%">
                    <div><span>Zameranie</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="place" width="10%">
                    <div><span>Miesto</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <?php /*
                <th id="state" width="10%">
                    <div><span>Štát</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="app_deadline" width="15%">
                    <div><span>Deadline prihlásenia</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                */
                ?>
                <th id="fee" width="10%" data-sortable-type="numeric">
                    <div><span>Cena</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="duration" width="10%" data-sortable-type="numeric">
                    <div><span>Trvanie</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="dates" width="10%" data-sortable-type="interval">
                    <div><span>Od-do</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th id="acad_complaxity" width="10%">
                    <div><span>Úroveň*</span></div>
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
                <th colspan="9">*Úroveň kurzu označuje mieru špecializácie daného kurzu na uvedenú tému.
                    <br> Rozoznávajú sa úrovne základná (Basic, B) čo je všeobecný úvod k danej téme (viac interdisciplinárne) a pokročilá (Advanced, A) ktorá poskytuje užšie a hlbšie zameranie na danú tému.
                    <br> N/A (not available) znamená, že úroveň nie je uvedená.</th>
            </tr>
        </tfoot>
    </table>
<?php
else :
 ?>

Viac o kurzoch sa dozvieš na <a href="https://best.eu.org/courses/welcome.jsp">https://best.eu.org/</a>

<?php
endif;
 ?>
