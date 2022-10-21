<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SignUpController extends Controller
{
    public function phone(Request $request)
    {
        $response = ["success" => false];

        $token = $request->bearerToken();
        if ($request->accepts(['application/json'])) {
            dd($request);
        }
        return $response;
    }
}
