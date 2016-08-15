<?php

use best\kosice\best_courses_lbgs\best_courses_lbgs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

best_courses_lbgs::instance()->enqueue_styles();
best_courses_lbgs::instance()->enqueue_scripts();

// this is the shortcode for displaying an interactive svg map of all lbgs (currently no database involvement)
// to test, just pust [best_lbgs_map] into a page or article.
?>

<div id="svg_map" style="min-height:600px; min-width:600px;;">
    <?php 
        // this is the short version, just echo SVG file contents into generated HTML code, let JS handle the rest
        //echo file_get_contents(esc_url( wp_best_courses_lbgs()->assets_url ) . 'images/map-optimized.svg'); 
        
        /* This is the long version, where we transform the SVG content to a simpleXML object 
         * and apply some parsing to somewhat unburden the client side (such as adding homepage URLs).
         * Unfortunately, due to parser bugs, the required database table is currently empty, 
         * so we can´t add those URLs now (but once we can, it would probably be easier than using AJAX).
         * Currently, the only parsing available (for demonstration purposes) involves translating country codes
         * and removing some document nodes (specifically, removing all SVG elements representing former LBGs).
         */
        $svg = simplexml_load_file(esc_url( best_courses_lbgs::instance()->assets_url ) . 'images/map-optimized.svg');
        
        //foreach( $xml->children() as $child)
        //    print_r($child);
   
        // register the default namespace
        $svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
        // required for the xlink:href=" ... attribute, if we were to use it later
        //$svg->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

        // delete SVG group of former BEST LBGs
        $res = $svg->xpath('./svg:g[@id="LBGs"]/svg:g[@id="former"]');
        $dom = dom_import_simplexml($res[0]);
        $dom->parentNode->removeChild($dom);
        
        // translate country codes in the id attributes into Slovak
        $res = $svg->xpath('./svg:g[@id="LBGs"]/svg:g/svg:path');
        $countryNames = array(
            'SE' => 'Švédsko', 'DK' => 'Dánsko', 'NO' => 'Nórsko', 'IS' => 'Island', 'FI' => 'Fínsko', 'RU' => 'Rusko', 
            'BE' => 'Belgicko', 'NL' => 'Holandsko', 'GB' => 'Veľká Británia', 'IE' => 'Írsko', 'FR' => 'Francúzsko', 
            'DE' => 'Nemecko', 'AT' => 'Rakúsko', 'CH' => 'Švajčiarsko', 'LI' => 'Lichtenštajnsko', 'LU' => 'Luxembursko',
            'PL' => 'Poľsko', 'LT' => 'Litva', 'LV' => 'Lotyšsko', 'EE' => 'Estónsko' , 'UA' => 'Ukrajina', 'BY' => 'Bielorusko',
            'HR' => 'Chorvátsko', 'SK' => 'Slovensko', 'CZ' => 'Česká republika', 'HU' => 'Maďarsko', 'SI' => 'Slovinsko', 
            'BA' => 'Bosna a Hercegovina', 'RS' => 'Srbsko', 'ME' => 'Čierna hora', 'MK' => 'Maccedónsko', 'AL' => 'Albánsko',
            'RO' => 'Rumunsko', 'BG' => 'Bulharsko', 'MD' => 'Moldavsko', 'AL' => 'Albánsko', 'GR' => 'Grécko', 'TR' => 'Turecko',
            'ES' => 'Španielsko', 'PT' => 'Portugalsko', 'IT' => 'Taliansko', 'MT' => 'Malta', 'AD' => 'Andora',
            'CY' => 'Cyprus', 'AM' => 'Arménsko', 'AZ' => 'Azerbajdžan', 'GE' => 'Gruzínsko', 'KZ' => 'Kazachstan'
        );
        foreach ($res as $lbg){
            $lbg->attributes()["id"] = substr_replace(
                $lbg->attributes()["id"], 
                ', ' . $countryNames[ substr( $lbg->attributes()["id"], -2 ) ],
                -3
            );
        }
        
        //point out observer groups in the description
        $res = $svg->xpath('./svg:g[@id="LBGs"]/svg:g[@id="observer"]/svg:path');
        foreach ($res as $lbg){
            $lbg->attributes()["id"] = $lbg->attributes()["id"] . " (pozorovateľská skupina)";
        }
    
        //echo the SVG code
        echo $svg->asXML();
    ?>
    

</div>