<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyRoofsMaterials;
use Validator;
ini_set('memory_limit','512M');
class PropertyValueMaterialController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyRoofsMaterials::where('is_active',1)->orderBy('id','desc')->get();  
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

            $new = new PropertyRoofsMaterials;
            $new->label = $request->label;
            $new->value = $request->value;
            $new->is_active = 1;
            $new->cat_id = $request->cat_id;
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
            'cat_id' => 'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            // PropertyRoofsMaterials::where('id',$request->id)->update([
            //     'label'=>$request->label,
            //     'value'=>$request->value,
            //     'cat_id'=>$request->cat_id,
            // ]);

            $roofMaterial = PropertyRoofsMaterials::findOrFail($request->id);

            $roofMaterial->update([
                'label' => $request->label,
                'value' => $request->value,
                'cat_id' => $request->cat_id,
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
