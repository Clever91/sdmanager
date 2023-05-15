<?php

namespace App\Http\Controllers\api\v2;

use App\Component\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\base\ApiController;
use App\Models\Domain;

class DomainController extends ApiController
{
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        if (is_null($user = $this->userExists($request))) {
            return $this->response(false);
        }

        try {
            // get domain info (exp: demo.salesdoc.io, demo.distr.uz)
            $errorData = [];
            $domain = $request->input("domain");
            $user_id = $request->input("user_id");
            
            $appType = "sdmanager";
            if ($user->isClient()) {
                $appType = "sdclient";
            }
            $url = "https://server.salesdoc.io/api/add/index.php?add={$appType}&code={$domain}";
            $response = Http::get($url);
            if ($response->ok()) {
                $body = $response->json();

                // we should check if this server is countrysale
                // and app type is sdclient, so return error
                if (!empty($body["type"]) && $body["type"] == "countrysale") {
                    $this->setErrorMessage("This app doesn't have access to countrysale server");
                    return $this->response(false);
                }
                
                if ($body["status"] === "success") {
                    $model = Domain::where(["domain" => $domain, "user_id" => $user_id])->first();
                    if (is_null($model)) {
                        $model = Domain::create([
                            "user_id" => $user_id,
                            "domain" => $domain,
                            "url" => $body["url"],
                        ]);
                    }
                    return $this->response(true);
                }
                if ($body["status"] === "error") {
                    $this->setErrorMessage($body["message"]);
                    return $this->response(false);
                }
            } else {
                $errorData["domain"] = "Given domain is incorrect";
            }
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
            return $this->response(false);
        }

        $this->setErrorData($errorData);
        return $this->response(false);
    }

    public function list(Request $request)
    {
        if (is_null($user = $this->userExists($request))) {
            return $this->response(false);
        }

        $domains = Domain::where([
            "user_id" => $user->id,
        ])->select("domain", "url")->get();
        return $this->response(true, $domains);
    }

    public function delete(Request $request)
    {
        if (is_null($this->userExists($request))) {
            return $this->response(false);
        }

        $domain = Domain::where([
            "user_id" => $request->input("user_id"),
            "domain" => $request->input("domain"),
        ])->first();
        if (is_null($domain)) {
            $this->setErrorMessage("Given domain is not found");
            return $this->response(false);
        }
        $domain->delete();
        return $this->response(true);
    }

    public function jwtToken(Request $request)
    {
        if (($user = $this->userExists($request)) === null) {
            return $this->response(false);
        }
        $data = [
            "phone" => (int)$user->phone,
            "type" => $user->type,
        ];
        $data["data"] = $request->all();

        $expire_time = 1 * 60 * 60; // 1 hour
        $secret_key = env('JWT_SECRET_KEY', "secret");
        $jwt = new JWT($secret_key);
        $jwtToken = $jwt->encode($data, $expire_time);
        return $this->response(["token" => $jwtToken]);
    }
}
