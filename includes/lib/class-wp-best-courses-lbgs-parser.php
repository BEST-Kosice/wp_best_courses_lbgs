<?php
/**
 * return parsed data for courses and list of Local BEST groups
 * ->courses
 * ->lbgs
 * on error return false
 * ->error_id  1 - can't download file
 * 			   2 - changed structure of file
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
  TODO refactor to static class
  php parser for courses  data
  NOTE Add special call to google api for attitude and longtitude save tod db
  NOTE make wp api endpoint for lbgs and courses
*/

namespace best\kosice\datalib;


// security
if ( ! defined( 'ABSPATH' ) ) exit;

// load lib for parsing
require 'vendor/simple_html_dom.php';





class best_kosice_data
{
    private $coursesurl         = 'https://best.eu.org/localWeb/eventListJS.jsp';
    private $lbgsurl            = 'https://best.eu.org/localWeb/lbgChooser.jsp';
    private $season_events_url  = 'https://best.eu.org/student/courses/coursesList.jsp';
    private $parsed_link_prefix = 'https://best.eu.org';
    private $userAgent          = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
    private $timeout            = 5;

    public $conection_info      = false;
    public $error_id            = false;

    private static $_instance;

    public function __construct(){
    }

    //TODO convert singleton to static class
    public static function instance () {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * return list of courses and details.
     *
     * @return array false on error
     */
    public function courses()
    {
        //$raw = file_get_contents($this->coursesurl);
        $data = $this->download_doc($this->coursesurl);

        // cant donwload
        if (!$data | $this->conection_info['http_code']!=200 ) {
            $this->error_id = 1;
            return false;
        }

        $data = $data ? $this->prepare_data($data) : false;

        // changed structure not document write
        if (!$data) {
            $this->error_id = 2;
            return false;
        }

        $data = (is_string($data[1][0]) && $data[1][0]) ? $this->parse_courses($data[1][0]) : false;

        // can't parse changed structure
        if (!$data) {
            $this->errorID = 2;
            return false;
        }

        return $data;
    }

    /**
     * return list of Local BEST Groups.
     *
     * @return array
     */
    public function lbgs()
    {
        $data = $this->download_doc($this->lbgsurl);

        // cant donwload
        if (!$data | $this->conection_info['http_code']!=200) {
            $this->error_id = 1;
            return false;
        }

        $data = $data ? $this->prepare_data($data) : false;

        // changed structure
        if (!$data) {
            $this->error_id = 2;
            return false;
        }

        $data = (is_array($data[1]) && $data[1]) ? $this->parse_lbgs($data[1]) : false;

        if (!$data) {
            $this->error_id = 2;
            return false;
        }

        return $data;
    }

    // TODO
    // NOTE NOT DONE
    /**
     * [season_dates description]
     * @return [type] [description]
     */
    public function season_dates()
    {

        $data = $this->download_doc($this->season_events_url);

        //contentBar

        $data = $this->parse_dates($data);


    }

    /**
     * @return string error message
     */
    public function error_message()
    {
        switch ($this->error_id) {
            case 1:
                return 'Unable to download document.';
                break;
            case 2:
                return 'Unable to parse changed document structure.';
                break;

        }
    }

    /**
     * Download document from url.
     *
     * @param string $url
     *
     * @return string or false
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

        if ( curl_errno($ch) )
            $data = false;
        else
            $this->conection_info = curl_getinfo($ch);

        curl_close($ch);

        // convert from ISO-8859-1  to uft8
        $data  = utf8_decode($data);
        return $data;
    }

    /**
     * Prepare html data for parsing.
     *
     * @param string $raw raw html doc with js html comments
     *
     * @return array or false      array that matches
     */
    private function prepare_data($raw)
    {
        // removing js  coments
        $raw = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\)\/\/[^"\'].*))/', '', $raw);

        // getting document write content
        $data = preg_match_all('/document.write\(\'(.*)\'\);/', $raw, $matches) ? $matches : false;

        return $data;
    }

    /**
     * parse courses from html.
     *
     * @param string $html [description]
     *
     * @return array or false       [description]
     */
    private function parse_courses($html)
    {
        $html = str_get_html($html);

        $theData = array();

        foreach ($html->find('table') as $onetable) {
            foreach ($onetable->find('tr') as $row) {

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

                if ($rowData)
                    $theData[] = $rowData;

            }
        }

        return $theData;
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
        $theData = false;
        foreach ($html as $key => $value) {
            $rowData = false;
            if (preg_match("/<option\s[^>]*value=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/option>/siU", $value, $matches)) {
                if ($matches[2] != '') {
                    $rowData = array();
                    $rowData[] = $matches[2];
                    $rowData[] = $matches[3];
                }
            }

            if ($rowData)
                $theData[] = $rowData;

        }

        return $theData;
    }

    private function parse_dates($html){
        $theData = false;
        $html = str_get_html($html);
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
