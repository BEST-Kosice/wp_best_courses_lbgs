<?php

use best\kosice\best_courses_lbgs\BEST_Courses_LBGS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BEST_Courses_LBGS::instance()->enqueue_styles();
BEST_Courses_LBGS::instance()->enqueue_scripts();

global $wpdb;
$data = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'best_lbg', ARRAY_A);


// TODO make it pretty
if ($data) {
?>
<h2>
    <?php echo __( 'List of Local Best Groups', PLUGIN_NAME );?>
    <small><?php echo '('.count($data).')';?></small>
</h2>
<?php foreach ($data as $row):?>
    <a href="<?php echo $row['web_page'];  ?> " target="_blank">
        <?php echo $row['city'] ?>
    </a>
    <br>
<?php endforeach;
}
