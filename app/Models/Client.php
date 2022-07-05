<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Client extends Model
{
    use HasFactory;
	protected $fillable = ["*"];
	public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = md5($password);
    }
}
