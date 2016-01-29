<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class Search
 * @package biologis\JIRA_PHP_API
 */
class Search implements \Iterator {

  /**
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  private $issueService;

  /**
   * @var
   */
  private $jql;

  /**
   * @var int
   */
  private $startAt;

  /**
   * @var int
   */
  private $maxResults;

  /**
   * @var array
   */
  private $fields;

  /**
   * @var bool
   */
  private $validateQuery;

  /**
   * @var array
   */
  private $expand;

  /**
   * @var bool
   */
  private $total;

  /**
   * @var array
   */
  private $issues;

  /**
   * @var int
   */
  private $currentIndex;


  /**
   * Search constructor.
   * @param \biologis\JIRA_PHP_API\IssueService $issueService
   */
  function __construct(IssueService $issueService) {
    $this->issueService = $issueService;

    $this->startAt = 0;
    $this->maxResults = 50;
    $this->fields = array();
    $this->validateQuery = true;
    $this->expand = array();

    $this->issues = array();
    $this->currentIndex = 0;
    $this->total = false;
  }


  /**
   * @param int $startAt
   */
  public function setStartAt($startAt) {
    $this->startAt = $startAt;
  }


  /**
   * @param int $maxResults
   */
  public function setMaxResults($maxResults) {
    $this->maxResults = $maxResults;
  }


  /**
   * @param array $fields
   */
  public function setFields($fields) {
    $this->fields = $fields;
  }


  /**
   * @param string $expand
   */
  public function setExpand($expand) {
    $this->expand = $expand;
  }


  /**
   * @param boolean $validateQuery
   */
  public function setValidateQuery($validateQuery) {
    $this->validateQuery = $validateQuery;
  }


  /**
   * @return \biologis\JIRA_PHP_API\IssueService
   */
  public function getIssueService() {
    return $this->issueService;
  }


  /**
   * @return int
   */
  public function getStartAt() {
    return $this->startAt;
  }


  /**
   * @return mixed
   */
  public function getJql() {
    return $this->jql;
  }


  /**
   * @return int
   */
  public function getMaxResults() {
    return $this->maxResults;
  }


  /**
   * @return boolean
   */
  public function isValidateQuery() {
    return $this->validateQuery;
  }


  /**
   * @return string
   */
  public function getExpand() {
    return $this->expand;
  }


  /**
   * @return int
   */
  public function getTotal() {
    return $this->total;
  }


  /**
   * @param $jql
   * @return bool
   */
  public function search($jql) {
    if (empty($this->jql)) {
      $this->jql = $jql;
    }
    else {
      return false;
    }

    $this->performSearch();

    return true;
  }


  /**
   * @return bool
   */
  private function performSearch() {
    // calculate how many issues are already loaded and continue at this index
    $index = $this->startAt + sizeof($this->issues);

    if ($this->total > $index || $this->total === false) {
      $comm_object = new \stdClass();
      $comm_object->jql = $this->jql;
      $comm_object->startAt = $index;
      $comm_object->maxResults = $this->maxResults;
      if (!empty($this->expand)) {
        $comm_object->expand = $this->expand;
      }
      if (!empty($this->fields)) {
        $comm_object->fields = $this->fields;
      }

      $result = $this->issueService->getCommunicationService()->post('search', $comm_object);

      if ($result !== false) {
        $this->total = $result->total;
        $this->parseAndAddIssues($result->issues);

        // check if the expected amount of issues was retrieved
        if (sizeof($this->issues) == $index + $this->maxResults || sizeof($this->issues) == $this->total) {
          return true;
        }
      }
    }

    return false;
  }


  /**
   * @param $rawIssues
   */
  private function parseAndAddIssues($rawIssues) {
    foreach($rawIssues as $rawIssue) {
      $issue_data = GenericJiraObject::transformStdClassToGenericJiraObject($rawIssue);
      $this->issues[] = new Issue($this->issueService, $issue_data, false);
    }
  }


  /**
   *
   */
  public function loadAll() {
    while($this->performSearch()) {}
  }


  /**
   * @return array
   */
  public function getIssues() {
    return $this->issues;
  }


  /**
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   * @return mixed Can return any type.
   * @since 5.0.0
   */
  public function current() {
    return $this->issues[$this->currentIndex];
  }


  /**
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function next() {
    $this->currentIndex++;
  }


  /**
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   * @return mixed scalar on success, or null on failure.
   * @since 5.0.0
   */
  public function key() {
    return $this->currentIndex;
  }


  /**
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean The return value will be casted to boolean and then evaluated.
   * Returns true on success or false on failure.
   * @since 5.0.0
   */
  public function valid() {
    // lazy loading (since only partial of total is retrieved)
    if ($this->currentIndex >= count($this->issues) && $this->currentIndex < $this->total) {
      return $this->performSearch();
    }

    return $this->currentIndex >= 0 && $this->currentIndex < $this->total;
  }


  /**
   * Rewind the Iterator to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function rewind() {
    $this->currentIndex = 0;
  }
}