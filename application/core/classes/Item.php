<?php

/**
 * User: sbraun
 * Date: 18.07.17
 * Time: 14:03
 */
class Empty_Silent_Item extends stdClass
{

    public function __get($name) {
        return (isset($this->$name) ? $this->$name : "");
    }

}

class Item extends stdClass
{
    /** @var Map $_primary */
    protected $_primary;
    protected $_updated;
    protected $__MODEL;
    /** @var array $foreign_keys */
    protected $foreign_keys;

    /**
     * @return string ClassName
     */
    public function get_class() {
        return get_class($this);
    }

    public function __construct($values = null) {
        if (!is_null($values)) {
            foreach ($values as $k => $v) {
                $this->$k = $values[$k];
            }
        }
        if (!empty($this->{$this->__MODEL . "_id"}))
            $this->id = $this->{$this->__MODEL . "_id"};
        elseif (!isset($this->id))
            $this->id = 0;
    }

    public function __call($name, $arguments) {
//        var_dump($name);
//        var_dump($arguments);
//        // TODO: Implement __call() method.
        if (substr($name, 0, 4) == "get_")
            return $this->__get(substr($name, 4));
        elseif (substr($name, 0, 4) == "set_")
            return $this->__set(substr($name, 4), $arguments[0]);
    }

    /**
     * Returns property of the item-object
     * it returns the reference to allow setting sub-array-properties
     * @param $name
     *
     * @return mixed|null
     */
    public function &__get($name) {
//        var_dump(array(__LINE__ =>$name));
        if (method_exists($this, "get_" . $name)) {
//            return $this->{"get_$name"}();
            $r = call_user_func([$this, "get_$name"]);
            return $r;
        } elseif (property_exists($this, $name))
            return $this->$name;
        # the following leads to null-value properties if property was accessed, but is needed to do
        # sth. like this: $items[$k]->{$expected_object_type}[$item2->id] = $item2;
        else {
            if (in_array($name, ['id'])) # this avoid the property will be set, just by accessing it
                return null;
            return $this->$name;
        }
        // TODO: Implement __get() method.
    }

    public function __set($name, $value) {
//        var_dump($name);
//        var_dump($value);
        // TODO: Implement __set() method.
        if (method_exists($this, "set_" . $name))
//            return $this->{"set_".$name};
            return call_user_func(array($this, "set_$name"), $value);
        else {
            if (is_array($value))
                return @$this->$name = $value;
            return $this->$name = $value;
        }
    }

    public function __isset(string $name):bool {
        return (bool) isset($this->$name);
    }

    /**
     * @param mixed|null $value - null for get | else for get
     *
     * @return Map
     */
    public function _primary($value = null) {
        if (!is_null($value)) { # setting
            $this->_set_primary($value);
        }
        # getting
        return $this->_get_primary();
    }

    protected function _set_primary($value = null) {
        if (is_null($value)) {
//            $this->_primary = new Map(['unknown-item' => @$this->id]);
            if (property_exists($this, "id") && is_numeric($this->id) && $this->__MODEL)
                $this->_primary = new Map([$this->__MODEL => $this->id]);
            elseif ($this->__MODEL)
                $this->_primary = new Map([$this->__MODEL => @$this->id]);
            else
                $this->_primary = new Map(['unknown-item' => @$this->id]);
        } else
            $this->_primary = new Map($value);
    }

    protected function _get_primary() {
        if (!isset($this->_primary)) {
            $this->_set_primary();
        }
        return $this->_primary;
    }
}
