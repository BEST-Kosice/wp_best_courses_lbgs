<?php
/**
 * return parsed data for courses and list of Local BEST groups
 * ->courses
 * ->lbgs
 * on error return false
 * ->error_id  1 - can't download file
 * 			   2 - changed structure of file.
 **/

// basic usage example

// use best\kosice\datalib as BST_data;
//
//
// $BEST_data = new BST_data\best_kosice_data();
//
// echo '<pre>';
// $parsed = $BEST_data->season_courses();
// var_dump($parsed);
//



/*
  TODO refactor to static class? (note: it is singleton to allow access to last error, pure static class would not allow it)
  php parser for courses  data
  NOTE Add special call to google api for attitude and longtitude save tod db
  NOTE make wp api endpoint for lbgs and courses
*/

namespace best\kosice\datalib;

use Sunra\PhpSimple\HtmlDomParser;

// security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// load lib for parsing
//require 'vendor/simple_html_dom.php';

// TODO: rename using convention: Large first characters, no explicit namespace repetition; rename file using wp convention
class best_kosice_data
{
    private $courses_url = 'https://best.eu.org/courses/list.jsp';
    private $lbgs_url = 'https://best.eu.org/aboutBEST/structure/lbgList.jsp';
    private $season_events_url = 'https://best.eu.org/student/courses/coursesList.jsp';
    private $parsed_link_prefix = 'https://best.eu.org';
    private $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
    private $timeout = 5;

    public $connection_info = false;
    public $error_id = false;

    /**
     * The single instance of best_kosice_data.
     * @var best_kosice_data
     */
    private static $_instance;

    /**
     * Constructor function with private access, singleton class.
     */
    private function __construct()
    {
    }

    /**
     * Return the instance of the Parser.
     *
     * @return best_kosice_data
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Return list of courses and their details.
     *
     * @return array|false courses, false on error
     */
    public function courses()
    {
        $data = $this->download_doc($this->courses_url);

        // can't download
        if (!$data | $this->connection_info['http_code'] != 200) {
            $this->error_id = 1;

            return false;
        }

        $data = $data ? $this->prepare_data($data) : false;

        // changed structure not document write
        if (!$data) {
            $this->error_id = 2;

            return false;
        }

        $data = is_string($data) ? $this->parse_courses($data) : false;

        // can't parse changed structure
        if (!$data) {
            $this->errorID = 2;

            return false;
        }

        return $data;
    }

    /**
     * Return list of Local BEST Groups.
     *
     * @return array|false local best groups, false on error
     */
    public function lbgs()
    {
        $data = $this->download_doc($this->lbgs_url);

        // can't download
        if (!$data | $this->connection_info['http_code'] != 200) {
            $this->error_id = 1;

            return false;
        }

        $data = $data ? $this->prepare_data($data) : false;

        // changed structure
        if (!$data) {
		    $this->error_id = 2;

            return false;
        }

        $data = is_string($data) ? $this->parse_lbgs($data) : false;
        //$data = (is_array($data[1]) && $data[1]) ? $this->parse_lbgs($data[1]) : false;

        if (!$data) {
            $this->error_id = 2;

            return false;
        }

        return $data;
    }

    // TODO
    // NOTE NOT DONE
    /**
     * [season_dates description].
     *
     * @return [type] [description]
     */
    public function season_dates()
    {
        $data = $this->download_doc($this->season_events_url);
        //contentBar
        $data = $this->parse_dates($data);
    }

    /**
     * Returns message describing an error of the last operation.
     *
     * @return string|false last error message, false if there was no error
     */
    public function error_message()
    {
        switch ($this->error_id) {
            case 1:
                return 'Unable to download document.';
            case 2:
                return 'Unable to parse changed document structure.';
            default:
                return false;
        }
    }

    /**
     * Download document from URL.
     *
     * @param string $url
     *
     * @return string|false downloaded document, false on error
     */
    private function download_doc($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            $data = false;
        } else {
            $this->connection_info = curl_getinfo($ch);
        }

        curl_close($ch);

        // convert from ISO-8859-1 to UTF8
        $data = utf8_encode($data);

