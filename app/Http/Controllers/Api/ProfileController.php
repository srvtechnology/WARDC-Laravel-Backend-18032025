<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use Log;
use Illuminate\Validation\Rule;
use App\Models\UserMain;


class ProfileController extends Controller
{


public function changePassword(Request $request)
{
    // Token validation
    $token = $request->bearerToken();
    $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

    if (!$accessToken) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Token is missing or invalid'
        ], Response::HTTP_UNAUTHORIZED);
    }

    $user = $accessToken->tokenable; // logged-in user

    // Validate request
    $request->validate([
        'old_password' => 'required|string',
        'new_password' => 'required|string',
    ]);

    // Check old password
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Old password is incorrect'
        ], Response::HTTP_BAD_REQUEST);
    }

    // Update new password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully'
    ], Response::HTTP_OK);
}


public function updateProfile(Request $request)
{
    // Token validation
    $token = $request->bearerToken();
    $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

    if (!$accessToken) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Token is missing or invalid'
        ], Response::HTTP_UNAUTHORIZED);
    }

    $user = $accessToken->tokenable; // logged-in user

    // Debugging user details
    Log::info('User attempting profile update', ['user_id' => $user->id]);

    // Validate inputs (ignore current user's email for uniqueness check)
    $validator = Validator::make($request->all(), [
        'first_name' => 'nullable|string',
        'last_name'  => 'nullable|string',
        'email'      => [
            'nullable',
            'email',
            Rule::unique('admin_users')->ignore($user->id), // ignore current user's email
        ],
    ]);

    if ($validator->fails()) {
        Log::warning('Profile update validation failed', ['errors' => $validator->errors()]);
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
        ], Response::HTTP_BAD_REQUEST);
    }

    // Debugging incoming data
    Log::info('Profile update request data', $request->all());

    // Update fields
    if ($request->filled('first_name')) $user->first_name = $request->first_name;
    if ($request->filled('last_name'))  $user->last_name  = $request->last_name;
    if ($request->filled('email'))      $user->email      = $request->email;

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $filename = time() . '-' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('public/user_image', $filename);

        // delete old image if exists
        if ($user->image && Storage::exists('public/user_image/' . $user->image)) {
            Storage::delete('public/user_image/' . $user->image);
        }

        $user->image = $filename;
    }

    $saved = $user->save();

    // Debug after save
    Log::info('Profile update result', [
        'saved' => $saved,
        'user' => $user->toArray()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data'    => $user
    ], Response::HTTP_OK);
}



// assesment app user


public function changePasswordAssesmentApp(Request $request)
{
    // Token validation
    $token = $request->bearerToken();
    $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

    if (!$accessToken || !($accessToken->tokenable instanceof UserMain)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Token is missing or invalid or wrong token'
        ], Response::HTTP_UNAUTHORIZED);
    }

  
    $user = $accessToken->tokenable;

    // Validate request
    $request->validate([
        'old_password' => 'required|string',
        'new_password' => 'required|string|min:6',
    ]);

    // Check old password
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Old password is incorrect'
        ], Response::HTTP_BAD_REQUEST);
    }

    // Update new password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully'
    ], Response::HTTP_OK);
}





public function updateProfileAssesmentApp(Request $request)
{
    // Token validation
    $token = $request->bearerToken();
    $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

    if (!$accessToken || !($accessToken->tokenable instanceof UserMain)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Token is missing or invalid or wrong token'
        ], Response::HTTP_UNAUTHORIZED);
    }

   
    $user = $accessToken->tokenable;

    Log::info('User attempting profile update', ['user_id' => $user->id]);

    // Validate inputs
    $validator = Validator::make($request->all(), [
        'name'  => 'nullable|string|max:255',
        'email' => [
            'nullable',
            'email',
            Rule::unique('users')->ignore($user->id), // "users" = table for UserMain
        ],
        'image' => 'nullable|image|max:2048', // optional image validation
    ]);

    if ($validator->fails()) {
        Log::warning('Profile update validation failed', ['errors' => $validator->errors()]);
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
        ], Response::HTTP_BAD_REQUEST);
    }

    // Update fields
    if ($request->filled('name'))  $user->name  = $request->name;
    if ($request->filled('email')) $user->email = $request->email;

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $filename = time() . '-' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('public/user_image', $filename);

        // delete old image if exists
        if ($user->image && Storage::exists('public/user_image/' . $user->image)) {
            Storage::delete('public/user_image/' . $user->image);
        }

        $user->image = $filename;
    }

    $saved = $user->save();

    Log::info('Profile update result', [
        'saved' => $saved,
        'user' => $user->toArray()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data'    => $user
    ], Response::HTTP_OK);
}





}
