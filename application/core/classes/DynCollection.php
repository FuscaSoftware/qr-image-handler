<?php
require_once __DIR__ . "/Collection.php";

/**
 * User: sbraun
 * Date: 18.07.17
 * Time: 13:59
 */
class DynCollection extends Collection
{
    /** @var CI_DB_result */
    private $result = null;
    /** @var string */
    private $item_class_name = "stdClass";
    /** @var null */
    protected $array = null;

    /**
     * DynCollection constructor.
     *
     * @param CI_DB_result $result
     * @param string|null  $object_type
     */
    public function __construct($result = null, $object_type = null) {
        if (is_null($result) || is_a($result, 'CI_DB_result'))
            $this->result = $result;
        else {
            var_dump(__METHOD__);
            die("Invalid type of \$result.");
        }
        if (!is_null($object_type))
            $this->item_class_name = $object_type;
    }

    public function offsetGet($offset) {
        if (!isset($this->array[$offset])) {
            if ($offset < $this->count())
                $o = $this->result->custom_row_object($offset, $this->item_class_name);
            else
                return false;
//            $this->offsetSet($offset, $o);# seems to need a lot of memory
            return $o;
        } else
            parent::offsetGet($offset);
    }

    public function offsetExists($offset) {
        return ($this->offsetGet($offset))? true : false;
    }

    public function current() {
        return $this->offsetGet($this->position);
    }

    public function valid() {
        if ($this->position >= $this->count())
            return false;
        if ($v = parent::valid())
            return $v;
        if ($o = $this->result->custom_row_object($this->position, $this->item_class_name)) {
            return ($o) ? true : false;
        }
    }

    public function count() {
        return $this->result->num_rows();
    }

    /**
     * @param int|null $limit_num
     * @param int  $limit_from
     *
     * @return array|object[]
     */
    public function get_array($limit_num = null, $limit_from = 0): array {
        if (!$limit_num && !$limit_from)
            return $this->result->custom_result_object($this->item_class_name);
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
     * @param int      $options e.g. JSON_PRETTY_PRINT
     * @param null|int $limit_num
     * @param int      $limit_from
     *
     * @return string
     */
    public function get_json($options = 0, $limit_num = null, $limit_from = 0): string {
        $output = $this->get_array($limit_num, $limit_from);
//        return json_encode($output, JSON_PRETTY_PRINT);
        return json_encode($output, $options);
    }
}