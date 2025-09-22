<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CounsilAdjustmentGroupA;
use Validator;
ini_set('memory_limit','512M');
class CouncilAdjustmentController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  CounsilAdjustmentGroupA::orderBy('id','desc')->get();  
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
            'name' => 'required',
            'type' => 'required',
            'sign' => 'required',
            'percentage' => 'required',
             'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new CounsilAdjustmentGroupA;
            $new->name = $request->name;
            $new->type = $request->type;
            $new->sign = $request->sign;
            $new->percentage = $request->percentage;
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
            'name' => 'required',
            'type' => 'required',
            'sign' => 'required',
            'percentage' => 'required',
            'id' => 'required',
             'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }  

            // CounsilAdjustmentGroupA::where('id',$request->id)->update([
            //     'name'=>$request->name,
            //     'type'=>$request->type,
            //     'sign'=>$request->sign,
            //     'percentage'=>$request->percentage,
            //     'category'=>$request->category,
            // ]);

            $councilAdjustment = CounsilAdjustmentGroupA::findOrFail($request->id);

            $councilAdjustment->update([
                'name'       => $request->name,
                'type'       => $request->type,
                'sign'       => $request->sign,
                'percentage' => $request->percentage,
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
        $response['data'] = CounsilAdjustmentGroupA::where('id',$id)->first();
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
