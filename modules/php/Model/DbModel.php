<?php

namespace PAX\Model;

use PAX\Core\Game;

// Active Record-stle ORM.
// Subclasses may only define instance variables that will be persisted to the
// database.
abstract class DbModel
{
  abstract protected function tableName();

  // Override if the table uses a different column for its primary key.
  protected function primaryKey()
  {
    return 'id';
  }

  // TODO is DbQuery vulnerable to sql injection?
  static public function create($instance)
  {
    $props = get_object_vars($instance);
    $propKeys = implode(', ', array_keys($props));
    // Flexible heredoc syntax not available until PHP 7.3.0
    $sql = ('' .
      "INSERT INTO {$instance->tableName()} ($propKeys)\n" .
      "VALUES ({$instance->sqlFormattedValues($props)})" .
      '');
    Game::get()->DbQuery($sql);
  }

  // If the properties specified in $fieldMap exists in the subclass, set the
  // subclass property to $fieldMap[$prop]. Immediately commits to the database.
  public function update($fieldMap)
  {
    $primaryKeyName = $this->primaryKey();
    // Check primary key not set
    if (isset($fieldMap[$primaryKeyName])) {
      throw new Exception("Cannot update "  . get_class($this) . " {$primaryKeyName} to {$fieldMap[$primaryKeyName]}: {$primaryKeyName} is the primary key");
    }

    // Check only fields that exist are set
    $props = get_object_vars($this);

    foreach ($fieldMap as $field => $unused_value) {
      if (!isset($props[$field])) {
        throw new Exception("Cannot update "  . get_class($this) . " {$primaryKeyName} to {$fieldMap[$field]}: {$field} is not a valid property");
      }
    }

    // Finally update object
    foreach ($fieldMap as $prop => $value) {
      $this->$prop = $value;
    }

    // Commit to database
    $this->commitUpdate($fieldMap);
  }

  // Persist specified object properties to the database.
  public function commitUpdate($fieldMap)
  {
    $primaryKeyName = $this->primaryKey();
    $primaryKeyValue = $this->$primaryKeyName;

    $sql = ('' .
      "UPDATE {$this->tableName()}" .
      "SET {$this->sqlFormattedKeyEqualValue($fieldMap)}" .
      "WHERE {$primaryKeyName} = {$primaryKeyValue}" .
      '');
    // print $sql;
    Game::get()->DbQuery($sql);
  }

  // Returns comma-separated string of values.
  private function sqlFormattedValues($fieldMap)
  {
    $arr = [];
    foreach ($fieldMap as $_prop => $value) {
      // Wraps string in single quotes
      $formattedValue = is_string($value) ? "'{$value}'" : "$value";
      $arr[] = $formattedValue;
    }
    return implode(', ', $arr);
  }

  // Returns a comma-separated string of key = value.
  private function sqlFormattedKeyEqualValue($fieldMap)
  {
    $arr = [];
    foreach ($fieldMap as $prop => $value) {
      $formattedValue = is_string($value) ? "'{$value}'" : "$value";
      $arr[] = $formattedValue;
    }
    return implode(', ', $arr);
  }

  protected function toJson()
  {
    return json_encode(get_object_vars($this));
  }

  public function __toString()
  {
    return $this->toJson();
  }
}
