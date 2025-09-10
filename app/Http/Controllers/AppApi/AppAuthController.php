<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AdminUser;
use App\Models\UserMain;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;


class AppAuthController extends Controller
{
    
    public function assesment_app_user_login(Request $request)
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

        $user = UserMain::where('email', $request->email)->first();

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

        $token = $user->createToken('UserToken')->plainTextToken;
        $response['success'] = true;
        $response['message'] = 'Assesment app Login successfully';
        $response['token'] = $token;
        return $response;    

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            return Response::json($response); 
        }
    }






    public function payment_app_user_login(Request $request)
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

        if ($user) {
            //5 for cashier
            if ($user->role_id != 5) {
            $response['success'] = false;
            $response['message'] = 'Role is not Cashier';
            return $response;    
            }
        }

        $token = $user->createToken('AdminToken', ['admin'])->plainTextToken;
        $response['success'] = true;
        $response['message'] = 'Payment app Login successfully';
        $response['token'] = $token;
        return $response;    

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            return Response::json($response); 
        }
    }





     public function app_user_logout(Request $request): JsonResponse
     {

        $user = Auth::guard('sanctum')->user(); // Get authenticated user
        // dd($user);

        if (!$user) {
            return response()->json(['message' => 'No authenticated user found'], 401);
        }

        // Delete only the current access token
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully','success'=>true]);

     }



}
