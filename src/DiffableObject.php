<?php

namespace biologis\JIRA_PHP_API;

/**
 * Class DiffableObject
 * @package biologis\JIRA_PHP_API
 */
class DiffableObject {


  /**
   * Array that stores the name of non-GenericJiraObjects that have been added or changed.
   * @var array
   */
  private $propertyChanges;

  /**
   * @var \stdClass
   */
  private $diffObject;

  /**
   * Reflection of this object in its initial state.
   * @var \ReflectionObject
   */
  private $initialReflectionObject;

  /**
   * DiffableObject constructor.
   */
  function __construct() {
    $this->propertyChanges = array();
    $this->diffObject = new \stdClass();
    $this->initialReflectionObject = new \ReflectionObject($this);
  }

  /**
   * @return array
   */
  public function getPropertyChanges() {
    return $this->propertyChanges;
  }

  /**
   * @return \stdClass object containing all changes done via setter since the last save operation
   */
  public function getDiffObject() {
    return $this->diffObject;
  }

  /**
   * @return \ReflectionObject
   */
  public function getInitialReflectionObject() {
    return $this->initialReflectionObject;
  }


  /**
   * Implementation of __call
   * Adds getter and setter methods for all non-private properties.
   * If a set-Operation for a non-private property is called, the change
   * will be tracked.
   *
   * @param $name
   * @param $arguments
   * @return mixed
   */
  function __call($name, $arguments) {
    if (strpos($name, 'get') === 0) {
      $propertyName = lcfirst(substr($name, 3));

      if (property_exists($this, $propertyName)) {
        return $this->{$propertyName};
      }

      return null;
    }

    if (strpos($name, 'set') === 0 && sizeof($arguments) == 1) {
      $propertyName = lcfirst(substr($name, 3));

      // never allow setting of private or protected properties
      if (!$this->initialReflectionObject->hasProperty($propertyName) || $this->initialReflectionObject->getProperty($propertyName)->isPublic()) {
        // do nothing if nothing changed
        if (!property_exists($this, $propertyName) || $this->{$propertyName} != $arguments[0]) {
          $this->{$propertyName} = $arguments[0];
          $this->propertyChanges[] = $propertyName;
        }
      }
    }
  }


  /**
   * @param string $name
   * @return mixed the property or null if it does not exist
   */
  public function __get($name) {
    if (property_exists($this, $name)) {
      return $this->{$name};
    }
    else {
      return null;
    }
  }


  /**
   * Adds an array that is not set via the set operation and therefore not tracked.
   * Use this if this array will contain further DiffableObjects.
   *
   * @param $name property name of array
   * @return bool true if array was created successfully
   */
  protected function addUntrackedArray($name) {
    if (empty($name)) {
      return false;
    }

    if (property_exists($this, $name)) {
      return false;
    }

    $this->{$name} = array();
    return true;
  }


  /**
   * Creates a stdClass object that has the structure of this object, but only includes tracked changes.
   *
   * @param $traverseArrays search objects in first level of arrays and traverse on them too (default: false)
   * @return \stdClass a diff copy of this object
   */
  protected function createDiffObject($traverseArrays = FALSE) {
    $diffObject = new \stdClass();

    $reflection = new \ReflectionObject($this);
    $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    foreach ($public_properties as $public_property) {

      if (is_a($public_property->getValue($this), 'biologis\JIRA_PHP_API\DiffableObject')) {
        $subDiffObject = $public_property->getValue($this)->createDiffObject();

        // do not store if empty
        if (!empty((array) $subDiffObject)) {
          $diffObject->{$public_property->getName()} = $subDiffObject;
        }
      }
      else {
        if ($traverseArrays && is_array($public_property->getValue($this))) {
          $diffArray = array();

          foreach ($public_property->getValue($this) as $arrayValue) {
            if (is_a($arrayValue, 'biologis\JIRA_PHP_API\DiffableObject')) {
              $subDiffObject = $arrayValue->createDiffObject();

              // do not store if empty
              if (!empty((array) $subDiffObject)) {
                $diffArray[] = $subDiffObject;
              }
            }
          }

          $diffObject->{$public_property->getName()} = $public_property->getValue($this);
        }
        elseif (is_array($this->propertyChanges) && in_array($public_property->getName(), $this->propertyChanges)) {
          $diffObject->{$public_property->getName()} = $public_property->getValue($this);
        }
      }
    }

    $this->diffObject = $diffObject;
    return $diffObject;
  }

  /**
   * Adds a DiffableObject as property to this object.
   * If the property already exists, either a DiffableObject or null is returned.
   *
   * @param $name
   * @param $diffableObject
   * @return \biologis\JIRA_PHP_API\DiffableObject the added object or null if property name is already taken
   */
  protected function addDiffableObject($name, $diffableObject = null) {
    if (!property_exists($this, $name)) {
      if ($diffableObject == null) {
        $diffableObject = new DiffableObject();
      }

      if (is_a($diffableObject, 'biologis\JIRA_PHP_API\DiffableObject')) {
        $this->{$name} = $diffableObject;
      }
    }
    else {
      if (!is_a($this->{$name}, 'biologis\JIRA_PHP_API\DiffableObject')) {
        return null;
      }
    }

    return $this->{$name};
  }


  /**
   * Resets all tracked changes for this DiffableObject and all child DiffableObjects.
   */
  protected function resetPropertyChangelist() {
    $reflection = new \ReflectionObject($this);
    $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    foreach ($public_properties as $public_property) {
      if (is_a($public_property->getValue($this), 'biologis\JIRA_PHP_API\DiffableObject')) {
        $public_property->getValue($this)->resetPropertyChangelist();
      }
    }

    $this->propertyChanges = array();
  }

}