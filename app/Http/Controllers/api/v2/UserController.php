<?php

namespace App\Http\Controllers\api\v2;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Http\Controllers\base\ApiController;

class UserController extends ApiController
{
    public function signIn(Request $request)
    {
        $phone = $request->input("phone");
        $uid = $request->input("uid");
        $type = $request->input("type");

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:12|regex:/^[0-9]*$/',
            'uid' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        if (!in_array($type, [USER::TYPE_CLIENT, User::TYPE_MANAGER])) {
            $this->setErrorMessage("The type is incorrect format: (sd_manager, sd_client)");
            return $this->response(false);
        }

        $firebase_project = "sdmanager";
        if ($type === User::TYPE_CLIENT)
            $firebase_project = "sdclient";
        $auth = Firebase::project($firebase_project)->auth();
        try {
            $user = $auth->getUser($uid);
        } catch (UserNotFound $e) {
            $this->setErrorMessage($e->getMessage());
            return $this->response(false);
        }

        $user = User::where(['phone' => $phone, 'uid' => $uid])->first();
        if (is_null($user)) {
            $user = User::create([
                'phone' => $phone,
                'uid' => $uid,
                'password' => $phone,
                'type' => $type
            ]);
        }

        $user->setPassword($phone);
        $user->tokens()->delete();
        $token = $user->createToken("sdmanager", ['api:getdata'])->plainTextToken;

        return $this->response(true, [
            "user_id" => $user->id,
            "uid" => $user->uid,
            "phone" => $user->phone,
            "token" => $token
        ]);
    }

    public function signOut(Request $request)
    {
        if (($user = $this->userExists($request)) === null) {
            return $this->response(false);
        }

        $user->tokens()->delete();

        return $this->response(true, [
            "user_id" => $user->id,
            "token" => null,
        ]);
    }
}
