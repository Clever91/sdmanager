<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const TYPE_ADMIN = "admin";
    const TYPE_OPERATOR = "operator";
    const TYPE_CLIENT = "client";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'type',
        'uid',
        'password',
    ];

    public function setPassword($pwd)
    {
        $this->password = bcrypt($pwd);
        $this->save();
    }

    public function isValidPassword($pwd)
    {
        return Hash::check($pwd, $this->password);
    }
}
