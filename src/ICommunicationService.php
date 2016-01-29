<?php

namespace biologis\JIRA_PHP_API;

/**
 * Interface ICommunicationService
 * @package biologis\JIRA_PHP_API
 *
 * All $path parameters are relative to the JIRA REST API URL with ending slah, e.g.
 * https://jira.yourdomain.com/rest/api/2/
 */
interface ICommunicationService {
  /**
   * A PUT request is send to the JIRA REST API.
   *
   * @param $path relative path to JIRA REST URL
   * @param \stdClass $data request that will be send as encoded JSON
   * @return mixed the decoded JSON response or false
   */
  public function put($path, \stdClass $data);

  /**
   * A GET request is send to the JIRA REST API.
   *
   * @param $path relative path to JIRA REST URL
   * @param array $paramaters query parameters
   * @return mixed the decoded JSON response or false
   */
  public function get($path, $paramaters = array());

  /**
   * A POST request is send to the JIRA REST API.
   *
   * @param $path relative path to JIRA REST URL
   * @param \stdClass $data request that will be send as encoded JSON
   * @return mixed the decoded JSON response or false
   */
  public function post($path, \stdClass $data);

  /**
   * A DELETE request is send to the JIRA REST API.
   *
   * @param $path relative path to JIRA REST URL
   * @param array $paramaters query parameters
   * @return bool true if the response is 2xx
   */
  public function delete($path, $parameters = array());

  /**
   * Sets the JIRA user credentials for communication.
   * Credentials are stored in an array, e.g.
   * array(
   *  'username' => 'JIRA_USERNAME',
   *  'password' => 'JIRA_USER_PASSWORD',
   * );
   *
   * @param array $credentials array with username and password
   */
  public function setJiraCredentials($credentials = array());
}