<?php

use best\kosice\best_courses_lbgs\best_courses_lbgs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

best_courses_lbgs::instance()->enqueue_styles();
best_courses_lbgs::instance()->enqueue_scripts();

// TODO tu príde zoznam lbg 
?>
