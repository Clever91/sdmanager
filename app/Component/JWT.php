<?php

namespace App\Component;

class JWT
{
    private $secret_key = null;

    /**
     * contruct of JWT
     * @param string $secret
     */
    public function __construct($secret)
    {
        $this->secret_key = $secret;
    }

    /**
     * Encode data to JWT
     * @param string $data
     * @param integer $exp_time
     * @return string
     */
    public function encode($data, $exp_time)
    {
        // header
        $header = ["alg" => "HS256", "typ" =>  "JWT"];
        $h = Base64URL::encode(json_encode($header));
        // payload
        $payload = $data;
        $payload["exp"] = time() + $exp_time;
        $p = Base64URL::encode(json_encode($payload));
        // signature
        $signature = hash_hmac("sha256", "{$h}.{$p}", $this->secret_key);
        $s = Base64URL::encode($signature);
        return "{$h}.{$p}.{$s}";
    }

    /**
     * Dencode token to JWT
     * @param string $token
     * @return array|false
     */
    public function decode($token)
    {
        // explode token
        $part = explode(".", $token);
        if (count($part) != 3)
            return false;
        // check signature 
        $signature = hash_hmac("sha256", "{$part[0]}.{$part[1]}", $this->secret_key);
        $signature = Base64URL::encode($signature);
        if ($part[2] !== $signature)
            return false;
        // check expire time
        $payload = json_decode(Base64URL::decode($part[1]), true);
        if (empty($payload) || $payload["exp"] < time())
            return false;
        // return payload
        return $payload;
    }
}
