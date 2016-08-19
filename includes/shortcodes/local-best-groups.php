<?php

use best\kosice\best_courses_lbgs\Best_Courses_LBGS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

Best_Courses_LBGS::instance()->enqueue_styles();
Best_Courses_LBGS::instance()->enqueue_scripts();

// TODO tu príde zoznam lbg
