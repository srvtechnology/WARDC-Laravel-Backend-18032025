<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Swimming;
use Validator;
ini_set('memory_limit','512M');
class SwimmingController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  Swimming::where('is_active',1)->get();  
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
            'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new Swimming;
            $new->label = $request->label;
            $new->value = $request->value;
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
            'value' => 'required',
            'id'=>'required',
            'category' => 'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            Swimming::where('id',$request->id)->update([
                'label'=>$request->label,
                'value'=>$request->value,
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
