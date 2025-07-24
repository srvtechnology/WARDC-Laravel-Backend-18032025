<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyPayment;
use App\Models\Property;
use App\Models\PropertyGeoRegistry;
use App\Models\District;
use App\Jobs\PropertyStickers;
use App\Notifications\PaymentSMSNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\PaymentAjdustDetails;
use Illuminate\Support\Facades\Auth;
use App\Models\PropertyAssessmentDetail;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
   


   public function paymentSearch(Request $request){
        $user = Auth::guard('sanctum')->user();
        $property = [];
        $last_payment = null;
        $paymentInQuarter = [];
        $history = [];

        if ($request->input('digital_address') || $request->input('old_digital_address') || $request->input('property_id')) {
            $address = explode('%', $request->input('digital_address') ? $request->input('digital_address') : $request->input('old_digital_address'));

            if ($request->filled('property_id')) {
                $address[0] = $request->input('property_id');
            }

            $PropertyGeoRegistry = PropertyGeoRegistry::with(['property'])
                ->whereHas('property', function ($query) use ($request, $address) {
                    return $query->where('id', $address[0]);
                })
                ->first();

          
            // if (request()->user()->hasRole('Super Admin')) {
            if ($user && $user->super_admin == 1) {
                if ($PropertyGeoRegistry) {
                    $property = Property::with(['landlord', 'occupancy', 'assessment', 'geoRegistry', 'assessmentHistory'])->find($PropertyGeoRegistry->property->id);
                    if ($property) {
                        $paymentInQuarter = $property->getPaymentsInQuarter();
                    }
                } else {
                    $property = new Property();
                    $paymentInQuarter = [];
                }
            } else {
                if ($PropertyGeoRegistry) {
                    $property = Property::where('district', $user->assign_district)
                        ->with(['landlord', 'occupancy', 'assessment', 'geoRegistry', 'assessmentHistory'])
                        ->find($PropertyGeoRegistry->property->id);
                    if ($property) {
                        $paymentInQuarter = $property->getPaymentsInQuarter();
                    }
                } else {
                    $property = new Property();
                    $paymentInQuarter = [];
                }
            }
        }

        $propertyId = $request->input('property_id');

        $pensioner_image_path = PropertyPayment::where('property_id', '=', $propertyId)->whereNotNull('pensioner_discount_image')->orderBy('created_at', 'desc')->first();

        $disability_image_path = PropertyPayment::where('property_id', '=', $propertyId)->whereNotNull('disability_discount_image')->orderBy('created_at', 'desc')->first();

        $digital_address = PropertyGeoRegistry::distinct()->orderBy('property_id')->pluck('digital_address', 'digital_address')->sort()->prepend('Select Digital Address', '');

        $propertyAssesment=PropertyAssessmentDetail::select('*', 'penalty as newpenalty')->where('property_id',$propertyId)->whereYear('created_at', $request->year)->first();

        $amountPaid=PropertyPayment::where('property_id', '=', $propertyId)->whereYear('created_at', $request->year)->count('total');

        //allpayments
        $allPayments=PropertyPayment::where('property_id', '=', $propertyId)->orderBy('id','desc')->with('admin')->get();
        //all assesments
        $allAssesments=PropertyAssessmentDetail::select('*', 'penalty as newpenalty')->where('property_id', '=', $propertyId)->orderBy('id','desc')->get();
        foreach ($allAssesments as $key => $value) {
            //get payment of that year as total paymemnt
            $allAssesments[$key]['currentYearTotalPayment']=PropertyPayment::whereYear('created_at', $value->created_at->year)->sum('amount');
        }
       
        return response()->json([
            'status' => false,
            'property' => $property,
            'digital_address' => $digital_address,
            'paymentInQuarter' => $paymentInQuarter,
            'history' => $history,
            'pensioner_image_path' => $pensioner_image_path,
            'disability_image_path' => $disability_image_path,
            'propertyAssesment'=>$propertyAssesment,
            'amountPaid'=>$amountPaid,
            'allPayments'=>$allPayments,
            'allAssesments'=>$allAssesments
        ], 200);


    }







    public function paymentInsert(Request $request){
         $validator = Validator::make($request->all(), [
           "assessment_id" => "required|integer",
            "property_id" => "required|integer",
            "paying_amount"=>"required",
            "payment_type"=>"required",
            "payee_name"=>"required",
            "payment_year"=>"required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $assesmentDetails=PropertyAssessmentDetail::where('id',$request->assessment_id)->where('property_id',$request->property_id)->first();

        $user = Auth::guard('sanctum')->user();
        
        $t_amount = intval(str_replace(',', '', $request->paying_amount));
        $t_penalty = 0;

        //insert in to payment table
        $ins=new PropertyPayment;
        $ins->property_id=$request->property_id;
        $ins->payment_made_year=$request->payment_year;
        $ins->amount=$t_amount;
        $ins->total=$t_amount+$t_penalty;
        $ins->payment_type=$request->payment_type;
        $ins->cheque_number=$request->cheque_no;
        $ins->payee_name=$request->payee_name;
        $ins->admin_user_id=$user->id;
        $ins->assessment=$assesmentDetails->property_rate_without_gst;
        $ins->balance=$request->due;
        $ins->save();

        if ($request->payment_year != (int) date('Y')) {
            if (@$request->image) {
                $image = @$request->image;
                $filename = time() . '-' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
                $image->move('storage/app/public/adjustPayment', $filename);
            }

            $insPa = new PaymentAjdustDetails();
            $insPa->property_id = $id;
            $insPa->payment_id = $payment->id;
            $insPa->year = $request->payment_year;
            $insPa->paying_amount = $request->amount;
            $insPa->adjust_amount = $request->adjust_amount;
            $insPa->total_amount = $request->total;
            $insPa->image = $filename;
            $insPa->comment = $request->comment;
            $insPa->save();
        }

        //update sue to assesment tbale
        $updateAssesment=PropertyAssessmentDetail::where('id',$request->assessment_id)->where('property_id',$request->property_id)->update(['due'=>$assesmentDetails->due - $ins->total]);

       return response()->json([
            'status' => true,
            'message' =>'Payment Done Successfully.'
        ], 200);


    }



    public function paymentDelete(Request $request){
        $find= PropertyPayment::where('id',$request->payment_id)->first();
        if(!$find){
            return response()->json([
                'status' => true,
                'message' =>'Payment Done Successfully.'
            ], 500);
        }

        // $property = $payment->property;
        //update due for that year
        $assesmentDetails=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->first();

        $updateAssesment=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->update(['due'=>$assesmentDetails->due + $find->total]);

        $find->delete();

        return response()->json([
                'status' => true,
                'message' =>'Payment Deleted Successfully.'
        ], 200);
    }






public function paymentUpdate( Request $request)
{
    $id=$request->id;
    $payment = PropertyPayment::find($id);
    $find= PropertyPayment::where('id',$id)->first();

    if (!$payment) {
        return response()->json([
            'status' => false,
            'message' => 'Payment not found.'
        ], 404);
    }
    
    if ($payment->created_at->year != now()->year) {
        return response()->json([
            'status' => false,
            'message' => 'Payment update not possible for previous years.'
        ], 404);
    }

    if (date('Y', strtotime($request->created_at)) != now()->year) {
    return response()->json([
        'status' => false,
        'message' => 'Payment update not possible using previous years.'
    ], 404);
}


    $validator = Validator::make($request->all(), [
        'assessment' => 'required',
        'amount' => 'required',
        'payment_type' => 'required|in:cash,cheque',
        'cheque_number' => 'nullable|required_if:payment_type,cheque',
        'payee_name' => 'required|max:250',
        'created_at' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $property = $payment->property;

    $t_amount = intval(str_replace(',', '', $request->amount));
    $t_penalty = intval(str_replace(',', '', $request->penalty));;
    $t_assessment = intval(str_replace(',', '', $request->assessment));
    $admin = Auth::guard('sanctum')->user();

    //update due for that year
    $assesmentDetails=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->first();

    if((int)$find->total>(int)$request->amount+(int)$request->penalty){
        //prev total more than comming amount then minus amount will add in due
        $amountToAdd=(int)$find->total-(int)$request->amount-(int)$request->penalty;
        $updateAssesment=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->update(['due'=>$assesmentDetails->due + $amountToAdd]);
    }else{

        $amountToMinus=(int)$request->amount-(int)$find->total+(int)$request->penalty;
        $updateAssesment=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->update(['due'=>$assesmentDetails->due - $amountToMinus]);
    }
    $finalAssementDetails=PropertyAssessmentDetail::where('property_id',$find->property_id)->whereYear('created_at',$find->created_at->year)->first();

    $data = $request->only([
        'payment_type',
        'cheque_number',
        'payee_name',
        'pensioner_discount_approve',
        'disability_discount_approve'
    ]);

    $data['admin_user_id'] = $admin->id ?? null;
    $data['total'] = $t_amount + $t_penalty;
    $data['amount'] = $t_amount;
    $data['assessment'] = $t_assessment;
    $data['created_at'] = $request->created_at;
    $data['updated_at'] = $request->created_at;
    $data['penalty'] = $t_penalty;
    $data['balance'] = $finalAssementDetails->due;

    $payment->fill($data);
    $payment->created_at = $request->created_at;
    $payment->updated_at = $request->created_at;
    $payment->save(['timestamps' => false]);


    

    return response()->json([
        'status' => true,
        'message' => 'Transaction successfully updated.',
        'data' => $payment
    ]);
}






}
