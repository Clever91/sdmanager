<?php

namespace App\Http\Controllers\base;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private $error = [];
    private $result = [];

    public function __construct()
    {
        // if (!request()->accepts(['application/json'])) {
        //     $this->setErrorMessage("Request's Accept must be application/json type");
        //     $this->response(false);
        // }
    }

    public function userExists($request)
    {
        $user = User::find($request->input("user_id"));
        if (is_null($user)) {
            $this->setErrorMessage("This user is not found");
        }
        return $user;
    }

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
