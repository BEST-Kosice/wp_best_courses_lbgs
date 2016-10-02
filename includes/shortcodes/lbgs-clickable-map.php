<?php

use best\kosice\best_courses_lbgs\BEST_Courses_LBGS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BEST_Courses_LBGS::instance()->enqueue_styles();
BEST_Courses_LBGS::instance()->enqueue_scripts();

// this is the shortcode for displaying an interactive svg map of all lbgs.
// to test, just pust [best_lbgs_map] into a page or article.
?>

<div id="lbg_map" style="min-height:600px; min-width:600px;;">
    <?php
        /* Just some code to test the parser during development without
         * having to fill up the updates history table needlessly.
         */
		/*use best\kosice\datalib\best_kosice_data;
		use Sunra\PhpSimple\HtmlDomParser;

		$parser = best_kosice_data::instance();
		$lbgs = $parser->lbgs();
		$html = HtmlDomParser::str_get_html($lbgs);

		$lbg = ""; $url = ""; $arr = array();
		foreach($html->find("#map .city-description section") as $lbg){
			$name = preg_replace( '/\s+Local Group\s+/', '', $lbg->find("h4 > a")[0]->innertext() );
			$url = $lbg->find("dd a[href*=http]");
			if (!$url)
				$url = null;
			else
				$url = $url[0];
			array_push( $arr, array($url, "na", $name) );
			//echo $name . " " . $url . "<br>";
		}
		return $arr;

		foreach($arr as $item){
			echo $item[2] . " " . $item[0] . " " . $item[1] . "<br>";
		}*/

		// this is the short version, just echo SVG file contents into generated HTML code, let JS handle the rest
		//echo file_get_contents(esc_url( Best_Courses_LBGS::instance()->assets_url ) . 'images/map-optimized.svg');


		/* This is the long version, where we transform the SVG content to a simpleXML object
		 * and apply some parsing to somewhat unburden the client side (such as adding homepage URLs).
		 * Unfortunately, due to parser bugs, the required database table is currently empty,
		 * so we can´t add those URLs now (but once we can, it would probably be easier than using AJAX).
		 * Currently, the only parsing available (for demonstration purposes) involves translating country codes
		 * and removing some document nodes (specifically, removing all SVG elements representing former LBGs).
		 */
		global $wpdb;
		$data = $wpdb->get_results('SELECT lbg_id, sk.name, web_page FROM '
								   .$wpdb->prefix.'best_lbg lbg INNER JOIN '.$wpdb->prefix.'best_lbg_sk sk
								   ON sk.lbg_id = lbg.state ORDER BY lbg_id DESC', ARRAY_A);

		$svg = simplexml_load_file( __DIR__ .'/../../assets/images/map-optimized.svg');

		// register the default namespace
		$svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
		// required for the xlink:href=" ... attribute, if we were to use it later
		$svg->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

		// delete SVG group of former BEST LBGs
		$res = $svg->xpath('./svg:g[@id="LBGs"]/svg:g[@id="former"]');
	    $dom = dom_import_simplexml($res[0]);
		$dom->parentNode->removeChild($dom);

		// legacy code - translate country codes in the id attributes into Slovak
		/*$res = $svg->xpath('./svg:g[@id="LBGs"]/svg:g/svg:path');
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
			$idx = substr( $lbg->attributes()["id"], -2 );
			$lbg->attributes()["id"] = substr_replace(
				$lbg->attributes()["id"],
				', ' . $countryNames[ $idx ],
				-3
			);
			foreach ($data as $item){
				if (strpos($lbg->attributes()["id"], $item["city"]) === 0)
					$lbg->attributes()["id"] .= ": " . $item["web_page"];
			}
		}*/

	    // create links and change the id of all svg:path objects (dots) to what will be displayed in the decription boxes
	    function add_link($group, $lbg_db_table, $id_addition){
			$group->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
	    	$lbgs = $group->xpath('./svg:path');
	    	$dom = dom_import_simplexml($group);
			foreach ($lbgs as $lbg){
				$link = new DOMElement("a");
				$dom->appendChild( $link )->appendChild(dom_import_simplexml($lbg));
				foreach ($lbg_db_table as $item){
					// if (stripos($lbg->attributes()["id"], $item["lbg_id"]) === 0){
					// 	$link->setAttribute("xlink:href", $item["web_page"]);
					// 	$link->setAttribute("target", '_blank');
					// 	$lbg->attributes()["id"] = $item["name"] . $id_addition;
					// 	break;
					// }
				}
			}
	    }

	//	add_link($svg->xpath('./svg:g[@id="LBGs"]/svg:g[@id="current"]')[0], $data, "");
	//    add_link($svg->xpath('./svg:g[@id="LBGs"]/svg:g[@id="observer"]')[0], $data, " (pozorovateľská skupina)");

		// echo the SVG code
		echo $svg->asXML();

    ?>
</div>
