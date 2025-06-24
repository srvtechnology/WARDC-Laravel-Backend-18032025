<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyType;
use Validator;
ini_set('memory_limit','512M');
class PropertyTypeController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyType::where('is_active',1)->get();  
         return $response;

        } catch (\Exception $e) {
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
            'label' => 'required',
            'value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new PropertyType;
            $new->label = $request->label;
            $new->value = $request->value;
            $new->is_active = 1;
            $new->save();
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


    public function update(Request $request)
    {
        $response = [];
        try {

          $validator = Validator::make($request->all(), [ 
            'label' => 'required',
            'value' => 'required',
            'id'=>'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            PropertyType::where('id',$request->id)->update([
                'label'=>$request->label,
                'value'=>$request->value,
            ]);

            $response['status'] = true;
            $response['message'] = 'Data updated successfully';
            return $response;



        } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }

    public function single($id)
    {
        $response = [];
        try {

        $response['status'] = true;
        $response['data'] = PropertyType::where('id',$id)->first();
        return $response;


        }catch (\Exception $e) {
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

        $response['status'] = true;
        $response['data'] = PropertyType::where('id',$id)->update(['is_active'=>0]);
        $response['message'] = 'Data deleted successfully';
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
