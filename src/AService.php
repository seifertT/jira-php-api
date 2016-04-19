<?php


namespace biologis\JIRA_PHP_API;

/**
 * Class AService
 * @package biologis\JIRA_PHP_API
 */
abstract class AService {
  /**
   * @var \biologis\JIRA_PHP_API\ICommunicationService
   */
  private $communicationService;


  /**
   * AService constructor.
   * @param \biologis\JIRA_PHP_API\ICommunicationService $comService
   */
  function __construct(ICommunicationService $comService) {
    $this->communicationService = $comService;
  }


  /**
   * @return \biologis\JIRA_PHP_API\ICommunicationService
   */
  public function getCommunicationService() {
    return $this->communicationService;
  }
}