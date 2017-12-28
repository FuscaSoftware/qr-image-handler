<?php

/**
 * Description of MY_Model
 *
 * CodeIgniter Methods:
 * $this->db->return_query();
 * $this->db->last_query();
 * $this->db->get_compiled_select()
 * $this->db->get_compiled_insert()
 * $this->db->get_compiled_update()
 *
 *
 * @property Model_helper_lib model_helper_lib
 * @property MY_Loader load
 * @property MY_DB_query_builder db
 * @property MY_DB_query_builder $db
 * @author sebra
 */
require_once APPPATH . "/core/traits/MY_Model_Trait.php";
use Model_helper_lib as MH;
class MY_Model extends CI_Model
{
    use MY_Model_Trait;
}
require_once APPPATH . "/core/classes/Item.php";
require_once APPPATH . "/core/classes/Map.php";
require_once APPPATH . "/core/classes/Collection.php";
require_once APPPATH . "/core/classes/DynCollection.php";
