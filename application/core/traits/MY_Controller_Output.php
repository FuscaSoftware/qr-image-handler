<?php

/**
 * User: sbraun
 * Date: 19.07.17
 * Time: 14:16
 */
trait MY_Controller_Output
{

    /**
     * Loads a view to memory, so you can put it everywhere (and returns its content)
     * To use the return of this function e.g. you can use $this->show_page()
     * @note needs $data in $this->data
     *
     * @param string $view path to view e.g. index_view or cms/content/index_view
     * @param array  $data
     *
     * @return string the parsed content of the view (no echo/output!)
     */
    public function get_view(string $view, $data = null): string {
        if (is_null($data)) {
            if (is_null(@$this->data))
                $this->data = [];
            $data = $this->data;
        }
        return $this->loader()->view($view, $data, true);
    }

    /**
     * outputs the page (header-output-footer),
     * typically the last step in a Controller->action()-method
     * you can use this in combination with one or multiple ..
     * $output[] = get_view('template');
     * .. calls.
     *
     * @param string|array $output (content of the page)
     */
    public function show_page($output = "") {
        return $this->show_page2($output);
    }

    public function show_page1($output = "") {
        if (is_array($output)) {
            $data['output'] = implode("\n", $output);
        } elseif (is_string($output)) {
            $data['output'] = $output;
        }
        if (!isset($this->data))#avoid error
        {
            $this->data = array();
        }
        if ($this->request_type == "iframe") {
//            $this->show_menu = false;
            $this->show_sidebar = false;
            $this->show_nav = false;
            $this->load->view($this->default_header, $this->data);
//            $this->load->view('common/header_iframe', $this->data);
            $this->load->view("output_view", $data);
            $this->load->view($this->default_footer, $this->data);
        } else {
            $this->load->view($this->default_header, $this->data);
            $this->load->view("output_view", $data);
            $this->load->view($this->default_footer, $this->data);
        }
    }

    public function show_page2($output, $vars = []) {
        if ($this->request_type == "iframe") {
//            $this->show_menu = false;
            $this->show_sidebar = false;
            $this->show_nav = false;
        }
        $vars['main_content'] = (is_array($output))? implode("\n", $output) : $output;
        $this->load->view("common/layout2017", $vars);
    }

    /** @deprecated use _set_cache_header() */
    public function set_cache_header($time = null) {
        return $this->_set_cache_header($time);
    }

    /**
     * sets header for cache/expire...
     *
     * @param int|null $time in s null is for default (0)
     */
    public function _set_cache_header($time = null) {
        if (is_null($time))
            $time = 0 * (60 * 60) * 2; # irgendwie fehlt hier eine Stunde, daher 2h anstatt nur einer. Falsche Zeit in PHP?!
        else
            $time = $time + (60 * 60) * 1;
        if ($time > 0) {
            $cache_length = $time;
            $cache_expire_date = gmdate("D, d M Y H:i:s", time() + $cache_length) . " GMT";
            header("Expires: $cache_expire_date");
            header("Pragma: cache");
            header("Cache-Control: max-age=$cache_length");
            header("User-Cache-Control: max-age=$cache_length");
        } else {
            $cache_length = $time;
            $cache_expire_date = gmdate("D, d M Y H:i:s", time() + $cache_length) . " GMT";
            header("Expires: $cache_expire_date");
            header("Pragma: no-cache");
            header("Cache-Control: max-age=$cache_length");
            header("User-Cache-Control: max-age=$cache_length");
        }
    }


    /**
     * outputs json result
     * typically the last step in a Controller->action()-method
     *
     * @param array $data expects array which is given to the output as json
     * @param bool  $return
     *
     * @return object|string
     */
    public function show_ajax($data = null, $return = false) {
        if (is_null($data)) {
            $data = $this->data;
        }
        if (headers_sent())
            die("headers already sent!");
        $data2['data'] = $data;
        if (is_array($data))
            $this->_set_cache_header(@$data['cache']);
        elseif (is_object($data))
            $this->_set_cache_header(@$data->cache);
        return $this->load->view("ajax/json_view", $data2, ($return) ? true : false);
    }

    /**
     * shorthand / alias for show_ajax() with inlcluded show_messages() after $this->message,
     * if the message sould send by ajax to the client.
     *
     * @param array $data expects array which is diven to the output as json
     * @param bool  $return
     *
     * @return object|string
     */
    public function show_ajax_message(&$data = null, $return = false) {
        if (is_null($data)) {
            $data = &$this->data;
        }
        $data['html']['prepend']['.message_area'] = $this->bootstrap_lib()->show_messages(null, null);
        return $this->show_ajax($data, $return);
    }

    public function show_modal($data = null) {
        if (is_null($data)) {
            $data = &$this->data;
        }
        $data2['data'] = $data;
        $output = $this->get_view("boxes/modal/modal", $data);
        $data_json['html']['prepend']['body'] = $output;
        $data_json['html']['prepend']['.message_area'] = $this->bootstrap_lib()->show_messages(null, null);
        $this->show_ajax($data_json);
    }

    public function getTemplateDir() {
        if (!is_null($this->templateDir)) {
            return $this->templateDir;
        }
        $class = get_class($this);
        return strtolower($class);
    }


    /**
     *
     * @param string $text        Message-Text
     * @param string $type        info|warning|danger|success
     * @param string $context     default
     * @param int    $time_out_ms time in ms, after which the message should disappear automatically
     * @param string $title       its a leading word which is set into <strong>-Tag in the message
     *
     * @link https://getbootstrap.com/components/#alerts Bootstrap
     */
    public function message($text, $type = "info", $context = "default", $time_out_ms = 0, $title = "") {
        if (is_null($context)) {
            $context = "default";
        }
        $this->load->library("bootstrap_lib");
//		$this->data['messages'][$context][] = (object) array("text" => $text, 'type' => $type);
        $this->data['messages'][$context][] = new Bootstrap_Message_Object($text, $type, $time_out_ms, $title);
//		$GLOBALS['messages'][$context][] = (object) array("text" => $text, 'type' => $type);
//		$_SESSION['messages'][$context][] = (object) array("text" => $text, 'type' => $type, "time_out_ms" => $time_out_ms);
        $_SESSION['messages'][$context][] = new Bootstrap_Message_Object($text, $type, $time_out_ms, $title);
    }

    /**
     * dumps any array or object into an alert info (message)
     * if you want to see this dump after an ajax-request use show_ajax_message() instead of only show_ajax()
     *
     * @param var    $var
     * @param string $title
     */
    public function dump($var, $title = null) {
        return $this->message("<pre>" . var_export($var, 1) . "</pre>", "info", "default", 0, $title);
    }

    /**
     * dumps a string into an alert info (message)
     * if you want to see this dump after an ajax-request use show_ajax_message() instead of only show_ajax()
     *
     * @param string $var
     * @param string $title
     */
    public function dump_query($var, string $title = null) {
        if (is_string($var))
            return $this->message("<pre>" . $var . "</pre>", "info", "default", 0, $title);
        else
            return $this->dump($var, $title);
    }
}