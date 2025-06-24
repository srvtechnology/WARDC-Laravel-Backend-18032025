<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyValueAdded;
use App\Models\PropertyAssessmentDetail;
use App\Models\Property_property_value_added;
class PropertyPropertyValueController extends Controller
{
    public function value()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['property'] =  Property::get();  
         $response['property_value_added'] =  PropertyValueAdded::get();  
         return $response;

        } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }

    public function dependency(Request $request)
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyAssessmentDetail::where('property_id',$request->id)->get();  
         return $response;

        } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }

    public function list()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  Property_property_value_added::with(['property_details','property_value_add_details','property_assesment_details'])->get();  
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
            'property_id' => 'required',
            'property_value_added_id' => 'required',
            'property_value_added_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

        } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
        }
    }
}
