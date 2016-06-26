<?php
// echo the shortcode
// tu príde tabuľka kurzov
//
if (!defined('ABSPATH')) {
    exit;
}
wp_best_courses_lbgs()->enqueue_styles();
wp_best_courses_lbgs()->enqueue_scripts();
?>

<table id="courses_table">
    <colgroup>
        <col span="4" style="width:135px">
        <col span="3" style="width:70px">
    </colgroup>
    <thead>
        <tr>
            <th>
                Nazov kurzu
                <a href="#">
                    <span ng-click="sortType = 'meno'; sortReverse = false" ng-show="sortType != 'meno'" class="fa fa-sort ng-hide"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'meno' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'meno' &amp;&amp; sortReverse == false" class="fa fa-caret-down"></span>
                </a>

            </th>
            <th>
                Miesto konania
                <a href="#">
                    <span ng-click="sortType = 'miesto'; sortReverse = false" ng-show="sortType != 'miesto'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'miesto' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'miesto' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>

            </th>
            <th>
                Zameranie kurzu
                <a href="#">
                    <span ng-click="sortType = 'zameranie'; sortReverse = false" ng-show="sortType != 'zameranie'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'zameranie' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'zameranie' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>

            </th>
            <th>
                Deadline podania prihlášky
                <a href="#">
                    <span ng-click="sortType = 'deadline'; sortReverse = false" ng-show="sortType != 'deadline'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'deadline' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'deadline' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>

            </th>
            <th>
                Cena
                <a href="#">
                    <span ng-click="sortType = 'cena'; sortReverse = false" ng-show="sortType != 'cena'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'cena' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'cena' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>

            </th>
            <th>
                Od-do
                <a href="#">
                    <span ng-click="sortType = 'trvanie.startdate'; sortReverse = false" ng-show="sortType != 'trvanie.startdate'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'trvanie.startdate' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'trvanie.startdate' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>

            </th>
            <th>
                Hĺbka**
                <a href="#">
                    <span ng-click="sortType = 'hlbka'; sortReverse = false" ng-show="sortType != 'hlbka'" class="fa fa-sort"></span>
                </a>
                <a href="#" ng-click="sortReverse = !sortReverse">
                    <span ng-show="sortType == 'hlbka' &amp;&amp; sortReverse == true" class="fa fa-caret-up ng-hide"></span>
                    <span ng-show="sortType == 'hlbka' &amp;&amp; sortReverse == false" class="fa fa-caret-down ng-hide"></span>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr ng-repeat="course in courses | orderBy:sortType:sortReverse" class="ng-scope">
            <td><a ng-href="http://www.best.tuke.sk/wic15ke/" class="ng-binding" href="http://www.best.tuke.sk/wic15ke/">Android</a></td>
            <td class="ng-binding">Kosice</td>
            <td class="ng-binding">Technology</td>
            <td class="ng-binding">1. december 2014</td>
            <td class="ng-binding">32 €</td>
            <td class="ng-binding">12.12.2014
                <br>-
                <br>17.12.2014</td>
            <td class="ng-binding">B</td>
        </tr>

        <tr ng-repeat="course in courses | orderBy:sortType:sortReverse" class="ng-scope">
            <td><a ng-href="http://www.xilinx.com/training/fpga/fpga-field-programmable-gate-array.htm" class="ng-binding" href="http://www.xilinx.com/training/fpga/fpga-field-programmable-gate-array.htm">FPGA</a></td>
            <td class="ng-binding">Bratislava</td>
            <td class="ng-binding">Technology</td>
            <td class="ng-binding">20. marec 2015</td>
            <td class="ng-binding">30 €</td>
            <td class="ng-binding">3.4.2015
                <br>-
                <br>8.4.2015</td>
            <td class="ng-binding">B</td>
        </tr>

        <tr ng-repeat="course in courses | orderBy:sortType:sortReverse" class="ng-scope">
            <td><a ng-href="https://www.best.eu.org/student/courses/event.jsp?activity=e6s71vw" class="ng-binding" href="https://www.best.eu.org/student/courses/event.jsp?activity=e6s71vw">Manager for hire</a></td>
            <td class="ng-binding">Talinn</td>
            <td class="ng-binding">Career related skills</td>
            <td class="ng-binding">10. február 2015</td>
            <td class="ng-binding">36 €</td>
            <td class="ng-binding">20.2.2015
                <br>-
                <br>25.2.2015</td>
            <td class="ng-binding">N/A</td>
        </tr>
        <tr ng-repeat="course in courses | orderBy:sortType:sortReverse" class="ng-scope">
            <td><a ng-href="http://besttrondheim.no/ac15/" class="ng-binding" href="http://besttrondheim.no/ac15/">Solar fun</a></td>
            <td class="ng-binding">Budapest</td>
            <td class="ng-binding">Applied engineering</td>
            <td class="ng-binding">9. január 2015</td>
            <td class="ng-binding">48 €</td>
            <td class="ng-binding">20.1.2015
                <br>-
                <br>25.1.2015</td>
            <td class="ng-binding">A</td>
        </tr>
    </tbody>
</table>
