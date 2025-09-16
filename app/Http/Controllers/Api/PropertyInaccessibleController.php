<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyInaccessible;
use Validator;
ini_set('memory_limit','512M');
class PropertyInaccessibleController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyInaccessible::where('is_active',1)->get();  
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
             'category' => 'required',
             ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new PropertyInaccessible;
            $new->label = $request->label;
            $new->is_active = 1;
            $new->category = $request->category;
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
            'id'=>'required',
             'category' => 'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            PropertyInaccessible::where('id',$request->id)->update([
                'label'=>$request->label,
                'category'=>$request->category,
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
