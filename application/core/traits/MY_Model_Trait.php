<?php

/**
 * Created by PhpStorm.
 * User: sbraun
 * Date: 19.07.17
 * Time: 10:41
 */
trait MY_Model_Trait
{

    /** @var bool $loadDb */
    public $load_db = true;
    /** @var bool $read_only */
    public $read_only = false;
    /** @var string $database_group */
    public $database_group = null;

    /** @var string $table - overwrite it!!! */
    protected $table = null;
    /** @var string */
    public $full_controller_name = null;
    /** @var string */
    public $item_label = null;
    /** @var string */
    public $items_label = null;
    /** @var string $label_field - defines the field in database which is used by default for e.g. popups */
    public $label_field = "name";
    /** @var array modelnames/tablenames of objects which can be linked by default object_object_knots */
    public $linked_by_knot = array();

    /** @var array */
    public $function_fields = array("created" => "now()", "modified" => "now()", "uuid" => "uuid()");
    /** @var string $item_class */
    public $item_class = "Item";

    /**
     * Fields shown in Backend (edit)
     * @var array
     */
    public $backend_fields = array();

    /**
     * These fields are used for update/inserts to/in the database
     * @var array
     */
    public $db_fields = array();

    /**
     * These caches/stores the db-schema
     * @var array
     */
    public $db_schema = array();

    /**
     * @var MY_Controller $ci
     * @deprecated is ugly! use ci()!
     */
    public $ci;

    /**
     * @var MY_Controller $CI
     * @deprecated is ugly! use ci()!
     */
    public $CI;

    /**
     * Constructor which loads (by default) database
     * @throws RuntimeException
     */
    public function __construct() {
        parent::__construct();
        $this->init_db();
        $this->ci = $this->CI = MY_Controller::get_instance();
    }

    public function init_db($database_group = null) {
        if (!is_null($database_group))
            $this->database_group = $database_group;
        if ($this->load_db && !isset($this->db)) {
            if (!isset($this->database_group) || is_null($this->database_group))
                $this->load->database();
            else
//				$this->db = $this->load->database('smr-picture', TRUE);
                $this->db = $this->loader()->database($this->database_group, TRUE);
            if (@ci()->save_queries)
                $this->db->save_queries = true;

            if (empty($this->table) && !($this->table == false))
                throw new RuntimeException("Please define property \$table for '" . get_class($this) . "'-Class.");
//			var_dump(array(__LINE__,$this->db));
        }
    }

    public function list_fields_2D($foreign_object_type = null): array {
        if (isset($this->list_fields_2D))
            return $this->list_fields_2D;
        elseif (isset($this->list_fields))
            return $this->get_fields_2D($this->list_fields);
    }

    /**
     * Helper Function to give you object-type of $this->db
     * @return MY_DB_query_builder
     */
    public function db(): MY_DB_query_builder {
        return $this->db;
    }

    /**
     * Helper Function to give you object-type of $this->load
     * @return MY_Loader
     */
    public function loader(): MY_Loader {
        return $this->load;
    }

    public function mh(): Model_helper_lib {
        $this->loader()->library("model_helper_lib");
        return $this->model_helper_lib;
    }

