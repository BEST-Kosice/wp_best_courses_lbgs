<?php
// echo the shortcode
// tu príde tabuľka kurzov
//
if (!defined('ABSPATH')) {
    exit;
}

wp_best_courses_lbgs()->enqueue_styles();
wp_best_courses_lbgs()->enqueue_scripts();

// TODO add translation string
// TODO podmienka ak niesú žiadne kurzy nezobraz žiadne
?>
    <p>Pre zobrazenie stránky prihlásenia na daný kurz klikni na názov kurzu.</p>
    <table id="courses_table" data-sortable>
        <thead>
            <tr>
                <th width="10%">
                    <div><span>Názov</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="15%">
                    <div><span>Zameranie</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Mesto</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Štát</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="15%">
                    <div><span>Deadline prihlásenia</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Cena</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Trvanie</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Od-do</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
                <th width="10%">
                    <div><span>Úroveň*</span></div>
                    <div class="fa fa-fw fa-sort"></div>
                    <div class="fa fa-caret-down"></div>
                    <div class="fa fa-caret-up"></div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            // TODO SPRAVIŤ wp QUERY LOOP PRE VÝPIS TABUĽKY
             ?>
            <tr>
                <td><a href="http://www.best.tuke.sk/wic15ke/" target="_blank">Android</a></td>
                <td>Technology</td>
                <td>Košice</td>
                <td>Slovensko</td>
                <td>1. december 2014</td>
                <td>32 €</td>
                <td>5 dní</td>
                <td>12.12.2014 - 17.12.2014</td>
                <td>B</td>
            </tr>
            <tr>
                <td><a href="http://besttrondheim.no/ac15/" target="_blank">Solar fun</a></td>
                <td>Applied engineering</td>
                <td>Budapešť</td>
                <td>Maďarsko</td>
                <td>9. január 2015</td>
                <td>48 €</td>
                <td>7 dní</td>
                <td>20.1.2015 - 27.1.2015</td>
                <td>A</td>
            </tr>
            <tr>
                <td><a href="https://www.best.eu.org/student/courses/event.jsp?activity=e6s71vw" target="_blank">Manager for hire</a></td>
                <td>Career related skills</td>
                <td>Talinn</td>
                <td>Estónsko</td>
                <td>10. február 2015</td>
                <td>36 €</td>
                <td>8 dní</td>
                <td>20.2.2015 - 28.2.2015</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td><a href="http://www.xilinx.com/training/fpga/fpga-field-programmable-gate-array.htm" target="_blank">FPGA</a></td>
                <td>Technology</td>
                <td>Bratislava</td>
                <td>Slovensko</td>
                <td>20. marec 2015</td>
                <td>30 €</td>
                <td>5 dní</td>
                <td>3.4.2015 - 8.4.2015</td>
                <td>B</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9">*Úroveň kurzu označuje mieru špecializácie daného kurzu na uvedenú tému.
                    <br> Rozoznávajú sa úrovne základná (Basic, B) čo je všeobecný úvod k danej téme (viac interdisciplinárne) a pokročilá (Advanced, A) ktorá poskytuje užšie a hlbšie zameranie na danú tému.
                    <br> N/A (not available) znamená, že úroveň nie je uvedená.</th>
            </tr>
        </tfoot>
    </table>
