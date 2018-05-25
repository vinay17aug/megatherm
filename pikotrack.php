<?php
class Crawl {

  protected $regex_link;
  protected $website_url;
  protected $website_url_base;
  protected $urls_processed;
  protected $urls_external;
  protected $urls_not_processed;
  protected $urls_ignored;

  public function __construct($website_url = NULL) {

    // enable error tracking, grr.
    ini_set('track_errors', true);

    // setup variables
    $this->regex_link = "/<\s*a\s+[^>]*href\s*=\s*[\"']?([^\"' >]+)[\"' >]/isU";
    $this->urls_processed = array();
    $this->urls_external = array();
    $this->urls_not_processed = array();
    $this->urls_ignored = array(
      '/search/apachesolr_search/',
      '/comment/reply/',
    );

    // validate argument(s)
    $result = $this->validate_arg_website_url($website_url);

    // error check
    if (!$result) {
      return FALSE;
    }

    // set website argument
    $this->website_url = $website_url;

    // get url base
    $url_base = $this->get_url_base($this->website_url);

    // error check
    if (!$url_base) {
      return FALSE;
    }

    // set website url base
    $this->website_url_base = $url_base;

    // add url to list of urls to process
    $this->urls_not_processed[] = $this->website_url;

    while(count($this->urls_not_processed)) {
      $this->process_urls_not_processed();
    }

    // sort data
    sort($this->urls_processed);
    sort($this->urls_external);

  }

  protected function validate_arg_website_url($website_url = NULL) {

    // validate argument
    if (!(is_string($website_url) && (substr($website_url,0,7)=='http://' || substr($website_url,0,8)=='https://'))) {
      return FALSE;
    }

    return TRUE;

  }

  protected function get_url_base($url = NULL) {

    // validate url
    if (!$url || !strlen($url)) {
      return FALSE;
    }

    $url_parts = parse_url($url);

    // validate
    if (!is_array($url_parts)) {
      return FALSE;
    }

    // explode host on '.'
    $exploded = explode('.', $url_parts['host']);

    // return host and domain extension
    $url_base = $exploded[count($exploded)-2] . '.' . $exploded[count($exploded)-1];


    return $url_base;

  }

  protected function scan_url($url) {

    // validate url
    if (!is_string($url) || !$url || !strlen($url)) {
      return FALSE;
    }

    // ensure url has not already been processed
    if (in_array($url, $this->urls_processed)) {
      return FALSE;
    }

    // add url to processed list
    $this->urls_processed[] = $url;

    // remove any previously saved errors
    unset($php_errormsg);

    // load page contents
    $page_contents = file_get_contents($url);

    // check for error when loading url; text starting with "file_get_contents"
    $error_text = 'file_get_contents';
    if (isset($php_errormsg) && substr($php_errormsg,0,strlen($error_text))==$error_text) {
      return FALSE;
    }

    // check for additional errors
    elseif ($page_contents === false || !strlen($page_contents)) {
      return FALSE;
    }

    // execute regex
    preg_match_all($this->regex_link, $page_contents, $matches);

    if (is_array($matches) && isset($matches[1])) {
      return array_unique($matches[1]);
    }

    return FALSE;

  }

  protected function process_matches($matches = NULL) {

    // validate
    if (!$matches || !is_array($matches) || empty($matches)) {
      return FALSE;
    }

    foreach ($matches as $match) {

      // ensure match exists
      if (empty($match)) {
        continue;
      }
      // ignore anchors
      elseif (substr($match,0,1)=='#') {
        continue;
      }
      // ignore javascript
      elseif (substr($match,0,11)=='javascript:') {
        continue;
      }
      // ignore mailto
      elseif (substr($match,0,7)=='mailto:') {
        continue;
      }

      // check for internal urls that begin with '/'
      if (substr($match,0,1)=='/') {
        $match = 'http://' . $this->website_url_base . $match;
      }

      // remove trailing slash
      if (substr($match, -1)=='/') {
        $match = substr($match, 0, -1);
      }

      // ensure href starts with http or https
      // NOTE: this needs work, URL could begin with relative paths like '../', ftp://, etc.
      if (!(substr($match,0,7)=='http://' || substr($match,0,8)=='https://')) {
        $match = 'http://' . $this->website_url_base . '/' . $match;
      }

      // check if url is to be ignored
      foreach ($this->urls_ignored as $ignored) {
        if (stripos($match, $ignored) !== FALSE) {
          continue 2;
        }
      }

      // get url base
      $url_base = $this->get_url_base($match);

      // check for external url
      if ($url_base != $this->website_url_base) {

        if (!in_array($match, $this->urls_external)) {
          $this->urls_external[] = $match;
        }
        continue;

      }

      // check if url has already been processed
      if (in_array($match, $this->urls_processed)) {
        continue;
      }

      // add url to list of urls to process
      if (!in_array($match, $this->urls_not_processed)) {
        $this->urls_not_processed[] = $match;
      }

    // end: foreach
    }

    return TRUE;

  }

  protected function process_urls_not_processed() {

    if (empty($this->urls_not_processed)) {
      return FALSE;
    }

    // get unprocessed url
    $url = array_shift($this->urls_not_processed);

    // scan url
    $matches = $this->scan_url($url);

    // error check
    if (!$matches || !is_array($matches) || empty($matches)) {
      return FALSE;
    }

    $this->process_matches($matches);

  }

  public function output_all_urls() {

    echo "===== INTERNAL URLS =====\n";
    foreach ($this->urls_processed as $url) {
      print $url . "\n";
    }

    echo "===== EXTERNAL URLS =====\n";
    foreach ($this->urls_external as $url) {
      print $url . "\n";
    }

  }

}
?>
<?php
$website_url = 'https://www.jonhywee.com/';
$crawl = new Crawl($website_url);
$crawl->output_all_urls();
?>