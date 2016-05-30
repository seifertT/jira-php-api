<?php


namespace biologis\JIRA_PHP_API;


/**
 * Class Transition
 * @package biologis\JIRA_PHP_API
 */
class Transition extends DiffableObject {
  private $issue;

  private $transitionCompleted;

  /**
   * Transition constructor.
   * @param $issue Issue
   * @param bool $transition
   */
  function __construct($issue, $transition = FALSE) {
    $this->issue = $issue;
    $this->transitionCompleted = FALSE;
    $this->initiate();

    if ($transition !== FALSE) {
      $this->transition->setId($transition);
    }
  }

  /**
   * Base initiation of this Transition object.
   */
  private function initiate() {
    $this->addDiffableObject('fields');
    $this->addDiffableObject('transition');
  }


  /**
   * Adds a comment that is added if the transition is executed.
   *
   * @param $commentMessage
   * @return bool
   */
  public function addTransitionComment($commentMessage) {
    if (empty($commentMessage)) {
      return false;
    }

    $this->addDiffableObject('update');
    $this->update->addUntrackedArray('comment');

    if (!is_array($this->update->comment)) {
      return false;
    }

    if (empty($this->update->comment)) {
      $comment = new DiffableObject();
      $comment->addDiffableObject('add');
      $comment->add->setBody($commentMessage);

      $this->update->comment[] = $comment;
    }
    else {
      $this->update->comment[0]->add->setBody($commentMessage);
    }

    return true;
  }

  /**
   * Executes the transition.
   * After a successful transition this function will not work again for this object.
   *
   * @return bool
   */
  public function doTransition() {
    // do not transition if this was already executed
    if ($this->transitionCompleted) {
      return TRUE;
    }

    // only execute transition if id is given
    if (empty($this->transition->getId())) {
      return FALSE;
    }

    // issue of this transition must be set and persistent
    if (empty($this->issue) || !$this->issue->isPersistent()) {
      return FALSE;
    }

    // this transition must be the issue's active one
    if ($this->issue->getActiveTransition() !== $this) {
      return FALSE;
    }

    // get transition's issue's key or id
    if (!empty($this->issue->getKey())) {
      $issue_identifier = $this->issue->getKey();
    }
    elseif (!empty($this->issue->getId())) {
      $issue_identifier = $this->issue->getId();
    }
    else {
      return FALSE;
    }

    $diffObject = $this->createDiffObject(true);

    $path = 'issue/' . $issue_identifier . '/transitions';

    $response = $this->issue->getCommunicationService()->post($path, $this->getDiffObject());

    if ($response === FALSE) {
      return FALSE;
    }

    $this->transitionCompleted = TRUE;
    $this->issue->deleteActiveTransition($this);
    return TRUE;
  }
}