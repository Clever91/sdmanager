<?php

namespace App\Http\Controllers\api\v2;

use App\Models\User;
use App\Models\ConfirmPhone;
use App\Http\Controllers\base\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SmsController extends ApiController
{
    public function send(Request $request)
    {
        $phoneNumber = $request->input("phone");
        $appType = $request->input("type");
        $smsToken = $request->input("token");
        $testNumbers = ["998900022280", "998111111111"];

        if ($smsToken != env("SMS_TOKEN", "")) {
            $this->setErrorMessage("У вас нет доступа к этому API");
            return $this->response(false);
        }

        if (!in_array($appType, [User::TYPE_CLIENT, User::TYPE_MANAGER])) {
            $this->setErrorMessage("The type is incorrect format: (sd_manager, sd_client)");
            return $this->response(false);
        }

        // validate phone only number
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|regex:/^[0-9]*$/'
        ]);

        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        // check if the phone number is allowed 
        $countryCode = null;
        $allowedCountries = ['998' => "UZ", "996" => "KG", "7" => "KZ"];
        foreach($allowedCountries as $prefix => $val) {
            if ($prefix == substr($phoneNumber, 0, strlen($prefix))) {
                $countryCode = $val;
                break;
            }
        }

        if (is_null($countryCode)) {
            $this->setErrorMessage("Приложение пока не поддерживается в вашей стране");
            return $this->response(false);
        }

        // generate confirm code
        $confirmCode = random_int(1000, 9999);
        if (in_array($phoneNumber, $testNumbers)) {
            $confirmCode = 1111;
        }

        // write to database
        $confirmModel = ConfirmPhone::where(["phone" => $phoneNumber, "app_type" => $appType])->first();

        // avoid to resend code before expire time
        if (!is_null($confirmModel)) {
            if ($confirmModel->passSeconds() <= ConfirmPhone::EXPIRE_TIME) {
                $waitSecond = ConfirmPhone::EXPIRE_TIME - $confirmModel->passSeconds();
                $this->setErrorMessage("You can send after {$waitSecond} seconds");
                return $this->response(false);
            }
        }

        $errorMessage = "Приложение пока не поддерживается в вашей стране.";
        if (is_null($confirmModel)) {
            $confirmModel = new ConfirmPhone();
            $confirmModel->phone = $phoneNumber;
            $confirmModel->app_type = $appType;
        }
        $confirmModel->confirm_code = $confirmCode;
        $confirmModel->expire_time = time();
        $confirmModel->save();

        // send sms to phone
        if (in_array($countryCode, array_values($allowedCountries))) {

            // if it is test phone number, don't send sms
            if ($confirmCode == 1111) {
                return $this->response(true, [
                    "phone" => $phoneNumber,
                    "expire_time" => ConfirmPhone::EXPIRE_TIME
                ]);
            }
            // send in eskiz.uz
            // $url = "http://billing/api/sms/one";
            $url = "https://billing.salesdoc.io/api/sms/one";

            $response = Http::post($url, [
                'phone_number' => $phoneNumber,
                'text' => "CODE: {$confirmCode}",
                'country_code' => $countryCode 
            ]);

            if ($response->ok()) {
                $body = $response->json();
                if ($body["success"] === true) {
                    return $this->response(true, [
                        "phone" => $phoneNumber,
                        "expire_time" => ConfirmPhone::EXPIRE_TIME
                    ]);
                } else {
                    $errorMessage = $body["error"]["message"] ?? "Something went wrong"; 
                }
            }
        } else {
            // otherwaise in an other service 
        }

        $this->setErrorMessage($errorMessage);
        return $this->response(false);
    }
}
