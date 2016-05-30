<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class GenericJiraObject
 * @package biologis\JIRA_PHP_API
 */
class GenericJiraObject extends DiffableObject {


  /**
   * @var IGenericJiraObjectRoot
   */
  protected $genericJiraObjectRoot;


  /**
   * Transforms a stdClass Object and all its child Objects to GenericJiraObjects.
   * The object to copy will not be modified, a GenericJiraObject copy is returned.
   *
   * @param \stdClass $data
   * @return \biologis\JIRA_PHP_API\GenericJiraObject copy of $data were stdClass is replaced with GenericJiraObject
   */
  public static function transformStdClassToGenericJiraObject(\stdClass $data) {
    $transformed_object = new GenericJiraObject();

    foreach ($data as $propertyKey => $propertyValue) {
      if (is_a($propertyValue, 'stdClass')) {
        $transformed_object->{$propertyKey} = self::transformStdClassToGenericJiraObject($propertyValue);
      }
      else {
        $propertyValueTransformed = $propertyValue;

        if (is_array($propertyValueTransformed)) { // we do not support objects hidden in arrays of arrays
          foreach ($propertyValueTransformed as $array_key => $array_element) {
            if (is_a($array_element, 'stdClass')) {
              $propertyValueTransformed[$array_key] = self::transformStdClassToGenericJiraObject($array_element);
            }
          }
        }

        $transformed_object->{$propertyKey} = $propertyValueTransformed;
      }
    }

    return $transformed_object;
  }


  /**
   * GenericJiraObject constructor.
   * @param null $genericJiraObjectRoot
   */
  function __construct($genericJiraObjectRoot = null) {
    parent::__construct();

    $this->genericJiraObjectRoot = $genericJiraObjectRoot;
  }


  /**
   * Extends ADiffableObject by loading the root object.
   * @inheritdoc
   * @param $name
   * @param $arguments
   */
  function __call($name, $arguments) {
    if (strpos($name, 'get') === 0) {
      if ($this->genericJiraObjectRoot != null) {
        $this->genericJiraObjectRoot->loadData();
      }
    }

    return parent::__call($name, $arguments);
  }


  /**
   * Adds a GenericJiraObject as property to this object.
   * If the property already exists, either a GenericJiraObject or null is returned.
   *
   * @param $name
   * @return \biologis\JIRA_PHP_API\GenericJiraObject the added object or null if property name is already taken
   */
  public function addGenericJiraObject($name) {
    return $this->addDiffableObject($name, new GenericJiraObject());
  }



  protected function setGenericJiraObjectRootRecursive($gJORoot) {
    $this->genericJiraObjectRoot = $gJORoot;

    foreach ($this as $propertyKey => $propertyValue) {
      // only access public properties
      if (!$this->getInitialReflectionObject()->hasProperty($propertyKey) || $this->getInitialReflectionObject()->getProperty($propertyKey)->isPublic()) {
        // never traverse a non GenericJiraObject or a root object
        if (is_a($propertyValue, 'biologis\JIRA_PHP_API\GenericJiraObject') && !is_a($propertyValue, 'biologis\JIRA_PHP_API\IGenericJiraObjectRoot')) {
          $propertyValue->setGenericJiraObjectRootRecursive($gJORoot);
        }
        elseif (is_array($propertyValue)) { // we do not support objects hidden in arrays of arrays
          foreach($propertyValue as $arrayValue) {
            if (is_a($arrayValue, 'biologis\JIRA_PHP_API\GenericJiraObject') && !is_a($arrayValue, 'biologis\JIRA_PHP_API\IGenericJiraObjectRoot')) {
              $arrayValue->setGenericJiraObjectRootRecursive($gJORoot);
            }
          }
        }
      }
    }
  }

  /**
   * @param \biologis\JIRA_PHP_API\GenericJiraObject $object
   */
  protected function merge(GenericJiraObject $object) {

    foreach ($object as $propertyName => $propertyValue) {
      // only merge public properties
      if (!$this->getInitialReflectionObject()->hasProperty($propertyName) || $this->getInitialReflectionObject()->getProperty($propertyName)->isPublic()) {
        // FIXME this should only be a short term approach; we can not merge objects in arrays, because we have no guarantee that they are in the same order
        // FIXME this would require some kind of comparator (e.g. key, id)
        if (property_exists($this, $propertyName) && is_a($this->{$propertyName}, 'biologis\JIRA_PHP_API\GenericJiraObject') && is_a($propertyValue, 'biologis\JIRA_PHP_API\GenericJiraObject')) {
          $this->{$propertyName}->merge($propertyValue);
        }
        else {
          $this->{$propertyName} = $propertyValue;
        }
      }
    }
  }
}