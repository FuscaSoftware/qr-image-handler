<?php

/**
 * User: sbraun
 * Date: 02.01.17
 * Time: 15:33
 */
class MY_Loader extends CI_Loader
{
    /**
     * sb: fucking dirty overload to get MY_DB_query_builder running
     *
     * Database Loader
     *
     * @param    mixed $params Database configuration options
     * @param    bool $return Whether to return the database object
     * @param    bool $query_builder Whether to enable Query Builder
     *                    (overrides the configuration setting)
     *
     * @return    object|bool    Database object if $return is set to TRUE,
     *                    FALSE on failure, CI_Loader instance in any other case
     */
    public function database($params = '', $return = FALSE, $query_builder = NULL) {
        // Grab the super object
        $CI =& get_instance();

        // Do we even need to load the database class?
        if ($return === FALSE && $query_builder === NULL && isset($CI->db) && is_object($CI->db) && !empty($CI->db->conn_id)) {
            return FALSE;
        }

//        require_once(BASEPATH.'database/DB.php');
        require_once(APPPATH . 'core/DB.php');

        if ($return === TRUE) {
            return DB($params, $query_builder);
        }

        // Initialize the db variable. Needed to prevent
        // reference errors with some configurations
        $CI->db = '';

        // Load the DB class
        $CI->db =& DB($params, $query_builder);
        return $this;
    }


}