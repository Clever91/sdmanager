<?php

namespace App\Http\Controllers\base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getBearToken()
    {
        return env("API_BEAR_TOKEN", "token");
    }
}
