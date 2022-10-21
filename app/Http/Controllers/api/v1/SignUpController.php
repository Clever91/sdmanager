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

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
        ]);
        if ($validator->fails()) {
            $this->setErrorMessage("Phone number must be number type and the lenght must be 9 digists");
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

    public function checkCode(Request $request)
    {
        if (!$this->isValidBearerToken($request)) {
            $this->setErrorMessage("Invalid brearer token");
            return $this->response(false);
        }

        if (!$request->accepts(['application/json'])) {
            $this->setErrorMessage("Request must be json format");
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'code' => 'required|numeric|digits:4',
        ]);
        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        $client = Client::where("phone", $request->input("phone"))->first();
        if (is_null($client)) {
            $this->setErrorMessage("This client is not found");
            return $this->response(false);
        }
        if ($client->code !== (int) $request->input("code")) {
            $this->setErrorMessage("Sorry, Given code is incorrect");
            return $this->response(false);
        }

        $token = $client->createToken("sdmanager", ['api:getdata'])->plainTextToken;
        return $this->response(true, [
            "id" => $client->id,
            "token" => $token,
        ]);
    }
}
