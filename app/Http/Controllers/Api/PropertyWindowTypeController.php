<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyWindowType;
use Validator;
ini_set('memory_limit','512M');
class PropertyWindowTypeController extends Controller
{
    public function index()
    {
        $response = [];
        try {
         
         $response['status'] = true;
         $response['data'] =  PropertyWindowType::where('is_active',1)->orderBy('id','desc')->get();  
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
            'good_percent' => 'required',
            'average_precent' => 'required',
            'bad_percent' => 'required',
            'good_value' => 'required',
            'value' => 'required',
            'bad_value' => 'required',
             'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $new = new PropertyWindowType;
            $new->label = $request->label;
            $new->good_percent = $request->good_percent;
            $new->average_precent = $request->average_precent;
            $new->bad_percent = $request->bad_percent;
            $new->good_value = $request->good_value;
            $new->value = $request->value;
            $new->bad_value = $request->bad_value;
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
            'good_percent' => 'required',
            'average_precent' => 'required',
            'bad_percent' => 'required',
            'good_value' => 'required',
            'value' => 'required',
            'bad_value' => 'required',
            'id'=>'required',
             'category' => 'required',
            ]);

            if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ], 422);
            } 

            // PropertyWindowType::where('id',$request->id)->update([
            //     'label'=>$request->label,
            //     'good_percent'=>$request->good_percent,
            //     'average_precent'=>$request->average_precent,
            //     'bad_percent'=>$request->bad_percent,
            //     'good_value'=>$request->good_value,
            //     'value'=>$request->value,
            //     'bad_value'=>$request->bad_value,
            //      'category'=>$request->category,
            // ]);

            $windowType = PropertyWindowType::findOrFail($request->id);

            $windowType->update([
                'label'           => $request->label,
                'good_percent'    => $request->good_percent,
                'average_precent' => $request->average_precent,
                'bad_percent'     => $request->bad_percent,
                'good_value'      => $request->good_value,
                'value'           => $request->value,
                'bad_value'       => $request->bad_value,
                'category'        => $request->category,
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
