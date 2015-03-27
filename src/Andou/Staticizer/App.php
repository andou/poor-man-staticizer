<?php

namespace Andou\Staticizer;

class App {

  /**
   * An array of task configurations
   *
   * @var array
   */
  protected $_configs;

  /**
   * A provider of shell output
   *
   * @var \Andou\Shelltools\Outputprovider 
   */
  protected $_output_provider = NULL;

  /**
   * Should we be?
   *
   * @var boolean 
   */
  protected $_verbose;

  /**
   * Folder in which to save the generated static HTMLs
   *
   * @var string
   */
  protected $_pool;

  /**
   * An array with all the static files created
   *
   * @var array
   */
  protected $_statics;

  /**
   * Returns an instance of this class
   * 
   * @return \Andou\Staticizer\App
   */
  public static function getInstance() {
    $classname = __CLASS__;
    return new $classname();
  }

  /**
   * Initialize the application
   * 
   * @param string $config_file
   * @param boolean $verbose
   * @return boolean|\Andou\Staticizer\App
   */
  public function init($config_file, $verbose = TRUE) {
    $this->_verbose = $verbose;
    if (!file_exists($config_file)) {
      $this->_echo("Config File does not exists");
      return FALSE;
    } else {
      $this->_echo("Config File ok");
    }
    $this->readConfigFile($config_file);

    return $this;
  }

  /**
   * Reads the configuration file
   * 
   * @param string $filename
   * @return \Andou\Staticizer\App
   */
  protected function readConfigFile($filename) {
    $staticization_task = file_get_contents($filename);
    $this->_configs = json_decode($staticization_task, TRUE);
    return $this;
  }

  /**
   * Returns a list of pages to staticize
   * 
   * @return string
   */
  protected function listPageToStaticize() {
    return $this->_configs['pages'];
  }

  /**
   * Returns the folder in which to save staticized content
   * 
   * @return string
   */
  protected function getDestinationPool() {
    return rtrim(ltrim($this->_configs['destination_pool'], "/"), "/");
  }

  /**
   * Retrieves the basepath url of the destinated project
   * 
   * @return string
   */
  protected function getBaseurl() {
    return $this->_configs['baseurl'];
  }

  /**
   * Trims away the basepath from a url
   * 
   * @param string $url
   * @return string
   */
  protected function relativeUrl($url) {
    return rtrim(str_replace($this->getBaseurl(), "", $url), "/") . "/";
  }

  /**
   * Creates (if needed) the destination folder and returns it
   * 
   * @return string
   */
  protected function getDestinationFolder() {
    if (!isset($this->_pool)) {
      $pool = rtrim(ltrim($this->_configs['pool'], "/"), "/");
      if (!file_exists($pool)) {
        mkdir($pool, 0700, true);
        $this->_echo("Staticization folder created");
      }
      $this->_pool = $pool;
    }
    return $this->_pool;
  }

  /**
   * Runs the task of staticization
   * 
   * @return boolean|\Andou\Staticizer\App
   */
  public function staticize() {
    if (!isset($this->_configs)) {
      $this->_echo("No valid config file specified");
      return FALSE;
    }
    foreach ($this->listPageToStaticize() as $url => $filename) {
      $this->staticizeContent($this->getHTML($url), $filename);
      $this->_statics[$url] = $filename;
      $this->_echo(sprintf("Staticized %s, saved in %s", $url, $filename));
    }
    file_put_contents($this->composeStaticFilename('rewrite_rules.txt'), $this->generateHtaccess());
    $this->_echo(sprintf("Generated rewrite rules"));
    return $this;
  }

  /**
   * Compose the filename of the static resource
   * 
   * @param string $filename
   * @return string
   */
  protected function composeStaticFilename($filename) {
    return sprintf("%s/%s/%s", BASEPATH, $this->getDestinationFolder(), $filename);
  }

  /**
   * Retrieve the HTML of a URI
   * 
   * @param string $address
   * @return string
   */
  protected function getHTML($address) {
    $headers = $this->_configs['headers'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $address,
        CURLOPT_HEADER => FALSE,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.104 Safari/537.36'
    ));
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
  }

  /**
   * Staticize an HTML content into a file
   * 
   * @param string $content
   * @param string $filename
   */
  protected function staticizeContent($content, $filename) {
    file_put_contents($this->composeStaticFilename($filename), $content);
  }

  /**
   * Generates a list of Rewrite Rules
   * 
   * @return string
   */
  protected function generateHtaccess() {
    $rewrites = array("#Start of " . $this->_configs['operation'], "#RewriteEngine on");
    foreach ($this->_statics as $url => $filename) {
      $rewrites[] = "RewriteCond   %{QUERY_STRING} !cacheoff";
      $rewrites[] = sprintf('RewriteRule   ^%s$ /%s/%s [PT]', $this->relativeUrl($url), $this->getDestinationPool(), basename($filename));
    }
    return implode("\n", $rewrites) . "\n#End of " . $this->_configs['operation'] . "\n";
  }

  /**
   * Sets the output provider
   * 
   * @param \Andou\Shelltools\Outputprovider $op
   */
  public function setOutputProvider(\Andou\Shelltools\Outputprovider $op) {
    $this->_output_provider = $op;
  }

  /**
   * Echoes a message in stdout
   * 
   * @param string $message
   * @return \Andou\Automatedpagetest\App
   */
  public function _echo($message) {
    if ($this->_verbose) {
      if (isset($this->_output_provider)) {
        $this->_output_provider->ol($message);
      } else if (defined("IS_SHELL") && IS_SHELL == TRUE) {
        echo $message . "\n";
      } else {
        echo $message . "<br/>";
      }
    }
    return $this;
  }

}