    /**
     * @param db $db
     *
     * @return string
     */
    public function last_query($db = null) {
        if (is_null($db))
            $db = $this->db();
        return $db->last_query();
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function get_fields_2D(array $array) {
        foreach ($array as $v) {
            $fields_2D[$v] = array();
        }
        return $fields_2D;
    }

    /**
     * Wende Filter auf $db an.
     *
     * sample1 = [
     *  "hotel" => ["name" => $data['value']]
     * ]
     *
     *
     *
     * @param array               $filterArray
     * @param MY_DB_query_builder $db
     */
    public function append_filters($filterArray, &$db) {
        if ($filterArray) {
            if (isset($filterArray[$this->table]))
                $filters = $filterArray[$this->table];
            else
                $filters = $filterArray;
            foreach ($filters as $fK => $fV) {
                if ($fK == "order_by") {
                    if (is_array($fV))
                        $db->order_by($fV['field'], $fV['direction']);
                    else
                        $db->order_by($fV);
                } elseif (is_array($fV)) {
                    foreach ($fV as $fVK => $fVV) {
                        if (is_string($fV) && strtolower($fV) == "condition")
                            $db->where($fVV);
                        elseif (is_string($fV) && strtolower($fV) == "or_condition")
                            $db->or_where($fVV);
                        elseif (is_array($fVV) && strtolower($fVK) == "in")
                            $db->where_in($fK, $fVV);
                        elseif (is_array($fVV) && strtolower($fVK) == "one_of")
                            foreach ($fVV as $fVVV)
                                $db->or_where($fK, $fVVV);
                        elseif (is_array($fVV) && strtolower($fVK) == "or_like")
                            foreach ($fVV as $fVVV)
                                $db->or_like($fK, $fVVV);
                        else
                            $db->or_like($fK, $fVV);
                    }
                } else {
                    if (!empty($fV) || is_numeric($fV))
                        $db->like($fK, $fV);
                }
            }
        }
    }

    /**
     * @deprecated since 2016-12-02 - only because of to avoid 'should be compatible' warning
     * Default Method to get a bunch of items of the type of the model
     *
     * @param array $filter optional
     *
     * @return array|false array of row-items(beans)
     */
    public function get_items($filter = null) {
        if (!is_null($filter) || is_array($filter))
            return $this->get_items2($filter, null);
        else
            return $this->get_items2();
    }

    /**
     * Default Method to get a bunch of items of the type of the model
     *
     * @param array|null  $filter optional
     * @param string|null $select optional
     *
     * @param array       $params
     *
     * @return CI_DB_result
     */
    public function get_items_result(array $filter = [], string $select = null, $params = []) {
        $db = (isset($params['db']))? $params['db'] : $this->db();
        if (isset($params['where']))
            $db->where($params['where']);
        if (!is_null($select))
            $db->select($select);
        if (!is_null($filter) && !empty($filter))
            $this->append_filters($filter, $db);
//        $params = @func_get_arg(2);

//		if(!isset($this->db))
//			return null;
        if (in_array('sort', $this->db_fields))
            $db->order_by('sort', 'ASC');
        elseif (in_array('modified', $this->db_fields))
            $db->order_by($this->table . '.modified', 'DESC');
        elseif (in_array('updated_at', $this->db_fields)) {
            $db->order_by($this->table . '.id', 'DESC');
            $db->order_by($this->table . '.updated_at', 'DESC');
        }

        return $db->get($this->table);
    }

    /**
     * Default Method to get a bunch of items of the type of the model
     *
     * @param array|null  $filter optional
     * @param string|null $select optional
     *
     * @param array       $params
     *
     * @return array|false array of row-items(beans)
     */
    public function get_items2(array $filter = [], string $select = null, $params = []) {
        return $this->get_items_result($filter, $select, $params)->custom_result_object($this->item_class);
    }

    /**
     * Default Method to get a bunch of items of the type of the model as a Collection
     *
     * @param array|null  $filter optional
     * @param string|null $select optional
     *
     * @param array       $params
     *
     * @return DynCollection
     */
    public function get_items3(array $filter = [], string $select = null, $params = []): DynCollection {
        return new DynCollection($this->get_items_result($filter, $select, $params), $this->item_class);
    }


    /**
     * @param int    $id
     * @param string $table
     * @param        third parameter (overloading) params array
     *
     * @return object|void
     */
    public function get_item_by_id($id, $table = null) {
        if (!is_numeric($id))
            return;
        if (is_null($table))
            $table = $this->table;
        $params = @func_get_arg(2);
        $db = (isset($params['db']))? $params['db'] : $this->db();
//        $this->db->select("'$this->table' as _MODEL");
        if (isset($params['select']))
            $db->select($params['select']);

        $query = $db->get_where($table, array($table . '.id' => $id));
        if (isset($params['item_class']) && class_exists($params['item_class']))
            return $query->custom_row_object(0, $params['item_class']);
        else if (isset($this->item_class))
            return $query->custom_row_object(0, $this->item_class);
        else
            return $query->custom_row_object(0, "Item");
    }

    /**
     * @param array  $map    key=>value pairs
     * @param string $select = null
     * @param string $table  = null
     *
     * @return object[]|array
     */
    public function get_items_by_fields(array $map, $select = null, $table = null) {
        if (!is_null($select))
            $this->db()->select($select);
        if (is_null($table))
            $table = $this->table;
        $this->db()->from($table);
        foreach ($map as $k => $v) {
            $this->db()->where([$k => $v]);
        }
        $query = $this->db()->get();
//        return [];
        if ($table == $this->table)
            return $query->custom_result_object($this->item_class);
        else
            return $query->result_object($this->item_class);
    }

    /**
     * @param string $field_name
     * @param string $field_value
     * @param string $table = null
     * @param        fourth parameter (overloading) params array - can handle db, select
     *
     * @return object|Item|mixed
     */
    public function get_item_by_field($field_name, $field_value, $table = null) {
        if (is_null($table))
            $table = $this->table;
        $params = @func_get_arg(3);
        $db = (isset($params['db']))? $params['db'] : $this->db();
        if (isset($params['select']))
            $db->select($params['select']);
        $db->from($table);
        $db->where(array($table . '.' . $field_name => $field_value));
//		$query = $this->db->get_where($table, array($table . '.' . $field_name => $field_value));
        $query = $db->get();
        return $query->custom_row_object(0, $this->item_class);
    }

    /**
     * @param string $field_name
     * @param string $field_value
     * @param string $table = null
     *
     * @return object[]|array|mixed
     */
    public function get_items_by_field($field_name, $field_value, $table = null) {
        if (is_null($table))
            $table = $this->table;
        $query = $this->db()->get_where($table, array($table . '.' . $field_name => $field_value));
        if (isset($this->item_class))
            return $query->custom_result_object($this->item_class);
        return $query->result_object();
    }

    /**
     * Returns array of item(bean)-objects by given object and expected object_type
     * e.g. you have a package with id 5 and you want to get all contents
     *
     * @deprecated since 2017-08-24 use get_items_by_knot2!
     *
     * @param int                      $id
     * @param string                   $object_type
     * @param string|null              $expected_object_type (if null it takes the one of the current model)
     * @param string|null              $select               (if null: * is used in select)
     * @param Knot_model|object|null   $knot_model           object instance of knot_model (null is recomended)
     * @param CI_DB_query_builder|null $db                   object instance of $this->db
     * @param int                      $limit_num
     * @param int                      $limit_from
     *
     * @return array of object items
     */
    public function get_items_by_knot($id, $object_type, $expected_object_type = null, $select = null, $knot_model = null, &$db = null, $limit_num = 0, $limit_from = 0) {
        $q1 = $this->query_get_items_by_knot($id, $object_type, $expected_object_type, $select, $knot_model, $db, $limit_num, $limit_from);
        if (!empty($q1))
            $items = $db->query($q1)->custom_result_object($this->item_class);
        else
            return $q1;
        return $items;
    }

    public function get_items_by_knot2($id, $object_type, $params = []) {
        # defaults
        $expected_object_type = null; $select = null; $knot_model = null; $db = null; $limit_num = 0; $limit_from = 0;
        $where = [];
        extract($params);
        if ($where) {
            $db = (@$db) ? $db : $this->db();
            $db->where($where);
        }

        $q1 = $this->query_get_items_by_knot($id, $object_type, $expected_object_type, $select, $knot_model, $db, $limit_num, $limit_from);
        if (!empty($q1))
            $items = new DynCollection($db->query($q1), $this->item_class);
        else
            return $q1;
        return $items;
    }

    public function query_get_items_by_knot($id, $object_type, $expected_object_type = null, $select = null, $knot_model = null, &$db = null, $limit_num = 0, $limit_from = 0): string {
        $return_type = "query";

        //testing
//        var_dump($id, $object_type, $expected_object_type);

        if (!is_numeric($id))
            return "";
        if (is_null($expected_object_type))
            $expected_object_type = $this->table;
//        ci()->dump([$object_type, $expected_object_type]);
        if (is_null($db)) {
            $db = $this->db();
            $db->reset_query();
            $db->distinct();
        }
//        print_r($db->database."\n");
        if (is_null($knot_model) && ($object_type == "picture" || $expected_object_type == "picture")) {
            $knot_model = ci()->picture_knot_model();
        } elseif (is_null($knot_model) && $object_type == "dogtag") {
            $knot_model = ci()->dogtag_knot_model();
        } elseif (is_null($knot_model)) {
            $knot_model = ci()->knot_model();
        }
        if (is_null($select)) {
            $select = $this->table . ".*";
            # 2017-01-02 try:
//            $db->select($expected_object_type.".id as ".$expected_object_type."_id");
            $db->select("'$expected_object_type' as __MODEL");
        }
        $db->select($select);
        if ($this->table != $knot_model->table)
            $db->from($db->database . "." . $this->table);
        if (is_a($knot_model, "Dogtag_knot_model")) {
            $db->select("dogtag_knots.weight * global_score as score", false);
            $db->select("dogtag_knots.required");
            $db->select("dogtag_knots.page_linked");
            if ($this->table != "dogtag")
                $db->from("dogtag");
            $db->where("dogtag.id = dogtag_knots.dogtag_id");
        } elseif ($expected_object_type == "content") {
            $db->select("content_type_sort");
            $db->order_by("content_type_sort", "asc");
            $db->select("object_object_knots.sort");
            $db->order_by("object_object_knots.sort", "asc");
        } elseif ($object_type === "picture" || $expected_object_type === "picture") {
            $db->select(ci()->picture_knot_model()->table . ".sort");
            $db->order_by(ci()->picture_knot_model()->table . ".sort");
        } else {
            $db->select("object_object_knots.sort");
            $db->order_by("object_object_knots.sort");
        }
        if ($limit_num > 0 && isset($limit_from)) {
            $db->limit($limit_num, $limit_from);
        }

        $knot_array = array($object_type => $id, $expected_object_type => 0);
//		var_dump($knot_array);echo "<br>";
        if ($knot_model->is_valid_array($knot_array)) {
            $knot_model->query_build_join_where($db, $knot_array);
            if ($return_type == "items") {
                $query = $db->get();
                if (isset($this->item_class))
                    return $query->custom_result_object($this->item_class);
                return $query->result_object();
            } elseif ($return_type == "query") {
                return $db->get_compiled_select();
            }
        }
    }

    /**
     * Returns one item(bean)-object by given object and expected object_type
     * e.g. you have a package with id 5 and you want to get all contents
     *
     * @param array           $knot_array
     * @param string|null     $select     (if null: $this->table.* is used in select, if: false "object_object_knots.*" is used)
     * @param Knot_model|null $knot_model object instance of knot_model (null is recomended)
     * @param object|null     $db         object instance of $this->db
     *
     * @return object item
     * @throws Exception
     */
    public function get_item_by_knot_array(array $knot_array, $select = null, $knot_model = null, &$db = null) {
//		if ( !is_numeric($id) )
//			return;
//		if ( is_null($expected_object_type) )
//			$expected_object_type = $this->table;
        if (is_null($knot_model)) {
//            $this->loader()->model("cms/knot_model");
            $knot_model = ci()->knot_model();
        }
        $item_class = $this->item_class;
        if ($select === false) { # only fields from knot-table
            $select = "object_object_knots.*";
            $item_class = $knot_model->item_class;
        } elseif (is_null($select)) # only fields from object-table
            $select = $this->table . ".*,";
        if (is_null($db)) {
            $db = $this->db();
            $db->reset_query();
            $db->distinct();
        }
        $db->select($select);
        $db->from($this->table);
//		$knot_array = array($object_type => $id, $expected_object_type => $expected_object_id);
//		var_dump($knot_array);echo "<br>";
        if ($knot_model->is_valid_array($knot_array)) {
            if (is_a($knot_model, "Knot_model"))
                $knot_model->query_build_join_where($db, $knot_array, $this->table);
            elseif (is_a($knot_model, "Dogtag_knot_model")) {
                /** @var Dogtag_knot_model $knot_model */
                $knot_model->query_build_join_where($db, $knot_array, $this->table);
            }
            $query = $db->get();
            return $query->custom_row_object(0, $item_class);
        }
    }

    public function get_values_for_select($excluded_ids = array()) {
        $items = $this->get_items();
        $items2 = array();
        $items2[] = (object)array("id" => 0, "name" => "");
        foreach ($items as $item)
            if (!in_array($item->id, $excluded_ids))
                $items2[] = (object)array("id" => $item->id, "name" => $item->name);
        return $items2;
    }

    /**
     * @deprecated since 2016-12 because of too-similar name to the codeigniter loader class; please use get_related()
     * Loads linked objects to objects so you can access the knots
     *
     * @param string $expected_object_type eg "picture","content" if item has $item->_MODEL property it will use this for loading knots
     * @param array  $items
     *
     * @throws Exception
     */
    public function load($expected_object_type, &$items) {
        return $this->get_related($expected_object_type, $items);
    }

    /**
     * Loads linked objects to objects so you can access the knots
     * There is no return because the found items are saved in the items-reference
     *
     * @param string              $expected_object_type eg "picture","content" if item has $item->_MODEL property it will use this for loading knots
     * @param array|DynCollection $items
     * @param int                 $limit_num
     * @param int                 $limit_from
     */
    public function get_related(string $expected_object_type, &$items, $limit_num = 0, $limit_from = 0) {

        //test
//        var_dump($expected_object_type);

        if (!is_array($items) && !is_a($items, "DynCollection"))
            die ("\$items is of wrong type! " . __CLASS__ . ":" . __METHOD__);
        if (!isset($this->{$expected_object_type . "_model"})) {
            $this->loader()->model("cms/" . $expected_object_type . "_model");
//			throw new RuntimeException($expected_object_type . "_model" . " is not available in " . get_class($this) . " or " . get_class(get_instance()));
        }
        if (strtolower(get_class($this)) == strtolower($expected_object_type . "_model"))
            die ("Achtung Selbstverlinkung nicht möglich!\n" . var_export(__METHOD__, 1));
        foreach ($items as $k => $item) {
            if ($item->id > 0 && !is_array(@$items[$k]->{$expected_object_type})) {
                if ($expected_object_type == "gallery") # only for code tracing
                    $expected_object_type_model = ci()->gallery_model();
                else
                    $expected_object_type_model = ci()->any_model($expected_object_type);
                $db = $expected_object_type_model->db();
                if (isset($item->__MODEL)) {
//                    $items2 = $this->{$expected_object_type . "_model"}->get_items_by_knot($item->id, $item->__MODEL, $expected_object_type);
                    $items2 = $expected_object_type_model->get_items_by_knot($item->id, $item->__MODEL, $expected_object_type, null, null, $db, $limit_num, $limit_from);
//                    ci()->dump_query($expected_object_type_model->last_query());
//                    var_dump($items2);
                } else {
//                    $items2 = $this->{$expected_object_type . "_model"}->get_items_by_knot($item->id, $this->table, $expected_object_type);
                    $items2 = $expected_object_type_model->get_items_by_knot($item->id, $this->table, $expected_object_type, null, null, $db, $limit_num, $limit_from);
                }
//			if (count($items2) && !isset($item->$expected_object_type))
//				$item->$expected_object_type = array();
                foreach ($items2 as $item2) {
//				var_dump($items[$k]);
                    @$items[$k]->{$expected_object_type}[$item2->id] = $item2;
                }
            } #else { var_dump($item->id);}
        }
    }

    /**
     * Loads linked objects to objects so you can access the knots
     * There is no return because the found items are saved in the items-reference
     *
     * @param string              $expected_object_type eg "picture","content" if item has $item->_MODEL property it will use this for loading knots
     * @param array|DynCollection $items
     *
     * @throws Exception
     */
    public function get_related2(string $expected_object_type, &$items) {
        if (!is_array($items) && !is_a($items, "DynCollection"))
            throw new Exception("\$items is of wrong type! " . __CLASS__ . ":" . __METHOD__);
        if (!isset($this->{$expected_object_type . "_model"})) {
            $this->loader()->model("cms/" . $expected_object_type . "_model");
//			throw new RuntimeException($expected_object_type . "_model" . " is not available in " . get_class($this) . " or " . get_class(get_instance()));
        }
        if (strtolower(get_class($this)) == strtolower($expected_object_type . "_model"))
            throw new Exception ("Achtung Selbstverlinkung nicht möglich!");
        foreach ($items as $k => $item) {
            if ($item->id > 0) {
                if ($expected_object_type == "gallery") # only for code tracing
                    $expected_object_type_model = ci()->gallery_model();
                else
                    $expected_object_type_model = ci()->any_model($expected_object_type);
                $db = $expected_object_type_model->db();
                $params = [
                    'db' => $db,
                    'join_to_type' => true,
                ];
                if (isset($item->__MODEL)) {
                    $items2 = $expected_object_type_model->get_items_by_knot2($item->id, $item->__MODEL, $params);
                } else {
                    $items2 = $expected_object_type_model->get_items_by_knot2($item->id, $this->table, $params);
                }
                @$items[$k]->{$expected_object_type} = $items2;
            } #else { var_dump($item->id);}
        }
    }

    /**
     * @param int    $my_id       first-object-id
     * @param int    $object_id   id of second-object
     * @param string $object_type type of second-object
     *
     * @return mixed return of 'insert_knot()'-Methods
     * @throws Exception
     */
    public function assign_to_object($my_id, $object_id, $object_type) {
//	    var_dump(array(
//	        $my_id,
//            $object_id,
//            "ot" => $object_type,
//            $this->linked_by_knot,
//            in_array($object_type, array_values($this->linked_by_knot)),
//        ));
        if ($object_type == "dogtag") {
            $this->load->model("cms/dogtag_knot_model");
            return $this->dogtag_knot_model->insert_knot(array($this->table => $my_id, $object_type => $object_id));
        } elseif (in_array($object_type, $this->linked_by_knot)) {
            $this->load->model("cms/knot_model");
            return $this->knot_model->insert_knot(array($this->table => $my_id, "$object_type" => $object_id));
        } else
            MY_Controller::get_instance()->dump("Fehler: " . __CLASS__ . ":" . __METHOD__ . " Verknüpfung von $this->table zu $object_type nicht erlaubt!");
//			throw new Exception(__CLASS__.":".__METHOD__);
    }

    /**
     * @param int|stdClass $item_or_id field for "id-field" in db/table | if is stdClass there have to be an id-property in the object
     * @param string|null  $table      for table-name if null it uses $this->table (this is your current model)
     *
     * @return bool|void
     */
    public function delete_item($item_or_id, $table = null) {
        if ($this->read_only)
            return false;
        if (!is_numeric($item_or_id) && !is_object($item_or_id) && !is_array($item_or_id))
            return;
        if (!is_numeric($item_or_id) && isset($item_or_id->id))#id is item
            $id = $item_or_id->id;
        if (is_numeric($item_or_id))
            $id = $item_or_id;
        if (is_null($table))
            $table = $this->table;
        $this->db->reset_query();
        if ($id)
            $this->db->where('id', $id);
        else {
            $this->db->where((array)$item_or_id);
        }
        return $this->db->delete($table);
    }

    /**
     * Only to delete content-element-knots
     *
     * @param array               $map
     * @param string|null         $table
     * @param MY_DB_query_builder $db
     *
     * @return bool
     */
    public function delete_knot(array $map, string $table = null, $db = null): bool {
        if (!is_array($map))
            return false;
        if (count($map) != 2 && (count(array_keys($map)) == count(array_values($map))))
            return false;
        if (is_null($table))
            $table = $this->table;
        if (is_null($db))
            $db = $this->db();
        ksort($map);
        $keys = array_keys($map);
        $vals = array_values($map);
        if (($keys[0] == "content" && $keys[1] == "element") ||
            ($keys[0] == "content_id" && $keys[1] == "element_id")
        ) {
//            $this->db()->reset_query();
            $db->where('content_id', $vals[0]);
            $db->where('element_id', $vals[1]);
            return $db->delete($table);
        } elseif (($keys[0] == "dogtag" && $table == "dogtag_knots") ||
            ($keys[0] == "dogtag_id" && $table == "dogtag_knots")
        ) {
            $db->where('dogtag_id', $vals[0]);
            $db->where('object_type', $keys[1]);
            $db->where('object_id', $vals[1]);
            return $db->delete($table);
        } else {
            $db->where('object1_id', $vals[0]);
            $db->where('object1_type', $keys[0]);
            $db->where('object2_id', $vals[1]);
            $db->where('object2_type', $keys[1]);
            return $db->delete($table);
        }

    }

    public function get_knot_model(array $map) {
        ksort($map);
        list($object1_type, $object2_type) = array_keys($map);
        if ($object1_type == "dogtag" || $object2_type == "dogtag") {
            $knot_model = ci()->dogtag_knot_model();
        } elseif ($object1_type == "content" && $object2_type == "element") {
            $knot_model = ci()->content_element_knots_model();
        } elseif (ci()->knot_model()->is_valid_array($map)) {
            $knot_model = ci()->knot_model();
        }
        if (isset($knot_model))
            return $knot_model;
    }

    /**
     * Insert Row to database AND insert knot
     *
     * @param array  $item
     * @param string $table
     * @param bool   $return_bool
     *
     * @return array|bool array(result=>true|false, id=>int, knot=>)
     */
    public function insert_item($item = null, $table = null, $return_bool = true) {
        if (is_object($item))
//            $data = (array)$data;
            # change to object to avoid accessing protected properties
            $data = clone $item;
        else
            $data = (object)$item;
        if ($this->read_only)
            return false;
        $this->load->helper('url');

        if (is_null($table))
            $table = $this->table;
//        $slug = url_title($this->input->post('title'), 'dash', TRUE);
        if (is_null($data)) {
            $dataIn = $this->input->get_post("data");
            $data = @$dataIn[$table][0];
        }
        if ($data) {
            if (!(isset($this->inserts_preserve_ids) && $this->inserts_preserve_ids)) {
                if (is_array($data))
                    unset($data['id']);
                if (is_object($data))
                    unset($data->id);
            }
            # wenn id frei, nehme Paket-Nummer bzw. Hotel-Nummer
            if (empty($data->id) && (isset($data->package_number) || isset($data->hotel_number))) {
                $number = (@$data->package_number) ? $data->package_number : ((@$data->hotel_number) ? $data->hotel_number : false);
                if ($this->table == "hotel")
                    $prefix = "110";
                elseif ($this->table == "package" && $data->brand == "SMR")
                    $prefix = "120";
                elseif ($this->table == "package" && $data->brand == "CW")
                    $prefix = "130";
                $o = $this->db()->query("SELECT * FROM $table where id = '" . addslashes($prefix . $number) . "'")->row_object();
                if (empty($o)) { # wenn id frei, nehme Paket-Nummer bzw. Hotel-Nummer
                    $data->id = $prefix . $number;
                }
            }

            $dataForeignKeys = (is_object($data)) ? @$data->foreign_keys : @$data['foreign_keys'];
            if (is_object($data)) {
                if (!is_a($data, "Item")) {
                    unset($data->foreign_keys);
                    unset($data->__MODEL);#protected
                }
            } else {
                unset($data['foreign_keys']);
                unset($data['__MODEL']);
            }

            //TODO: Datentyp Timestamp als NULL speichern, wenn "" oder "0000-00-00 00:00:00"
            foreach ($data as $k => $v) {
                if (empty($v) || $v == "0000-00-00 00:00:00") {
                    $data_type = @$this->get_db_schema($k, $table)->DATA_TYPE;
                    if ((empty($v) || $v == "0000-00-00 00:00:00") && $data_type == "timestamp") {
                        $this->db->set($k, "NULL", false);
                        if (is_object($data))
                            unset($data->$k);
                        else
                            unset($data[$k]);
                    }
                }
                if (strstr($k, " ")) {
//                    var_dump($k);
//                    $this->db->set(''.addslashes($k), $v, false);
                    $this->db->set('`' . addslashes($k) . '`', $v, false);
                    if (is_object($data)) unset($data->$k); else unset($data[$k]);
                }
            }
            if ((is_object($data) && empty($data->created) || is_array($data) && empty($data['created'])))
                $this->db->set('created', 'NOW()', FALSE);
            if ((is_object($data) && empty($data->modified) || is_array($data) && empty($data['modified'])))
                $this->db->set('modified', 'NOW()', FALSE);

            if (in_array("uuid", $this->db_fields) && !@$data->uuid)
                $this->db->set("uuid", 'UUID()', FALSE);
            if (in_array("creator", $this->db_fields) && !@$data->creator && ci()->get_username())
                $this->db->set("creator", ci()->get_username());
            if (in_array("modifier", $this->db_fields) && !@$data->modifier)
                $this->db->set("modifier", ci()->get_username());
            try {
                $returnDB = $this->db->insert($this->db->database . "." . $table, $data);
                $error = (!$returnDB) ? $this->db->error() : null;
            } catch (Exception $e) {
                var_dump($e);
            }
            $new_id = $this->db->insert_id();
            if (is_object($item) && empty($item->id))
                $item->id = $new_id;
            elseif (is_array($item) && empty($item['id']))
                $item['id'] = $new_id;

            //link to Object
            if ($dataForeignKeys) {
                foreach ($dataForeignKeys as $k => $v) {
                    $r_knot = $this->assign_to_object($new_id, $v, $k);
                }
//				$data_insert = array();
//				$data_insert['knot']['object'] = $dataForeignKeys;
//				$data_insert['knot']['object'][$table] = $this->db->insert_id();
//				$r_knot = $this->knot_model->insert_knot($data_insert['knot']['object']);
            }
            if ($return_bool)
                return $returnDB;
            else
                return array("result" => $returnDB, "id" => $new_id, "knot" => @$r_knot, "error" => $error);
        }
        return false;
    }

    /**
     * @param null $data
     * @param null $table
     * @param bool $return_int = true - if false you get true or false if true an integer (or false)
     *
     * @return int|array|bool|mixed
     */
    public function insert_item2($data = null, $table = null, $return_int = true) {
        $r = $this->insert_item($data, $table, false);
        if (is_object($data) && !isset($data->id))
            $data->id = $r['id'];
        if (!$r['result'])
            return $r['result'];
        elseif ($return_int)
            return $r['id'];
        else
            return $r;
    }

    /**
     * @param array|object $item
     * @param string       $table
     * @param array        $where_map especially usefull for knots e.g. ['dogtag_id' => 123, 'object_type' => 'package', 'object_id' => 234]
     *
     * @return bool
     */
    public function update_item($item = null, $table = null, $where_map = null): bool {
        # create array AND copy of item to allow unsetting of properties which should not be saved
        $data = (is_object($item)) ? clone $item : (array)$item;
//        $data = $item;
        /** @var MY_DB_query_builder $db */
        $db = $this->db;
        if ($this->read_only)
            return false;
        if (is_null($data)) {
            $data = $this->input->post($this->table);
        }
        if (is_null($table))
            $table = $this->table;
        $db->reset_query();

        if (
            (is_object($item) && in_array($item->modified, ["noupdate", "no-update"]))
            || (is_array($item) && in_array(@$item['modified'], ["noupdate", "no-update"]))
        ) {
            # unsetting is done later;
        } else {
            $db->set('modified', 'NOW()', FALSE);
            if (in_array("modifier", $this->db_fields))
                $db->set("modifier", ci()->get_username());
        }


        //TODO: Datentyp Timestamp als NULL speichern, wenn "" oder "0000-00-00 00:00:00"
        foreach ($data as $k => $v) { # protected properties will not be written
            $unset_current_property = false;
            if ($v === "noupdate" || $v === "no-update")
                $unset_current_property = true;
            if (in_array($v, ['now()', "NOW()", 'now', "NOW"], true)) {
                $db->set($k, 'NOW()', FALSE);
                $unset_current_property = true;
//                if (is_array($data)) unset($data[$k]);
//                if (is_object($data)) unset($data->$k);
            }
            if (empty($v) || $v == "0000-00-00 00:00:00") {
                $data_type = @$this->get_db_schema($k, $table)->DATA_TYPE;
                if (empty($data_type)) {
                    echo "<pre>\n Unknown Field:\n";
                    var_dump($k, $table, $this->db()->last_query(), $data, $data_type);
                    echo "</pre>";
                    die;
                }
                # if empty AND timestamp use null instead of date
                if (((empty($v) && !is_numeric($v)) || ($v == "0000-00-00 00:00:00")) && $data_type == "timestamp") {
                    $db->set($k, "NULL", false);
                    $unset_current_property = true;
//                    if (is_array($data))
//                        unset($data[$k]);
//                    if (is_object($data))
//                        unset($data->$k);
                }
            }
            if ($unset_current_property) {
                if (is_array($data)) unset($data[$k]);
                if (is_object($data)) unset($data->$k);
            }
        }

        if (!empty($where_map) && is_array($where_map)) {
            foreach ($where_map as $k => $v) {
                $db->where($k, $v);
                if (is_array($data)) unset($data[$k]);
                if (is_object($data)) unset($data->$k, $data->id);
            }
        } else {
            $id = (is_object($data)) ? $data->id : $data['id'];
            $db->where('id', $id);
        }
//        if (is_array($data)) unset($data['__MODEL'], $data['id']);
//        if (is_object($data)) unset($data->__MODEL, $data->id);
        $r = $db->update($table, $data);
        if (is_a($item, "Item"))
            $item->_updated = $r;
        return $r;
    }

    /**
     * @deprecated 2016-09 - use save_items() now!
     *
     * @return array of result bools
     */
    public function save_item() {
        if ($this->read_only)
            return false;
//        get_instance()->message("Deprecated! ".__CLASS__.":".__METHOD__);
        return $this->save_items();
    }

    /**
     * saves data[<modelname>][id] data (from input) to database
     *
     * @param array|null $data
     *
     * @return array|bool unqualified indexed array (0,1,2,3) of save-results
     */
    public function save_items($data = null) {
        if ($this->read_only)
            return false;
        $data = (is_null($data))? $this->input->post_get('data') : $data;
        $dataIn1 = $data[$this->table];
        foreach ($dataIn1 as $k => $data2) {
            $r[] = $this->save_one_item2($data2);
        }
        return $r;
    }

    /**
     * saves one item to database
     * you should overwrite this method in the model-class and use save_one_item2()
     *
     * @deprecated since 2016 - use save_one_item2()
     *
     * @param $data
     *
     * @return bool
     */
    public function save_one_item($data) {
//        get_instance()->message("Called: ".__CLASS__.":".__METHOD__);
        return $this->save_one_item1($data);
    }

    /**
     * @deprecated 2016-07-25
     *
     * save data-array to database but does not handle foreign_keys
     *
     * @param $data
     *
     * @return bool
     */
    public function save_one_item1($data) {
//        get_instance()->message("Called: ".__CLASS__.":".__METHOD__);
        if ($this->read_only)
            return false;
        if ((is_array($data) && $data['id'] > 0) || (is_object($data) && $data->id > 0)) {
            $r = $this->update_item($data);
        } else
            $r = $this->insert_item($data);
        return $r;
    }

    /**
     * saves data-array for one row to database
     * and save foreign-keys (data['foreign_keys'][<table>] => <id>)
     *
     * @param array  $data array for single item
     * @param string $table
     *
     * @return array|bool [success|id]
     */
    public function save_one_item2($data, $table = null) {
        $db = $this->db;
//        if (is_object($data))
//            $data = (array)$data;
//        get_instance()->message("Called: ".__CLASS__.":".__METHOD__);
        if ($this->read_only)
            return false;
        if ((is_object($data) && !empty($data->foreign_keys))
            || (is_array($data) && @$data['foreign_keys'])
        ) {
            $foreign_keys = (is_object($data)) ? $data->foreign_keys : $data['foreign_keys'];
            if (is_object($data) && !is_a($data, "Item")) ;
            unset($data->foreign_keys);
            if (is_array($data))
                unset($data['foreign_keys']);#because it should not be saved directly
        }
//		MY_Controller::get_instance()->dump($foreign_keys, "foreign_keys");

        if ((is_object($data) && $data->id > 0)
            || (is_array($data) && @$data['id'] > 0)
        ) { #update existing item
            $my_id = (is_object($data)) ? $data->id : $data['id'];
            $r['success'] = $this->update_item($data, $table);
            $r['id'] = $my_id;
            $r[$this->table][$my_id]['type'] = 'update';
        } else { # insert new item
            $r['success'] = $this->insert_item($data, $table);
            if ($r['success']) {
                $my_id = $r['id'] = $db->insert_id();
                $r[$this->table][$my_id]['type'] = 'insert';
            }
        }
        if ($my_id) {
            $r[$this->table][$my_id]['success'] = $r['success'];
            if (isset($foreign_keys)) {
                foreach ($foreign_keys as $object_type => $object_id) {
                    if (method_exists($this, 'assign_to_object')) {
                        $knot_result = $this->assign_to_object($my_id, $object_id, $object_type);
                        $knot_item = $knot_result['knot_item'];
                        $r[$this->table][$my_id]['knot'][$object_type] = $knot_item;
                        $r[$this->table][$my_id]['foreign_keys'][$object_type] = $object_id;
                    } else
                        ci()->message(get_class($this) . ":" . 'assign_to_object' . "-Method is missing.");
                }
            }
        }
        $r['last_query'] = $this->last_query();
        return $r;
    }

    /**
     * applies db-schema (data types) from database to field-Array
     *
     * @param array  $fields_2D to set types and values
     * @param string $table     optional table get schema from
     *
     * @return bool
     */
    public function apply_schema(&$fields_2D, $table = null) {
        if (@ci()->no_overhead)
            return false;
//		get_instance()->dump($fields);
//		var_dump($fields);
//		var_dump(array_keys($fields));
//		var_dump(array_values($fields));
        if (is_null($table))
            $table = $this->table;
        foreach ($fields_2D as $fK => $fV) {
            $schema = $this->get_db_schema($fK, $table);
            if (!is_object($schema)) {#occures when field is not in table eg. foreign_key-field or mis-typing
                continue;
            }
            $this->apply_shema_for_column($schema, $fields_2D, $fK);
        }
        if ($schema)
            return true;
    }

    public function apply_shema_for_column($schema, &$fields, $fK) {
        $fields[$fK]['type'] = (isset($fields[$fK]['type'])) ? $fields[$fK]['type'] : $schema->DATA_TYPE;
        if (!isset($fields[$fK]['COLUMN_COMMENT']))
            $fields[$fK]['COLUMN_COMMENT'] = (!empty($schema->COLUMN_COMMENT)) ? "" . $schema->COLUMN_COMMENT . "" : "";
        if ($schema->DATA_TYPE == "enum") {
            $fields[$fK]['values'] = $this->get_enum_values($fK, $schema);
        } elseif (in_array($schema->DATA_TYPE, array("varchar", "int", "tinyint")))
            $fields[$fK]['limit'] = isset($fields[$fK]['limit']) ?
                $fields[$fK]['limit'] : intval(str_replace(array($schema->DATA_TYPE . "(", ")"), array("", ""), $schema->COLUMN_TYPE));
    }

    /**
     * @param string|null $column_name
     * @param string|null $table
     *
     * @return object|mixed
     */
    public function get_db_schema($column_name = null, $table = null) {
        /** @var MY_DB_query_builder $db */
        $db = $this->db();
        if (is_null($table))
            $table = $this->table;
        if (!isset($this->db_schema) || !isset($this->db_schema[$column_name])) {
            $column_q = ($column_name) ? "COLUMN_NAME = '$column_name'" : "1";
            $q = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT, COLUMN_TYPE, TABLE_SCHEMA, TABLE_NAME  FROM INFORMATION_SCHEMA.COLUMNS WHERE " .
                "TABLE_SCHEMA = '" . $db->database . "' AND" . " TABLE_NAME = '$table' AND " . $column_q . "\n";
            $query = $db->query($q);
            if ($column_name) {
                $this->db_schema[$column_name] = $query->row_object();
            } else {
                $this->db_schema[$column_name] = $query->result_object();
            }
        }
        return $this->db_schema[$column_name];
    }

    public function get_enum_values($column_name, $schema = null) {
        if (is_null($schema))
            $schema = $this->get_db_schema($column_name);
        if ($schema->DATA_TYPE != "enum")
            return FALSE;
        $types = str_replace(array("enum('", "')"), array("", ""), $schema->COLUMN_TYPE);
        $types_arr = explode("','", $types);
        $items = array();
        foreach ($types_arr as $k => $v) {
            $items[] = (object)array("id" => $v, "name" => $v);
        }
        return $items;
    }

    /**
     *
     * @return \Empty_Silent_Item Object so you can use a edit-form for create-action without 'undefindd-property'-warnings.
     */
    public function get_empty_item() {
        return new Empty_Silent_Item();
    }

    /**
     * Set some default values to use $output[] = $this->get_view("cms/list_view", $data);
     *
     * @param Collection|array $items
     * @param int              $active_id
     * @param array            $data
     *
     * @return array
     */
    public function get_data_for_list($items, int $active_id = 0, array &$data = null): array {
        if (is_null($data))
            $data = [];
        $data['box_index']['create_params'] = @$data['create_params'];
        $data['box_index']['modelname'] = $this->table;
        $data['box_index']['controllername'] = $this->full_controller_name;
//        $data['box_index']['title'] = "Liste " . $this->item_label;
        $data['box_index']['title'] = $this->item_label;
        $data['items'] = $data['box_index']['items'] = $items;
        $data['box_index']['active_id'] = (int)$active_id;
//        $data['box_index']['hide_actions'] = false;
//        $data['box_index']['list_fields'] = $this->list_fields;
//        $data['box_index']['list_fields_2D'] = $this->list_fields_2D;
        return $data;
    }
}