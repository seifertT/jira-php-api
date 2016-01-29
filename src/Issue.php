<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class Issue
 * @package biologis\JIRA_PHP_API
 */
class Issue extends GenericJiraObject {

  /**
   * Reference to the IssueService that generated this issue.
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  private $issueService;

  /**
   * Whether this issue is fully loaded or not.
   * @var bool
   */
  private $isLoaded;

  /**
   * Whether this issue is stored in JIRA or not.
   * @var bool
   */
  private $isPersistent;


  /**
   * ID of this issue.
   * @var int
   */
  private $id;

  /**
   * Key of this issue.
   * @var string
   */
  private $key;


  /**
   * Issue constructor.
   * @param \biologis\JIRA_PHP_API\IssueService $issueService IssueService that generated this issue
   * @param \biologis\JIRA_PHP_API\GenericJiraObject|NULL $initObject this object will be merged into the issue
   * @param bool $isLoaded false if initObject might not contain all data of the issue
   */
  public function __construct(IssueService $issueService, GenericJiraObject $initObject = null, $isLoaded = false) {
    parent::__construct();

    $this->issueService = $issueService;
    $this->value = array();

    if ($initObject == null) {
      $this->isPersistent = false;
      $this->initializeIssueStub();
    }
    else {
      $this->isPersistent = true;
      $this->initialize($initObject);
    }

    $this->isLoaded = $isLoaded;
  }


  /**
   * @param string $name
   * @return mixed the property or null if it does not exist
   */
  public function __get($name) {
    if (property_exists($this, $name)) {
      return $this->{$name};
    }
    elseif ($this->isPersistent && !$this->isLoaded) {
      if (!empty($this->key) || !empty($this->id)) {
        $key = $this->key;

        if (empty($key)) {
          $key = $this->id;
        }

        $response = $this->communicationService->get('issue/' . $key);

        if ($response) {
          $response = GenericJiraObject::transformStdClassToGenericJiraObject($response);

          $this->merge($response);
          $this->isLoaded = true;
        }
        else {
          return null;
        }
      }
      else {
        // misconfigured object that is persistent, but does not have an id or key
        $this->isPersistent = false;
      }

      return $this->__get($name);
    }
    else {
      return null;
    }
  }


  /**
   * Either updates or creates this issue in JIRA.
   *
   * @return bool
   */
  public function save() {
    $this->createDiffObject();

    // update if this issue is already persistent in jira
    if ($this->isPersistent) {
      // if nothing changed, fake storage
      if (!empty((array) $this->getDiffObject())) {
        $issue_identifier = '';

        // prefer key over id
        if (!empty($this->key)) {
          $issue_identifier = $this->key;
        }
        elseif (!empty($this->id)) {
          $issue_identifier = $this->id;
        }
        else {
          return false;
        }

        $path = 'issue/' . $issue_identifier;

        $response = $this->issueService->getCommunicationService()->put($path, $this->getDiffObject());

        if ($response !== false) {
          $this->resetPropertyChangelist();
        }
        else {
          return false;
        }
      }
    }
    // create if this issue was not yet persistent in jira
    else {
      if ($this->hasRequiredCreateProperties()) {
        $response = $this->issueService->getCommunicationService()->post('issue', $this->getDiffObject());

        if ($response !== false) {
          $response = GenericJiraObject::transformStdClassToGenericJiraObject($response);

          if (!empty($response->id) && !empty($response->key)) {
            $this->merge($response);
            $this->resetPropertyChangelist();
            $this->isPersistent = true;
          }
          else {
            // this exception only occurs if JIRA does not provide data of the created issue
            // if save() is executed again, it would create another issue with the same data instead of updating the current one
            throw new \RuntimeException('The issue was created but this object could not be linked to it.');
          }
        }
        else {
          return false;
        }
      }
      else {
        return false;
      }
    }

    return true;
  }


  /**
   * @param \biologis\JIRA_PHP_API\GenericJiraObject $object
   */
  private function initialize(GenericJiraObject $object) {
    if (!$this->isLoaded && $this->isPersistent) {
      $this->merge($object);

      // a persistent issue requires at least a key or id
      $key_exists = property_exists($this, 'key') && !empty($this->key);
      $id_exists = property_exists($this, 'id') && !empty($this->id);

      if (!$key_exists || !$id_exists) {
        throw new \UnexpectedValueException('Loaded issue does not provide any key or id property.');
      }
    }
  }


  /**
   * @param \biologis\JIRA_PHP_API\GenericJiraObject $object
   */
  private function merge(GenericJiraObject $object) {
    foreach ($object as $key => $value) {
      $this->{$key} = $value;
    }
  }


  /**
   * Adds all required properties to create a new issue.
   */
  private function initializeIssueStub() {
    $fields = $this->addGenericJiraObject('fields');
    $project = $fields->addGenericJiraObject('project');
    $project->key = '';
    $project->id = '';
    $fields->summary = '';
    $fields->descripton = '';
    $issuetype = $fields->addGenericJiraObject('issuetype');
    $issuetype->name = '';
    $issuetype->id = '';
  }


  /**
   * Checks if all properties required to create a new issue are set.
   *
   * @return bool
   */
  private function hasRequiredCreateProperties() {
    $diffObject = $this->getDiffObject();

    $project_key_exists = !empty($diffObject->fields->project->key);
    $project_id_exists = !empty($diffObject->fields->project->id);
    $summary_exists = !empty($diffObject->fields->summary);
    $description_exists = !empty($diffObject->fields->description);
    $issuetype_name_exists = !empty($diffObject->fields->issuetype->name);
    $issuetype_id_exists = !empty($diffObject->fields->issuetype->id);

    return ($project_key_exists || $project_id_exists) && $summary_exists && $description_exists && ($issuetype_name_exists || $issuetype_id_exists);
  }
}