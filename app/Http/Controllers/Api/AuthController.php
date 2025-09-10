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
use Illuminate\Support\Facades\Mail;

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






    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admin_users,email'
        ]);

        $otp = rand(100000, 999999); // 6-digit OTP

        $admin = AdminUser::where('email', $request->email)->first();
        $upd=  AdminUser::where('email', $request->email)->update(['otp'=>$otp]);
       

        // Send OTP via email (optional)
        Mail::raw("Your OTP code is: $otp", function ($message) use ($admin) {
            $message->to($admin->email)->subject('Password Reset OTP');
        });

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully to your email.'
        ]);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admin_users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:6|confirmed' 
        ]);

        $admin = AdminUser::where('email', $request->email)
                          ->where('otp', $request->otp)
                          ->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP or email.'
            ], 400);
        }

        $upd= AdminUser::where('email', $request->email)->update(['otp'=>null,'password'=>Hash::make($request->password)]);
        

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully.'
        ]);
    }
}
