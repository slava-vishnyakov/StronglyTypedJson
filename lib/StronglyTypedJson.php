<?php

abstract class StronglyTypedJson
{
    static $validate = true;
    protected $__types = array();
    protected $__values = array();

    private function validateAgainstType($type, $value)
    {
        switch ($type) {
            case 'anything':
                return true;
            case 'integer':
                return is_integer($value);
            case 'int':
                return is_integer($value);
            case 'double':
                return is_double($value);
            case 'bool':
                return is_bool($value);
            case 'float':
                return is_float($value);
            case 'long':
                return is_long($value);
            case 'numeric':
                return is_numeric($value);
            case 'string':
                return is_null($value) or is_string($value);
            default:
                if (class_exists($type)) {
                    return (get_class($value) == $type) or
                           (get_class($value) == $type) or
                           (is_subclass_of($value, $type)) or
                           (is_subclass_of($value, $type));
                }
                throw new InvalidArgumentException("There was no validator for '$type'");
        }
    }

    public function toArray()
    {
        if(self::$validate) {
            $this->validateObject();
        }
        $array = $this->getPrivateVars($this);
        if($array == array()) {
            return new stdClass;
        }
        return $this->makeArray($array);
    }

    private function getPrivateVars()
    {
        $class = new ReflectionObject($this);
        $values = array();
        foreach($class->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {
            $name = $property->getName();
            if(isset($this->__values[$name])) {
                $values[$name] = $this->__values[$name];
            } else {
                $values[$name] = $this->getDefaultValueForProperty($name);
            }
        }
        return $values;
    }

    private function makeArray($objectVars)
    {
        /** @var $value StronglyTypedJson */
        foreach ($objectVars as $key => $value) {
            if (is_object($value)) {
                $objectVars[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $objectVars[$key] = $this->makeArray($value);
            }
        }
        return $objectVars;
    }

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    function __get($name)
    {
        return $this->__values[$name];
    }

    function __set($name, $value)
    {
        if(self::$validate) {
            $this->validateProperty($name, $value);
        }
        $this->__values[$name] = $value;
    }

    private function getDefaultValueForProperty($name)
    {
        $type = $this->getPropertyType($name);
        if (substr($type, -2, 2) == '[]') {
            return array();
        } else {
            return null;
        }

    }

    private function getPropertyType($name)
    {
        try {
            $property = new ReflectionProperty(get_class($this), $name);
        } catch (Exception $e) {
            if(isset($this->__types[$name])) {
                return $this->__types[$name];
            } else {
                throw new RuntimeException("Unknown property $name when assigning");
            }
        }
        $docComment = $property->getDocComment();
        preg_match('#@var\s+([\w\[\]\?]+)#is', $docComment, $m);
        if ($m) {
            $type = $m[1];
            return $type;
        } else {
            $type = null;
            return $type;
        }
    }

    private function validateProperty($name, $value)
    {
        $type = $this->getPropertyType($name);
        if($type) {
            if(!$this->isValidProperty($name, $value, $type)) {
                if(is_object($value)) {
                    $thisType = get_class($value);
                } else {
                    $thisType = json_encode($value);
                }
                $selfClass = get_class($this);
                throw new InvalidArgumentException("ArgumentException: '{$name}' of '{$selfClass}' was not valid '{$type}' (actual type = {$thisType})");
            }
        }
    }

    private function isValidProperty($name, $value, $type)
    {
        if ($type) {
            if (substr($type, -1, 1) == '?') {
                return $this->validateNullable($type, $value, $name);
            }
            if (substr($type, -2, 2) == '[]') {
                return $this->validateArray($type, $value);
            }
            return $this->validateAgainstType($type, $value);
        }
    }

    private function validateObject()
    {
        foreach(get_object_vars($this) as $name => $value) {
            $this->validateProperty($name, $value);
        }
    }

    public function addProperty($name, $type) {
        $this->__types[$name] = $type;
    }

    private function validateArray($type, $value)
    {
        $subtype = substr($type, 0, strlen($type) - 2);
        if (!is_array($value)) {
            return false;
        }
        if (substr(json_encode($value),0,1) != '[') {
            return false;
        }
        foreach ($value as $i => $element) {
            /** @var $element StronglyTypedJson */
            if($element instanceof StronglyTypedJson) {
                $element->validateObject();
                return true;
            } elseif(!is_object($element)) {
                $this->validateAgainstType($subtype, $element);
            } else {
                if (!$this->validateProperty($subtype, $element)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function validateNullable($type, $value, $name)
    {
        $subtype = substr($type, 0, strlen($type) - 1);
        if(is_null($value)) {
            return true;
        } else {
            return $this->isValidProperty($name, $value, $subtype);
        }
    }

}