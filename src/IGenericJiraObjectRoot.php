<?php

namespace biologis\JIRA_PHP_API;


interface IGenericJiraObjectRoot {
  public function loadData();

  public function getCommunicationService();
}