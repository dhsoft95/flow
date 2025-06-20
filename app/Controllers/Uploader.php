<?php

namespace App\Controllers;

use App\Libraries\Google;
use App\Libraries\StreamResponse;

class Uploader extends App_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        show_404();
    }

    function upload_file()
    {
        upload_file_to_temp();
    }

    function upload_excel_import_file()
    {
        upload_file_to_temp(true);
    }

    function validate_file()
    {
        return validate_post_file($this->request->getPost("file_name"));
    }

    function validate_image_file()
    {
        $file_name = $this->request->getPost("file_name");
        if (!is_valid_file_to_upload($file_name)) {
            echo json_encode(array("success" => false, 'message' => app_lang('invalid_file_type')));
            exit();
        }

        if (is_image_file($file_name)) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('please_upload_valid_image_files')));
        }
    }

    function stream_google_drive_file($file_id, $filename)
    {

        $google = new Google();
        $file_data = $google->get_file_content($file_id);
        if (is_array($file_data)) {
            if (isset($file_data["mime_type"])) {
                return $this->_download($filename, $file_data["contents"], true);
            }else if (isset($file_data['error']['message'])) {
                echo $file_data['error']['message'];
            }
        }
    }

    private function _download(string $filename = '', $data = '', bool $setMime = false)
    {
        if ($filename === '' || $data === '') {
            return null;
        }

        $filepath = '';
        if ($data === null) {
            $filepath = $filename;
            $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
            $filename = end($filename);
        }

        $response = new StreamResponse($filename, $setMime);

        if ($filepath !== '') {
            $response->setFilePath($filepath);
        } elseif ($data !== null) {
            $response->setBinary($data);
        }

        return $response;
    }
}
