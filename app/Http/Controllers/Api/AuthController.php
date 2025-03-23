<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Validator;
class AuthController extends Controller
{
    

    public function login(Request $request)
    {
        $response = [];
        try {

        $validator = Validator::make($request->all(), [ 
            'email'=>'required',
            'password'=>'required',
        ]);
        
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $user = User::where('email', $request->email)->first();

        if($user==""){
            $response['success'] = false;
            $response['message'] = 'User not found';
            return $response;
        }

        if ($user) {
            if (!\Hash::check($request->password, $user->password)) {
            $response['success'] = false;
            $response['message'] = 'Incorrect Password';
            return $response;    
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $response['success'] = true;
        $response['message'] = 'Login successfully';
        $response['token'] = $token;
        return $response;    

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            return Response::json($response); 
        }
    }
}
