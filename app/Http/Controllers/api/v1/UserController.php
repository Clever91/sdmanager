<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\base\ApiController;
use App\Models\User;

class UserController extends ApiController
{
    public function password(Request $request)
    {
        $user = User::find($request->input("id"));
        if (is_null($user)) {
            $this->setErrorMessage("This user is not found");
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed',
        ]);
        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }
        $user->setPassword($request->input("password"));

        return $this->response(true, [
            "id" => $user->id
        ]);
    }

    public function signOut(Request $request)
    {
        $user = User::find($request->input("id"));
        if (is_null($user)) {
            $this->setErrorMessage("The user is not found");
            return $this->response(false);
        }

        $user->tokens()->delete();

        return $this->response(true, [
            "id" => $user->id,
            "token" => null,
        ]);
    }
}
