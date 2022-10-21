<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Client extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = ['phone', 'code'];

    public function generateCode()
    {
        $this->code = rand(1000, 9999);
        $this->save();
    }
}
