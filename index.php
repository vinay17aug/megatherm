<?php
error_reporting(E_ALL);
include_once('simple_html_dom.php');

$input_url = 'https://www.jonhywee.com/';

function link_finder($input_url) {
    // Create DOM from URL or file
    $html = file_get_html($input_url);
    
    $track_pre = array();
    $result = array();
    // Find all links
    foreach($html->find('a') as $element) {
        $sublink = $element->href;
        
        if($sublink) {
            $track_pre[] = $sublink;
            $result[$input_url][] = $sublink;
            
            link_finder($sublink);
        }
    }
    
    return $result;
}

pr(link_finder($input_url));