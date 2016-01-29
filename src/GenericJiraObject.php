<?php

namespace biologis\JIRA_PHP_API;


/**
 * Class GenericJiraObject
 * @package biologis\JIRA_PHP_API
 */
class GenericJiraObject {


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
   * Transforms a stdClass Object and all its child Objects to GeneralJiraObjects.
   * The object to copy will not be modified, a GeneralJiraObject copy is returned.
   *
   * @param \stdClass $data
   * @return \biologis\JIRA_PHP_API\GenericJiraObject copy of $data were stdClass is replaced with GenericJiraObject
   */
  public static function transformStdClassToGenericJiraObject(\stdClass $data) {
    $reflection = new \ReflectionObject($data);
    $properties = $reflection->getProperties();

    $transformed_object = new GenericJiraObject();

    foreach ($properties as $property) {
      if (is_object($property->getValue($data)) && is_a($property->getValue($data), 'stdClass')) {
        $transformed_object->{$property->getName()} = self::transformStdClassToGenericJiraObject($property->getValue($data));
      }
      else {
        $transformed_object->{$property->getName()} = $property->getValue($data);
      }
    }

    return $transformed_object;
  }


  /**
   * GenericJiraObject constructor.
   */
  function __construct() {
    $this->propertyChanges = array();
    $this->diffObject = new \stdClass();
    $this->initialReflectionObject = new \ReflectionObject($this);
  }


  /**
   * @return \stdClass object containing all changes done via setter since the last save operation
   */
  public function getDiffObject() {
    return $this->diffObject;
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
    }

    if (strpos($name, 'set') === 0 && sizeof($arguments) == 1) {
      $propertyName = lcfirst(substr($name, 3));

      // never allow setting of private or protected properties
      if (!$this->initialReflectionObject->hasProperty($propertyName) || !$this->initialReflectionObject->getProperty($propertyName)->isPrivate()) {
        // do nothing if nothing changed
        if (!property_exists($this, $propertyName) || $this->{$propertyName} != $arguments[0]) {
          $this->{$propertyName} = $arguments[0];
          $this->propertyChanges[] = $propertyName;
        }
      }
    }
  }


  /**
   * Adds a GenericJiraObject as property to this object.
   * If the property already exists, either a GenericJiraObject or null is returned.
   *
   * @param $name
   * @return \biologis\JIRA_PHP_API\GenericJiraObject the added object or null if property name is already taken
   */
  public function addGenericJiraObject($name) {
    if (!property_exists($this, $name)) {
      $this->{$name} = new GenericJiraObject();
    }
    else {
      if (!is_object($this->{$name}) || !is_a($this->{$name}, 'biologis\JIRA_PHP_API\GenericJiraObject')) {
        return null;
      }
    }

    return $this->{$name};
  }


  /**
   * Creates a stdClass object that has the structure of this object, but only includes tracked changes.
   *
   * @return \stdClass a diff copy of this object
   */
  protected function createDiffObject() {
    $diffObject = new \stdClass();

    $reflection = new \ReflectionObject($this);
    $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    foreach ($public_properties as $public_property) {

      if (is_object($public_property->getValue($this)) && is_a($public_property->getValue($this), 'biologis\JIRA_PHP_API\GenericJiraObject')) {
        $subDiffObject = $public_property->getValue($this)->createDiffObject();

        // do not store if empty
        if (!empty((array) $subDiffObject)) {
          $diffObject->{$public_property->getName()} = $subDiffObject;
        }
      }
      elseif (is_array($this->propertyChanges) && in_array($public_property->getName(), $this->propertyChanges)) {
        $diffObject->{$public_property->getName()} = $public_property->getValue($this);
      }
    }

    $this->diffObject = $diffObject;
    return $diffObject;
  }


  /**
   * Resets all tracked changes for this GenericJiraObject and all child GenericJiraObjects.
   */
  protected function resetPropertyChangelist() {
    $reflection = new \ReflectionObject($this);
    $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    foreach ($public_properties as $public_property) {
      if (is_object($public_property->getValue($this)) && is_a($public_property->getValue($this), 'biologis\JIRA_PHP_API\GenericJiraObject')) {
        $public_property->getValue($this)->resetPropertyChangelist();
      }
    }

    $this->propertyChanges = array();
  }
}