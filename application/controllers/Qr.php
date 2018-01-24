<?php
/**
 * User: sbraun
 * Date: 16.01.18
 * Time: 18:29
 */

class Qr extends MY_Controller
{
    public $default_helpers = [];
    public $default_libraries = [];
    public $auth_ldap_status = false;
    public $no_overhead = true;
    public $default_qr_px_size = 6;
    public $default_qr_color = ['black', [0,0,0]];
    public $color = null;

    public function index($param1 = null) {
        var_dump($param1);
    }

    public function __construct() {
        parent::__construct();
    }

    public function _prepare($uuid) {
        if (!$uuid)
            throw new Exception('No UUID given! :(');
        $this->load->model('qrcode_model');
        require_once(APPPATH . "/third_party/TCPDF/tcpdf_barcodes_2d.php");
        $this->color[0] = ($this->input->get('color')) ?: $this->default_qr_color[0];
        if ($this->color[0] !== 'black') {
            if ($this->color[0] === 'grey')
                $this->color[1] = [176, 176, 176];
        }

    }

    /**
     * @param string|null $uuid
     * @param string      $file_type
     *
     * @throws Exception
     */
    public function show($uuid = null, $file_type = 'qr.svgi') {
        $this->_prepare($uuid);
        /** @var Qrcode_model $qrcode_model */
        $qrcode_model = $this->qrcode_model;
        if (!$uuid) {
            $items = $qrcode_model->get_items3();
            echo "<pre>";
            var_dump($items->get_array());
            print_r(APPPATH);
            echo "</pre>";
        } else {
            $px = ($this->input->get('size')) ?: $this->default_qr_px_size;
//            $this->color = ($this->input->get('color')) ?: $this->default_qr_color;
            $qrcode_data = $qrcode_model->get_item_by_field('uuid', $uuid);
//            $barcodeobj = new TCPDF2DBarcode($qrcod_data->content, 'QRCODE,H');
            $t0 = microtime(1);
            $barcodeobj = new TCPDF2DBarcode($qrcode_data->content, 'QRCODE,H');
            switch (explode('.', $file_type)[1]) {
                case 'png':
                    // output the barcode as PNG image
                    $barcodeobj->getBarcodePNG($px, $px, $this->color[1]);
//                    $barcodeobj->getBarcodePNG($px, $px, [10, 10, 64]);
                    break;
                case 'html':
                    echo $barcodeobj->getBarcodeHTML($px, $px, 'black');
                    break;
                case 'svgi':
                    echo $barcodeobj->getBarcodeSVGcode($px, $px, 'black');
                    break;
                case 'svg':
                default:
                    // output the barcode as SVG image
                    $barcodeobj->getBarcodeSVG($px, $px, 'black');
            }
            die;
        }
    }
//
//    public function html($uuid) {
//        // include 2D barcode class (search for installation path)
//        #require_once(dirname(__FILE__).'/tcpdf_barcodes_2d_include.php');
////        require_once (APPPATH . "/third_party/TCPDF/tcpdf_barcodes_2d.php");
//        $this->_prepare($uuid);
//        $px = ($this->input->get('size')) ?: $this->default_qr_px_size;
//        /** @var Qrcode_model $qrcode_model */
//        $qrcode_model = $this->qrcode_model;
//        $qrcode_data = $qrcode_model->get_item_by_field('uuid', $uuid);
//
//        // set the barcode content and type
//        $barcodeobj = new TCPDF2DBarcode($qrcode_data->content, 'QRCODE,H');
//        // output the barcode as HTML object
//        echo $barcodeobj->getBarcodeHTML($px, $px, 'black');
//        die;
//    }
//
//    public function png() {
//        $px = ($this->input->get('size')) ?: $this->default_qr_px_size;
//
//        // include 2D barcode class (search for installation path)
//        require_once(dirname(__FILE__) . '/tcpdf_barcodes_2d_include.php');
//
//// set the barcode content and type
//        $barcodeobj = new TCPDF2DBarcode('http://www.tcpdf.org', 'QRCODE,H');
//
//// output the barcode as PNG image
//        $barcodeobj->getBarcodePNG($px, $px, array(0, 0, 0));
//        die;
//    }
//
//    public function svg() {
//        $px = ($this->input->get('size')) ?: $this->default_qr_px_size;
//
//        // include 2D barcode class (search for installation path)
//        require_once(dirname(__FILE__) . '/tcpdf_barcodes_2d_include.php');
//
//// set the barcode content and type
//        $barcodeobj = new TCPDF2DBarcode('http://www.tcpdf.org', 'QRCODE,H');
//
//// output the barcode as SVG image
//        $barcodeobj->getBarcodeSVG($px, $px, 'black');
//    }
//
//    public function svgi() {
//        $px = ($this->input->get('size')) ?: $this->default_qr_px_size;
//
//        // include 2D barcode class (search for installation path)
//        require_once(dirname(__FILE__) . '/tcpdf_barcodes_2d_include.php');
//
//// set the barcode content and type
//        $barcodeobj = new TCPDF2DBarcode('http://www.tcpdf.org', 'QRCODE,H');
//
//// output the barcode as SVG inline code
//        echo $barcodeobj->getBarcodeSVGcode($px, $px, 'black');
//    }


}