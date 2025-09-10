<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminUser;
use App\Models\RoleModel;
use App\Models\UserMain;
use Validator;
class SystemUserController extends Controller
{
    public function listing()
    {
        $response = [];
        try {

         $response['data'] = AdminUser::orderBy('id','desc')->with('role_details')->where([['id', '!=', 1]])->get();
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
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'email' => 'required|email|unique:admin_users,email',
            'password' => 'required',
            'street_name' => 'required',
            'street_number' => 'required',
            'role_id' => 'required',
          ]);

          if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
          } 

          $user = new AdminUser();
          $user->first_name = $request->first_name;
          $user->last_name = $request->last_name;
          $user->street_name = $request->street_name;
          $user->street_number = $request->street_number;
          $user->gender = $request->gender;
          $user->email = $request->email;
          $user->password = \Hash::make($request->password);
          $user->is_active = $request->is_active;
          $user->role_id = $request->role_id;
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

         $response['data'] = AdminUser::where('id',$id)->first();
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
                'first_name' => 'required',
                'last_name' => 'required',
                'gender' => 'required',
                'street_name' => 'required',
                'street_number' => 'required',
                'role_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find and update user
            AdminUser::where('id',$request->id)->update([
                'first_name' => $request->first_name,
                'gender' => $request->gender,
                'last_name' => $request->last_name,
                'street_name' => $request->street_name,
                'street_number' => $request->street_number,
                'role_id' => $request->role_id,
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


    public function delete($id)
    {
        $response = [];

        try {
            $user = AdminUser::find($id);

            if (!$user) {
                $response['status'] = false;
                $response['message'] = 'User not found.';
                return $response;
            }

            if ($user->payments()->count() == 0) {
                $user->delete();
                $response['status'] = true;
                $response['message'] = 'User deleted successfully.';
                return $response;
            }

            $response['status'] = false;
            $response['message'] = 'User cannot be deleted. User is associated with payment.';
            return $response;

        } catch (\Exception $e) {
            $response['status'] = false;
            $response['message'] = 'An error occurred.';
            $response['error'] = $e->getMessage();
            return response()->json($response, 500);
        }
    }

    public function getRoleListing()
    {
        $response = [];
        try {

         $response['data'] = RoleModel::get();
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
}
