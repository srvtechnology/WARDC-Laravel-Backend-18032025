<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

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

        $user = AdminUser::where('email', $request->email)->first();

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

        $token = $user->createToken('AdminToken', ['admin'])->plainTextToken;
        $response['success'] = true;
        $response['message'] = 'Login successfully';
        $response['token'] = $token;
        return $response;    

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            return Response::json($response); 
        }
    }

     public function logout(Request $request): JsonResponse
     {

        $user = Auth::guard('sanctum')->user(); // Get authenticated user

        if (!$user) {
            return response()->json(['message' => 'No authenticated user found'], 401);
        }

        // Delete only the current access token
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully','success'=>true]);

     }

     public function profile(Request $request)
     {
        $response = [];
        try {
            $user = Auth::guard('sanctum')->user();
            $response['success'] = true;
            $response['user'] = $user;
            return $response;
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            return Response::json($response); 
        }
     }
}
