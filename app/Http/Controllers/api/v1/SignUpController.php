<?php

namespace App\Http\Controllers\api\v1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\base\ApiController;

class SignUpController extends ApiController
{
    public function phone(Request $request)
    {
        if (!$this->isValidBearerToken($request)) {
            $this->setErrorMessage("Invalid brearer token");
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
        ]);
        if ($validator->fails()) {
            $this->setErrorMessage("Phone number must be number type and the lenght must be 9 digists");
            return $this->response(false);
        }

        $user = User::firstOrCreate([
            'phone' => $request->input("phone")
        ]);
        $user->generateCode();

        return $this->response(true, [
            "id" => $user->id,
            "phone" => $user->phone,
            "code" => $user->code,
        ]);
    }

    public function checkCode(Request $request)
    {
        if (!$this->isValidBearerToken($request)) {
            $this->setErrorMessage("Invalid brearer token");
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

        $user = User::where("phone", $request->input("phone"))->first();
        if (is_null($user)) {
            $this->setErrorMessage("This user is not found");
            return $this->response(false);
        }
        if ($user->code !== (int) $request->input("code")) {
            $this->setErrorMessage("Sorry, Given code is incorrect");
            return $this->response(false);
        }

        $user->tokens()->delete();
        $token = $user->createToken("sdmanager", ['api:getdata'])->plainTextToken;

        return $this->response(true, [
            "id" => $user->id,
            "token" => $token,
        ]);
    }
}