        return $data;
    }

    /**
     * Prepare html data for parsing.
     *
     * @param string $raw raw html doc with js html comments
     *
     * @return array|false array that matches
     */
    private function prepare_data($raw)
    {
        preg_match("/<article\s[^>]*([^\" >]*?)\\1[^>]*>(.*)<\/article>/siU", $raw, $matches);
        return $matches[0];
    }

    /**
     * Parse courses from HTML.
     *
     * @param string $html [description]
     *
     * @return array|false [description]
     */
    private function parse_courses($html)
    {
        $html = HtmlDomParser::str_get_html($html);

        $returnData = false;

        // leisure events
        $learning_events_table = $html->find('table',0);

        $learning_events = $this->parse_table($learning_events_table);

        if ($learning_events) {
            $returnData['learning']['data'] = $learning_events;
        }

        $leisure_events_table = $html->find('table',1);

        $leisure_events = $this->parse_table($leisure_events_table);

        if ($leisure_events) {
            $returnData['leisure']['data'] = $leisure_events;
        }

        return $returnData;
    }

    /**
     * parse lbgs link and name.
     *
     * @param [type] $html [description]
     *
     * @return [type] [description]
     */
    private function parse_lbgs($html)
    {
        $theData = array();
        
		$html = HtmlDomParser::str_get_html($html);
        
		foreach($html->find("#map .city-description section") as $lbg){
            //get homepage URL of LBG from link inside heading
			$name = preg_replace( '/\s+Local\s+Group\s+/', '', $lbg->find("h4 > a")[0]->innertext() );
			if (preg_match('/\s+Local\s+Group\s+/', $name) == 1)
				$name = preg_replace( '/\s+Local\s+Group\s+/', '', $name );
			else
				$name = preg_replace( '/\s+Observer\s+Group\s+/', '', $name );
			//get LBG code from <img> src attribute	
		    $code = $lbg->find("img");
			if ($code)
				$code = substr($code[0]->src, -2);
			//if there is no image next to an LBG item, we will guess it is an observer group
			else {
				$observers = $html->find("#list section.lbg-list", 1);
				foreach ($observers->find("a.lbg-link") as $observer){
				    if (stripos($observer->children[0]->innertext, $name) !== FALSE)
						$code = substr($observer->href, -2);
				}
			}
			//get homepage URL
		    $url = $lbg->find("dd a[href^=http]");
            if (!$url)
                $url = $this->parsed_link_prefix . '/aboutBEST/structure/lbgView.jsp?lbginfo=' . $code;
            else
                $url = $url[0]->href;
            array_push( $theData, array($url, $code, $name) );
        }
        return $theData;
		
		//old parser
        /*foreach ($html as $key => $value) {
            $rowData = false;
            if (preg_match("/<option\s[^>]*value=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/option>/siU", $value, $matches)) {
                if ($matches[2] != '') {
                    $rowData = array();
                    $rowData[] = $matches[2];
                    // parsing state
                    preg_match('#\((.*?)\)#', $matches[3], $state);
                    $rowData[] = $state[1];
                    $rowData[] = trim(substr($matches[3], 0, strpos($matches[3], '(')));
                }
            }

            if ($rowData) {
                $theData[] = $rowData;
            }
        }

        return $theData;*/
    }

    private function parse_table($table) {


        $theData = array();

        foreach ($table->find('tr') as $row) {
            $rowData = $row->find('td') ? array() : false;

            foreach ($row->find('td') as $cell) {
                if (substr_count($cell->innertext, 'src') > 0) {
                    foreach ($cell->find('img') as $element) {
                        $rowData[] = trim($element->src);
                    }
                } elseif (substr_count($cell->innertext, 'href') > 0) {
                    foreach ($cell->find('a') as $element) {
                        //title
                            $rowData[] = addslashes(html_entity_decode(trim($element->innertext)));

                            //link
                            $rowData[] = addslashes(html_entity_decode(trim($this->parsed_link_prefix.$element->href)));
                    }
                } else {
                    $rowData[] = addslashes(html_entity_decode(strip_tags(trim($cell->innertext))));
                }
            }

            if ($rowData) {
                $theData[] = $rowData;
            }
        }


        return $theData;





    }

    private function parse_dates($html)
    {
        $theData = false;
        $html = HtmlDomParser::str_get_html($html);

        foreach ($html->find('#contentBar') as $element) {

            //var_dump($element->nodes);

            //

            //
            // while ($element->hasChildNodes()){
            //
            //     var_dump(un$element->firstChild());
            //     break;
            //  }

            $parentNode = $element->innertext();

                // $regex = '/<[^>]*>[^<]*<[^>]*>/';
           // preg_replace($regex, '', $element->innertext);
        }
    }
// end of class
}