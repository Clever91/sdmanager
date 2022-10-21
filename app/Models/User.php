<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'type',
        'code',
        'password',
    ];

    public function generateCode()
    {
        $this->code = rand(1000, 9999);
        $this->save();
    }

    public function setPassword($pwd)
    {
        $this->password = bcrypt($pwd);
        $this->save();
    }

    public function validatePassword($pwd)
    {
        return $this->password === bcrypt($pwd);
    }
}
