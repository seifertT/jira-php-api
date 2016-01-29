<?php

namespace biologis\JIRA_PHP_API;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7;


/**
 * Class GuzzleCommunicationService
 * @package biologis\JIRA_PHP_API
 *
 * Implementation of ICommunicationService using guzzle.
 */
class GuzzleCommunicationService implements ICommunicationService {


  /**
   * Request timeout.
   * @var float
   */
  private $timeout;

  /**
   * URL to JIRA REST servive, e.g. https://jira.yourdomain.com/rest/api/2/
   * @var string
   */
  private $jiraURL;

  /**
   * User credentials username and password stored in an array.
   * @var array
   */
  private $jiraCredentials;

  /**
   * Reference to guzzle client.
   * @var \GuzzleHttp\Client
   */
  private $guzzleHTTPClient;


  /**
   * GuzzleCommunicationService constructor.
   * @param string $jiraURL URL to JIRA REST servive
   * @param array $jiraCredentials jira username and password
   */
  function __construct($jiraURL, $jiraCredentials) {
    $this->jiraURL = $jiraURL;
    $this->jiraCredentials = $jiraCredentials;
    $this->timeout = 10.0;

    $this->guzzleHTTPClient = new Client([
      'base_uri' => $this->jiraURL,
      'timeout'  => $this->timeout,
    ]);
  }


  /**
   * @return float
   */
  public function getTimeout() {
    return $this->timeout;
  }


  /**
   * @return string
   */
  public function getJiraURL() {
    return $this->jiraURL;
  }


  /**
   * @return Client
   */
  public function getGuzzleHTTPClient() {
    return $this->guzzleHTTPClient;
  }


  /**
   * @return array
   */
  public function getJiraCredentials() {
    return $this->jiraCredentials;
  }


  /**
   * @param array $credentials array with username and password
   */
  public function setJiraCredentials($credentials = array()) {
    $this->jiraCredentials = $credentials;
  }


  /**
   * @param string $path
   * @param \stdClass $data
   * @return bool|\stdClass
   */
  public function put($path, \stdClass $data) {
    // serialize data
    $data_json = json_encode($data);

    $options = array(
      'body' => $data_json,
      'headers' => array(
        'Content-type' => 'application/json',
      )
    );

    $this->addCredentials($options);

    try {
      $response = $this->guzzleHTTPClient->request('PUT', $path, $options);

      if ($response->getStatusCode() == 204) {
        return new \stdClass();
      }
      else {
        return false;
      }
    } catch(\Exception $e) {
      return false;
    }
  }


  /**
   * @param string $path
   * @param \stdClass $data
   * @return bool|mixed
   */
  public function post($path, \stdClass $data) {
    // serialize data
    $data_json = json_encode($data);

    $options = array(
      'body' => $data_json,
      'headers' => array(
        'Content-type' => 'application/json',
      )
    );

    $this->addCredentials($options);

    try {
      $response = $this->guzzleHTTPClient->request('POST', $path, $options);

      if ($response->getStatusCode() == 201 || $response->getStatusCode() == 200) {
        $response_content = json_decode($response->getBody()->getContents());
        return $response_content;
      }
      else {
        return false;
      }
    } catch (\Exception $e) {
      return false;
    }
  }


  /**
   * @param string $path
   * @param array $parameters
   * @return bool
   */
  public function delete($path, $parameters = array()) {
    // TODO: Implement delete() method.
    return false;
  }


  /**
   * @param \biologis\JIRA_PHP_API\relative $path
   * @param array $parameters
   * @return bool|mixed
   */
  public function get($path, $parameters = array()) {

    $query_parameters= http_build_query(array_filter($parameters));
    $relative_path = $path . $query_parameters;

    $options = array();

    $this->addCredentials($options);

    try {
      $response = $this->guzzleHTTPClient->request('GET', $relative_path, $options);

      if ($response->getStatusCode() == 200) {
        $response_content = json_decode($response->getBody()->getContents());
        return $response_content;
      }
      else {
        return false;
      }
    } catch (\Exception $e) {
      return false;
    }
  }


  /**
   * Add basic auth credentials to options array.
   * @param $options
   */
  private function addCredentials(&$options) {
    $options += array(
      'auth' => array(
        $this->jiraCredentials['username'],
        $this->jiraCredentials['password']
      )
    );
  }
}