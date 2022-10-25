<?php

namespace App\Http\Controllers\base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private $error = [];
    private $result = [];

    public function setErrorMessage($msg)
    {
        $this->error["message"] = $msg;
    }

    public function setErrorData($data)
    {
        $this->error["message"] = "Request is not valid";
        $this->error["data"] = $data;
    }

    public function response($success = true, $data = [])
    {
        return [
            "success" => $success,
            "result" => !empty($data) ? $data : $this->result,
            "error" => $this->error
        ];
    }
}
