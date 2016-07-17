<?php
// echo the shortcode
// tu príde tabuľka kurzov
//
if (!defined('ABSPATH')) {
    exit;
}
wp_best_courses_lbgs()->enqueue_styles();
wp_best_courses_lbgs()->enqueue_scripts();

// TODO tu príde zoznam lbg 
?>
