<?php

namespace App\Http\Controllers;

use App\Models\Token;
use Illuminate\Http\Request;

class HashController extends Controller
{
    public function getToken()
    {
        $salt = "|godjilla";
        $line = rand(100000000, 999999999);
        $encrypt = hash( 'sha256',$line.$salt);

        $token = new Token;
        $token->token = $encrypt;
        $token->is_used = false;
        $token->save();

        return response()->json(['key' => $line]);
    }
}
