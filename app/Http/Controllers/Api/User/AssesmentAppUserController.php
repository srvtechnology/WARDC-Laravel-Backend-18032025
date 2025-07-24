<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserMain;
use Validator;
ini_set('memory_limit','512M');
class AssesmentAppUserController extends Controller
{
    public function listing()
    {
        $response = [];
        try {

         $response['data'] = UserMain::get();
         $response['status'] = true;
         return $response;

        }catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }

    public function insert(Request $request)
    {
        $response = [];
        try {

          $validator = Validator::make($request->all(), [ 
            'name' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'password' => 'required',
            'street_name' => 'required',
            'street_number' => 'required',
          ]);

          if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
          } 

          $user = new UserMain;
          $user->name = $request->name;
          $user->street_name = $request->street_name;
          $user->street_number = $request->street_number;
          $user->gender = $request->gender;
          $user->email = $request->email;
          $user->password = \Hash::make($request->password);
          $user->is_active = $request->is_active;
          $user->image = '';
          $user->ward = '';
          $user->save();
          $response['status'] = true;
          $response['message'] = 'Data inserted successfully';
          return $response;

        } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }

    public function edit($id)
    {
        $response = [];
        try {

         $response['data'] = UserMain::where('id',$id)->first();
         $response['status'] = true;
         return $response;

        }catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }

    }

    public function update(Request $request)
    {
        try {
            // Validation without password
            $validator = Validator::make($request->all(), [ 
                'name' => 'required',
                'gender' => 'required',
                'email' => 'required|email',
                'street_name' => 'required',
                'street_number' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find and update user
            $user = UserMain::findOrFail($request->id);
            $user->update([
                'name' => $request->name,
                'gender' => $request->gender,
                'email' => $request->email,
                'street_name' => $request->street_name,
                'street_number' => $request->street_number,
                'is_active' => $request->is_active,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $response = [];

        try {
            $user = UserMain::find($id);

            if (!$user) {
                $response['status'] = false;
                $response['message'] = 'User not found.';
                return $response;
            }

            if ($user->properties()->count() == 0) {
                $user->delete();
                $response['status'] = true;
                $response['message'] = 'User deleted successfully.';
                return $response;
            }

            $response['status'] = false;
            $response['message'] = 'User cannot be deleted. User is associated with properties.';
            return $response;

        } catch (\Exception $e) {
            $response['status'] = false;
            $response['message'] = 'An error occurred.';
            $response['error'] = $e->getMessage();
            return response()->json($response, 500);
        }
    }


}
