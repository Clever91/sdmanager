<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\base\ApiController;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SignUpController extends ApiController
{
    public function phone(Request $request)
    {
        if (!$this->isValidBearerToken($request)) {
            $this->setErrorMessage("Invalid brearer token");
            return $this->response(false);
        }

        if (!$request->accepts(['application/json'])) {
            $this->setErrorMessage("Request must be json format");
            return $this->response(false);
        }

        if (!$request->has('phone')) {
            $this->setErrorMessage("Phone number must not be empty");
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
        ]);
        if ($validator->fails()) {
            $this->setErrorMessage("Phone number must be number and the lenght must be 9 digists");
            return $this->response(false);
        }

        $client = Client::firstOrCreate([
            'phone' => $request->input("phone")
        ]);
        $client->generateCode();

        return $this->response(true, [
            "id" => $client->id,
            "phone" => $client->phone,
            "code" => $client->code,
        ]);
    }
}
