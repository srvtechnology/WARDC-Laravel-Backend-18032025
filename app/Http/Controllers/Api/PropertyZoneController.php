<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyZones;
use Validator;
ini_set('memory_limit','512M');
class PropertyZoneController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyZones::where('is_active',1)->get();  
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
            'cat_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new PropertyZones;
            $new->label = $request->label;
            $new->value = $request->value;
            $new->cat_id = $request->cat_id;
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
            'cat_id' => 'required',
            'id'=>'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            PropertyZones::where('id',$request->id)->update([
                'label'=>$request->label,
                'value'=>$request->value,
                'cat_id'=>$request->cat_id,
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
}
