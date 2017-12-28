<?php

/**
 * User: sbraun
 * Date: 19.07.17
 * Time: 14:11
 */
trait MY_Controller_Links
{
    /**
     * @return MY_Controller
     */
    public static function &get_instance(): MY_Controller {
        return parent::get_instance();
    }
    /**
     * shorthand for get_instance();
     * @deprecated because never used and ci() is more easy
     * @return MY_Controller
     */
    public static function &_():MY_Controller {
        return self::get_instance();
    }

    /**
     * @param string $object_type like "content","element","package"
     * @return MY_Model instance of a Model e.g. return this->content_model
     */
    public function any_model(string $object_type): MY_Model {
        if (method_exists($this, $object_type."_model"))
            return $this->{$object_type."_model"}();#otherwise use call_user_func()
        elseif (is_object($this->{$object_type . "_model"}))
            return $this->{$object_type . "_model"};
        die("Please load " . $object_type . "_model first");
    }
    # libs
    public function bootstrap_lib():Bootstrap_lib{$this->load->library("bootstrap_lib");return $this->bootstrap_lib;}
    public function google_analytics_lib():Google_analytics_lib{$this->load->library("google_analytics_lib");return $this->google_analytics_lib;}
    public function controller_helper_lib():Controller_helper_lib{$this->load->library("controller_helper_lib");return $this->controller_helper_lib;}
    public function topic_helper_lib():Topic_helper_lib{$this->load->library("topic_helper_lib");return $this->topic_helper_lib;}
    public function element_lib():Element_lib{$this->load->library("element_lib");return $this->element_lib;}
    public function media_lib():Media_lib{$this->load->library("media_lib");return $this->media_lib;}
    # cms-models
    public function api_model():Api_model{$this->load->model("api/api_model");return $this->api_model;}
    public function cached_json_model():Cached_json_model{$this->load->model("api/cached_json_model");return $this->cached_json_model;}
    public function contact_model():Contact_model{$this->load->model("cms/contact_model");return $this->contact_model;}
    public function content_model():Content_model{$this->load->model("cms/content_model");return $this->content_model;}
    public function content_type_model():Content_type_model{$this->load->model("cms/content_type_model");return $this->content_type_model;}
    public function content_element_knots_model():Content_element_knots_model{$this->load->model("cms/content_element_knots_model");return $this->content_element_knots_model;}
    public function dogtag_knot_model():Dogtag_knot_model{$this->load->model("cms/dogtag_knot_model");return $this->dogtag_knot_model;}
    public function dogtag_model():Dogtag_model{$this->load->model("cms/dogtag_model");return $this->dogtag_model;}
    public function element_model():Element_model{$this->load->model("cms/element_model");return $this->element_model;}
    public function element_type_model():Element_type_model{$this->load->model("cms/element_type_model");return $this->element_type_model;}
    public function event_model():Event_model{$this->load->model("cms/event_model");return $this->event_model;}
    public function gallery_model():Gallery_model{$this->load->model("cms/gallery_model");return $this->gallery_model;}
    public function hotel_attribute_model():Hotel_attribute_model{$this->load->model("cms/hotel_attribute_model");return $this->hotel_attribute_model;}
    public function hotel_service_model():Hotel_service_model{$this->load->model("cms/hotel_service_model");return $this->hotel_service_model;}
    public function knot_model():Knot_model{$this->load->model("cms/knot_model");return $this->knot_model;}
    public function picture_knot_model():Picture_knot_model{$this->load->model("cms/picture_knot_model");return $this->picture_knot_model;}
    public function location_model():Location_model{$this->load->model("cms/location_model");return $this->location_model;}
    public function offerer_model():Offerer_model{$this->load->model("cms/offerer_model");return $this->offerer_model;}
    public function old_picture_model():Old_picture_model{$this->load->model("cms/picture_model");$this->load->model("cms/old_picture_model");return $this->old_picture_model;}
    public function old_content_import_model():Old_content_import_model{$this->load->model("cms/old_content_import_model");return $this->old_content_import_model;}
    public function hotel_model():Hotel_model{$this->load->model("cms/hotel_model");return $this->hotel_model;}
    public function package_model():Package_model{$this->load->model("cms/package_model");return $this->package_model;}
    public function picture_model():Picture_model{$this->load->model("cms/picture_model");return $this->picture_model;}
    public function dms_picture_model():Dms_picture_model{$this->load->model("import/dms_picture_model");return $this->dms_picture_model;}
    public function producer_model():Producer_model{$this->load->model("cms/producer_model");return $this->producer_model;}
    public function redirect_model():Redirect_model{$this->load->model("cms/redirect_model");return $this->redirect_model;}
    public function staticpage_model():Staticpage_model{$this->load->model("cms/staticpage_model");return $this->staticpage_model;}
    public function topic_model():Topic_model{$this->load->model("cms/topic_model");return $this->topic_model;}
    public function metatag_model():Metatag_model{$this->load->model("cms/metatag_model");return $this->metatag_model;}
    public function publisher_model():Publisher_model{$this->load->model("export/publisher_model");return $this->publisher_model;}
    public function cache_manager_model():Cache_manager_model{$this->load->model("export/cache_manager_model");return $this->cache_manager_model;}
    # search
    public function search_model():Search_model{$this->load->model("search/search_model");return $this->search_model;}
    # loader
    public function loader():MY_Loader{return $this->load;}
}

function &ci(): MY_Controller {
    return MY_Controller::get_instance();
}
