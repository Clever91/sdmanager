<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\base\ApiController;

class DashboardController extends ApiController
{
    public function index()
    {
        return ['Ok'=>true];
    }
}
