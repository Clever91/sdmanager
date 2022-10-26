<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\base\ApiController;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use App\Models\User;

class UserController extends ApiController
{
    public function create(Request $request)
    {
        $phone = $request->input("phone");
        $password = $request->input("password");
        $uid = $request->input("uid");

        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:3',
            'phone' => 'required',
            'uid' => 'required'
        ]);

        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        // Return an instance of the Auth component for the default Firebase project
        $auth = Firebase::project('app')->auth();
        try {
            $user = $auth->getUser($uid);
        } catch (UserNotFound $e) {
            $this->setErrorMessage($e->getMessage());
            return $this->response(false);
        }

        $user = User::where(['phone' => $phone, 'uid' => $uid])->first();
        if (!is_null($user)) {
            $user->setPassword($password);
            return $this->response(true, [
                "id" => $user->id,
                "uid" => $user->uid,
                "phone" => $user->phone
            ]);
        }

        $user = User::create([
            'phone' => $phone,
            'uid' => $uid,
            'password' => $password,
            'type' => User::TYPE_CLIENT
        ]);
        $user->setPassword($password);

        return $this->response(true, [
            "id" => $user->id,
            "phone" => $user->phone
        ]);
    }

    public function password(Request $request)
    {
        $user = User::find($request->input("id"));
        if (is_null($user)) {
            $this->setErrorMessage("This user is not found");
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|confirmed|min:3',
        ]);
        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }
        if (!$user->isValidPassword($request->input("old_password"))) {
            $this->setErrorMessage("Given old password is incorrect");
            return $this->response(false);
        }
        $user->setPassword($request->input("new_password"));

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
