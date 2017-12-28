<?php
require_once __DIR__ . "/Collection.php";
/**
 * User: sbraun
 * Date: 18.07.17
 * Time: 14:00
 */
class Collection extends stdClass implements Serializable, ArrayAccess, Countable, Iterator, JsonSerializable
{
    /** @var array */
    protected $array = [];
    /** @var int */
    protected $position = null;

    /**
     * Collection constructor.
     *
     * @param array|null $values
     */
    public function __construct(array $values = null) {
        $this->array = $values;
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
        return key_exists($offset, $this->array);
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
        var_dump(__METHOD__);
        return $this->array[$offset];
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
//        var_dump(__METHOD__);
        $this->array[$offset] = $value;
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
        var_dump(__METHOD__);
        unset ($this->array[$offset]);
    }

    /**
     * String representation of object
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize() {
        var_dump(__METHOD__);
        serialize($this->array);
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
        var_dump(__METHOD__);
        $this->array = unserialize($serialized);
    }

    /**
     * Count elements of an object
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count() {
        var_dump(__METHOD__);
        return count($this->array);
    }


    /**
     * Return the current element
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current() {
//        var_dump(__METHOD__);
        return $this->array[$this->position];
    }

    /**
     * Move forward to next element
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() {
//        var_dump(__METHOD__);
        ++$this->position;
    }

    /**
     * Return the key of the current element
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() {
//        var_dump(__METHOD__);
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() {
//        var_dump(__METHOD__);
        return isset($this->array[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() {
//        var_dump(__METHOD__);
        $this->position = 0;
    }

    public function isEmpty() {
        return ($this->count())? true : false;
    }

    public function first() {
        if (!is_null($this->array))
            return $this->array[array_keys($this->array)[0]];
        return $this->offsetGet(0);
    }

    public function last() {
        if (!is_null($this->array))
            return $this->array[end(array_keys($this->array))];
        return $this->offsetGet($this->count() - 1);
    }

    /**
     * @param int|null $limit_num
     * @param int  $limit_from
     *
     * @return array|object[]
     */
    public function get_array($limit_num = null, $limit_from = 0): array {
        if (!$limit_num && !$limit_from)
            return (is_array($this->array))? $this->array : [];
        else {
            $output = [];
            for ($i = $limit_from; (($i < ($limit_from + $limit_num)) && ($i < $this->count())); $i++) {
                $item = $this->offsetGet($i);
                $output[] = $item;
            }
            return $output;
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize() {
        return $this->get_array();
    }
}

/**
 * @param Collection|array  $array_or_collection
 *
 * @return array|bool
 */
function get_array($array_or_collection) {
    if (is_a($array_or_collection, "Collection"))
        return $array_or_collection->get_array();
    else if (is_array($array_or_collection))
        return $array_or_collection;
    else
        return [];
}

require_once __DIR__ . "/DynCollection.php";