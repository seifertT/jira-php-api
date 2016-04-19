<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class CommentService
 * @package biologis\JIRA_PHP_API
 */
final class CommentService extends AService {

  /**
   * Stores one single comment service instance per communication service object
   * @var array
   */
  private static $commentService = array();

  /**
   * Returns a comment service instance of the given communication service object
   *
   * @param $commService
   * @return CommentService
   */
  public static function getCommentService($commService) {
    $commServiceUID = spl_object_hash($commService);

    if (empty(self::$commentService[$commServiceUID])) {
      self::$commentService[$commServiceUID] = new CommentService($commService);
    }

    return self::$commentService[$commServiceUID];
  }


  /**
   * Creates a new JIRA comment.
   * @return \biologis\JIRA_PHP_API\Comment
   */
  public function create($jiraIssueID) {
    $comment = new Comment($this, $jiraIssueID);

    return $comment;
  }
}