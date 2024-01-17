<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmPhone extends Model
{
    use HasFactory;

    const EXPIRE_TIME = 90; // seconds

    protected $fillable = [
        "phone",
        "app_type",
        "confirm_code",
        "expire_time",
    ];

    public function passSeconds()
    {
        return time() - $this->expire_time;
    }

    public function checkConfirmCode($code)
    {
        return $this->confirm_code === $code;
    }
}
