<?php

namespace App\Http\Controllers\api\v2;

use App\Models\ConfirmPhone;
use Str;
use Throwable;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Http\Controllers\base\ApiController;

class UserController extends ApiController
{
    public function signIn(Request $request)
    {
        $jwtToken = $request->input("token");
        $appType = $request->input("type");

        if (!in_array($appType, [User::TYPE_CLIENT, User::TYPE_MANAGER])) {
            $this->setErrorMessage("The type is incorrect format: (sd_manager, sd_client)");
            return $this->response(false);
        }

        $firebase_project = "sdmanager";
        if ($appType === User::TYPE_CLIENT) {
            $firebase_project = "sdclient";
        }
        $auth = Firebase::project($firebase_project)->auth();

        try {
            $verifiedIdToken = $auth->verifyIdToken($jwtToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            // $user = $auth->getUser($uid);
            // get phone from firebase
            $phone = $verifiedIdToken->claims()->get('phone_number');
            $phone = str_replace("+", "", $phone);
        } catch (Throwable $e) {
            $this->setErrorMessage($e->getMessage());
            return $this->response(false);
        }

        // ikkita firebase projectda bir xil uid bo'ladigan bo'lsa, muamo bo'lishi mumkin
        // bitta nomerda ikkita app ga ro'yxatga o'tsa va uid bir xil bo'lsa mysql da error beradi (unique columns)
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
                'app_type' => $appType
            ]);
        }

        $user->setPassword($phone);
        $user->tokens()->delete();
        $token = $user->createToken($firebase_project, ['api:getdata'])->plainTextToken;

        return $this->response(true, [
            "user_id" => $user->id,
            "uid" => $user->uid,
            "phone" => $user->phone,
            "token" => $token
        ]);
    }

    public function checkCode(Request $request)
    {
        $phoneNumber = $request->input("phone");
        $code = intval($request->input("code"));
        $appType = $request->input("type");
        $smsToken = $request->input("token");

        if ($smsToken != env("SMS_TOKEN", "")) {
            $this->setErrorMessage("Токен доступа недействителен");
            return $this->response(false);
        }

        if (!in_array($appType, [User::TYPE_CLIENT, User::TYPE_MANAGER])) {
            $this->setErrorMessage("The type is incorrect format: (sd_manager, sd_client)");
            return $this->response(false);
        }

        // check confirm model
        $confirmModel = ConfirmPhone::where(["phone" => $phoneNumber, "app_type" => $appType])->first();
        if (is_null($confirmModel)) {
            $this->setErrorMessage("К сожалению, код не найден");
            return $this->response(false);
        }

        // check exprire time
        if ($confirmModel->passSeconds() > ConfirmPhone::EXPIRE_TIME) {
            $this->setErrorMessage("Извините, срок действия кода истек");
            return $this->response(false);
        }

        // check confirm code
        if (!$confirmModel->checkConfirmCode($code)) {
            $this->setErrorMessage("Извините, код неверный");
            return $this->response(false);
        }

        // if the code is confirmed
        $confirmModel->delete();

        $user = User::where(['phone' => $confirmModel->phone, "app_type" => $appType])->first();
        if (is_null($user)) {
            $user = User::create([
                'phone' => $phoneNumber,
                'uid' => Str::uuid(), // must delete this column
                'password' => $phoneNumber, // must delete this column
                'app_type' => $appType
            ]);
        }

        $user->setPassword($phoneNumber);
        $user->tokens()->delete();
        $token = $user->createToken($appType, ['api:getdata'])->plainTextToken;

        return $this->response(true, [
            "user_id" => $user->id,
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
