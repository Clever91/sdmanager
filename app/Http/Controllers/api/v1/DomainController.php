<?php

namespace App\Http\Controllers\api\v1;

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
            'phone' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        if (is_null($this->userExists($request))) {
            return $this->response(false);
        }

        try {
            // get domain info (exp: demo.salesdoc.io, demo.distr.uz)
            $errorData = [];
            $domain = $request->input("domain");
            $user_id = $request->input("user_id");
            $url = "https://server.salesdoc.io/api/add/index.php?code={$domain}";
            $response = Http::get($url);
            if ($response->ok()) {
                // if it is successfully, so make request to get accesss token
                $body = $response->json();
                if ($body["status"] == "success") {
                    // make request to get token
                    $url = $body["url"];
                    $params = [
                        "jsonrpc" => "2.0",
                        "id" => 9999,
                        "method" => "authByPhone",
                        "params" => []
                    ];
                    $params["params"]["phone"] = $request->input("phone");
                    // start making request
                    $res = Http::post($url."/api3/manager/index", $params);
                    if ($res->ok()) {
                        $body = $res->json();
                        if (isset($body["error"]) && !empty($body["error"])) {
                            $errorData = $body["error"];
                        } else if (isset($body["result"]) && !empty($body["result"])) {
                            $model = Domain::where(["domain" => $domain, "user_id" => $user_id])->first();
                            if (is_null($model)) {
                                $model = Domain::create([
                                    "user_id" => $user_id,
                                    "domain" => $domain,
                                    "url" => $url,
                                ]);
                            }
                            $result["access"] = $body["result"];
                            $result["server"] = [
                                "domain" => $domain,
                                "url" => $url
                            ];
                            return $this->response(true, $result);
                        }
                    }
                } else {
                    $errorData["domain"] = "Given domain is incorrect";
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

    public function refreshToken(Request $request)
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

        $domain = Domain::where(["user_id" => $user->id, "domain" => $request->input("domain")])->first();
        if (is_null($domain)) {
            $this->setErrorMessage("Given domain doesn`t belong to given user");
            return $this->response(false);
        }
        // make request to get token
        $params = [
            "jsonrpc" => "2.0",
            "id" => 9999,
            "method" => "authByPhone",
            "params" => []
        ];
        $params["params"]["phone"] = $user->phone;
        $errorData = [];
        try {
            $res = Http::post($domain->url."/api3/manager/index", $params);
            if ($res->ok()) {
                $body = $res->json();
                if (isset($body["error"]) && !empty($body["error"])) {
                    $errorData = $body["error"];
                } else if (isset($body["result"]) && !empty($body["result"])) {
                    return $this->response(true, $body["result"]);
                }
            }
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
            return $this->response(false);
        }

        $this->setErrorData($errorData);
        return $this->response(false);
    }
}
