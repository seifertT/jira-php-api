<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class IssueService
 * @package biologis\JIRA_PHP_API
 */
class IssueService extends AService {
  /**
   * Creates a new JIRA issue search.
   * @return \biologis\JIRA_PHP_API\Search
   */
  public function createSearch() {
    $search = new Search($this);

    return $search;
  }


  /**
   * Loads and returns a jira issue.
   *
   * @param string|int $key issue key or id to load
   * @return \biologis\JIRA_PHP_API\Issue Issue or null if it does not exist.
   */
  public function load($key) {
    $parameters = array(
      'fields' => '',
      'expand' => '',
    );

    $response = $this->getCommunicationService()->get('issue/' . $key, $parameters);

    if ($response) {
      $response = GenericJiraObject::transformStdClassToGenericJiraObject($response);

      return new Issue($this, $response, TRUE);
    }
    else {
      return null;
    }
  }


  /**
   * Creates a new JIRA issue.
   * @return \biologis\JIRA_PHP_API\Issue
   */
  public function create() {
    $issue = new Issue($this);

    return $issue;
  }
}