<?php

use best\kosice\best_courses_lbgs\BEST_Courses_LBGS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BEST_Courses_LBGS::instance()->enqueue_styles();
BEST_Courses_LBGS::instance()->enqueue_scripts();

// TODO tu príde zoznam lbg
