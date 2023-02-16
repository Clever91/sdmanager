<?php

namespace App\Http\Controllers\api\v2;

use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Http\Controllers\base\ApiController;
use Throwable;

class UserController extends ApiController
{
    public function signIn(Request $request)
    {
        $jwtToken = $request->input("token");
        $type = $request->input("type");

        if (!in_array($type, [USER::TYPE_CLIENT, User::TYPE_MANAGER])) {
            $this->setErrorMessage("The type is incorrect format: (sd_manager, sd_client)");
            return $this->response(false);
        }

        $firebase_project = "sdmanager";
        if ($type === User::TYPE_CLIENT)
            $firebase_project = "sdclient";
        $auth = Firebase::project($firebase_project)->auth();

        try {
            $verifiedIdToken = $auth->verifyIdToken($jwtToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $user = $auth->getUser($uid);
            // get phone from firebase
            $phone = $verifiedIdToken->claims()->get('phone_number');
            $phone = str_replace("+", "", $phone);
        } catch (Throwable $e) {
            $this->setErrorMessage($e->getMessage());
            return $this->response(false);
        }

        // ikkita firebase projectda bir xil uid bo'ladigan bo'lsa, muamo bo'lishi mumkin
        $user = User::where(['uid' => $uid])->first();
        if (!is_null($user) && $user->phone !== $phone) {
            $this->setErrorMessage("Sorry, this uid has other phone number");
            return $this->response(false);
        }
        // $user = User::where(['phone' => $phone, 'uid' => $uid])->first();
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
