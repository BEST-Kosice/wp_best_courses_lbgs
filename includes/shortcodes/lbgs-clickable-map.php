<?php
// echo the shortcode
if (!defined('ABSPATH')) {
    exit;
}
wp_best_courses_lbgs()->enqueue_styles();
wp_best_courses_lbgs()->enqueue_scripts();

//this is the shortcode for displaying an interactive svg map of all lbgs (currently no database involvement)
//to test, just pust [lbgs_clickable_map] into a page or article.
?>

<a id="assets_dir_url" <? echo "href=" . esc_url(wp_best_courses_lbgs()->assets_url) . 'images/' ?> style="display:none;"></a>
<div id="svg_map" style="min-height:600px; min-width:600px;;"></div>