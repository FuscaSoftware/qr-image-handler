<?php
//require_once __DIR__ . "/Collection.php";
/**
 * User: sbraun
 * Date: 18.07.17
 * Time: 14:02
 */
class Map extends stdClass implements Serializable, ArrayAccess, Countable {
    protected $map = [];
    public function __construct(array $map = null) {
        if (!is_null($map) && is_array($map)) {
            if (!(count(array_keys($map)) == count(array_values($map))))
                throw new Exception("\$map is not valid!");
            ksort($map);
            $this->map = $map;
        }
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return isset($this->map[$offset]);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        if (is_numeric($offset) && !$this->offsetExists($offset) && count($this->map) > $offset)
            $offset = array_keys($this->map)[$offset];
        if ($offset == "keys" && !$this->offsetExists($offset) && count($this->map) > 0)
            return array_keys($this->map);
        if ($offset == "values" && !$this->offsetExists($offset) && count($this->map) > 0)
            return array_values($this->map);
        return [$offset => $this->map[$offset]];
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $r = $this->map[$offset] = $value;
        ksort($this->map);
        return $r;
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        unset($this->map[$offset]);
    }


    /**
     * String representation of object
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize() {
        return serialize($this->map);
    }

    /**
     * Constructs the object
     * @link  http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized) {
        $this->map = unserialize($serialized);
    }

    /**
     * @return string|false
     */
    public function as_key() {
        if (empty($this->map))
            return false;
        ksort($this->map);
        $keys = array_keys($this->map);
        $values = array_values($this->map);
        if (count($keys) == 1 && count($values) == 1)
            $string =  $keys[0] ."--".$values[0];
        if (count($keys) == 2 && count($values) == 2)
            $string =  $keys[0] ."--".$values[0]."---".$keys[1] ."--".$values[1];
        return rawurlencode($string);
    }

    public function to_string() {
        return $this->as_key();
    }

    public function get_knot_key() {
        return $this->to_string();
    }

    public function count() {
        return count($this->map);
    }
}