<?php

namespace App\Http\Controllers\api\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\base\ApiController;

class SignInController extends ApiController
{
    public function index(Request $request)
    {
        $user = User::where([
            "phone" => $request->input("phone"),
            "uid" => $request->input("uid")
        ])->first();
        if (is_null($user)) {
            $this->setErrorMessage("The phone number or the password is incorrect");
            return $this->response(false);
        }

        if (!$user->isValidPassword($request->input("password"))) {
            $this->setErrorMessage("The phone number or the password is incorrect");
            return $this->response(false);
        }

        $user->tokens()->delete();
        $token = $user->createToken("sdmanager", ['api:getdata'])->plainTextToken;

        return $this->response(true, [
            "user_id" => $user->id,
            "uid" => $user->uid,
            "phone" => $user->phone,
            "token" => $token,
        ]);
    }
}
