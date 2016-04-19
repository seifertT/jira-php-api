<?php

namespace biologis\JIRA_PHP_API;

/**
 * Class Comment
 * @package biologis\JIRA_PHP_API
 */
class Comment {

  /**
   * @var \biologis\JIRA_PHP_API\CommentService
   */
  private $commentService;

  /**
   * @var int
   */
  private $jiraIssueID;

  /**
   * @var string
   */
  private $comment;
  /**
   * @var int
   */
  private $commentID;



  /**
   * @return string
   */
  public function getComment() {
    return $this->comment;
  }

  /**
   * @param string $comment
   */
  public function setComment($comment) {
    $this->comment = $comment;
  }


  /**
   * Comment constructor.
   * @param \biologis\JIRA_PHP_API\CommentService $commentService
   * @param $jiraIssueID
   */
  public function __construct(CommentService $commentService, $jiraIssueID) {
    $this->commentService = $commentService;
    $this->jiraIssueID = $jiraIssueID;
  }

  /**
   * Creates or updates the comment.
   * TODO: currently only creation is supported
   */
  public function save() {
    if (empty($this->jiraIssueID) || empty($this->commentService)) {
      return false;
    }

    if (empty($this->commentID)) {  // create
      $commentObject = new \stdClass();
      $commentObject->body = $this->comment;

      $path = 'issue/' . $this->jiraIssueID . '/comment';

      $response = $this->commentService->getCommunicationService()->post($path, $commentObject);

      if ($response !== false) {
        if (!empty($response->id)) {
          $this->commentID = $response->id;
        }
        else {
          // this exception only occurs if JIRA does not provide data of the created comment
          // if save() is executed again, it would create another comment with the same data instead of updating the current one
          throw new \RuntimeException('The comment was created but this object could not be linked to it.');
        }
      }
    }
    else {  // TODO update
      return false;
    }

    return true;
  }
}