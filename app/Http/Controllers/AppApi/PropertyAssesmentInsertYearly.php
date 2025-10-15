<?php

namespace App\Http\Controllers\AppApi;

use App\Grids\LandLordVerifyGrid;
use App\Exports\PropertyExport;
use App\Exports\Vipsdownload;
use App\Exports\WaybillExport;
use App\Exports\SummaryExport;
use App\Grids\PropertiesGrid;
use App\Http\Controllers\Controller;
use App\Jobs\PropertyInBulk;
use App\Jobs\PropertyEnvpBulk;
use App\Jobs\PropertyNotice;
use App\Jobs\PropertyStickers;
use App\Logic\SystemConfig;
use App\Models\BoundaryDelimitation;
use App\Models\Property;
use App\Models\Summary;
use App\Models\PropertyAssessmentDetail;
use App\Models\PropertyCategory;
use App\Models\PropertyDimension;
use App\Models\PropertyGeoRegistry;
use App\Models\PropertyInaccessible;
use App\Models\PropertyRoofsMaterials;
use App\Models\PropertyType;
use App\Models\PropertyUse;
use App\Models\PropertyPayment;
use App\Models\PropertyValueAdded;
use App\Models\PropertyWallMaterials;
use App\Models\PropertyZones;
use App\Models\RegistryMeter;
use App\Models\PropertyWindowType;
use App\Models\LandlordDetail;
use App\Models\UserTitleTypes;
use App\Models\PropertySanitationType;
use App\Models\AdjustmentValue;
use App\Models\Adjustment;
use App\Models\Swimming;
use App\Models\User;
use App\Models\Bulk;
use App\Models\District;
use App\Models\InaccessibleProperty;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Twilio;
use App\Notifications\PaymentRequestSMS;
use DB;
use App\Exports\BulkExport;
use App\Imports\BulkImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SmsToProperty;
use App\Models\CounsilAdjustmentGroupA;
use App\Models\PropertyToCounsilGroupA;
use App\UserAssignedProperty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\MillRate;
use App\Jobs\UpdatePropertyAssessmentJob;


ini_set('memory_limit','512M');


class PropertyAssesmentInsertYearly extends Controller
{

  


//not req.
public function propertyAssessmentSaveYearlyOld(Request $request){

 	$currentYear = Carbon::now()->year;
 	$propertyId=$request->propertyId;

    // Check if data for this property and year already exists
    $exists = PropertyAssessmentDetail::where('property_id', $propertyId)
                ->whereYear('created_at', $currentYear)
                ->exists();

    if ($exists) {
        return response()->json(['message' => 'Data for current year already exists.'], 409);
    }

    // Fetch the latest data for this property
    $find = PropertyAssessmentDetail::where('property_id', $propertyId)->latest('created_at')->first();

    if (!$find) {
        return response()->json(['message' => 'No existing data found to clone.'], 404);
    }

	    $ins = new PropertyAssessmentDetail();

	    // Manually assigning all fields (except id)
	    $ins->property_id = $find->property_id;
	    $ins->property_categories = $find->property_categories;
	    $ins->property_wall_materials = $find->property_wall_materials;
	    $ins->roofs_materials = $find->roofs_materials;
	    $ins->property_window_type = $find->property_window_type;
	    $ins->property_dimension = $find->property_dimension;
	    $ins->length = $find->length;
	    $ins->breadth = $find->breadth;
	    $ins->square_meter = $find->square_meter;
	    $ins->property_rate_without_gst = $find->property_rate_without_gst;
	    $ins->property_gst = $find->property_gst;
	    $ins->property_rate_with_gst = $find->property_rate_with_gst;
	    $ins->property_use = $find->property_use;
	    $ins->zone = $find->zone;
	    $ins->no_of_mast = $find->no_of_mast;
	    $ins->no_of_shop = $find->no_of_shop;
	    $ins->no_of_compound_house = $find->no_of_compound_house;
	    $ins->compound_name = $find->compound_name;
	    $ins->gated_community = $find->gated_community;
	    $ins->swimming_id = $find->swimming_id;
	    $ins->assessment_images_2 = $find->assessment_images_2;
	    $ins->assessment_images_1 = $find->assessment_images_1;
	    $ins->demand_note_delivered_at = $find->demand_note_delivered_at;
	    $ins->demand_note_recipient_name = $find->demand_note_recipient_name;
	    $ins->demand_note_recipient_mobile = $find->demand_note_recipient_mobile;
	    $ins->demand_note_recipient_photo = $find->demand_note_recipient_photo;
	    $ins->last_printed_at = $find->last_printed_at;
	    $ins->window_type_type = $find->window_type_type;
	    $ins->total_adjustment_percent = $find->total_adjustment_percent;
	    $ins->group_name = $find->group_name;
	    $ins->mill_rate = $find->mill_rate;
	    $ins->wall_material_percentage = $find->wall_material_percentage;
	    $ins->wall_material_type = $find->wall_material_type;
	    $ins->roof_material_percentage = $find->roof_material_percentage;
	    $ins->roof_material_type = $find->roof_material_type;
	    $ins->value_added_percentage = $find->value_added_percentage;
	    $ins->value_added_type = $find->value_added_type;
	    $ins->window_type_percentage = $find->window_type_percentage;
	    $ins->is_map_set = $find->is_map_set;
	    $ins->water_percentage = $find->water_percentage;
	    $ins->electricity_percentage = $find->electricity_percentage;
	    $ins->waste_management_percentage = $find->waste_management_percentage;
	    $ins->market_percentage = $find->market_percentage;
	    $ins->hazardous_precentage = $find->hazardous_precentage;
	    $ins->drainage_percentage = $find->drainage_percentage;
	    $ins->informal_settlement_percentage = $find->informal_settlement_percentage;
	    $ins->easy_street_access_percentage = $find->easy_street_access_percentage;
	    $ins->paved_tarred_street_percentage = $find->paved_tarred_street_percentage;
	    $ins->pensioner_discount = $find->pensioner_discount;
	    $ins->disability_discount = $find->disability_discount;
	    $ins->sanitation = $find->sanitation;
	    $ins->is_rejected_pensioner = $find->is_rejected_pensioner;
	    $ins->is_rejected_disability = $find->is_rejected_disability;
	    $ins->council_group_name = $find->council_group_name;

	    $lastYearDue=$find->due!=null? $find->due: $find->property_rate_without_gst;
	    $ins->arrear_calc = $lastYearDue;
	    $ins->penalty =  round($lastYearDue * 0.25, 2);
	    // $ins->due = round(max(0, $lastYearDue + round($lastYearDue * 0.25, 2)  - 0), 2); // as amount paid in 1 day will be 0
          $ins->due = round(max(0, $lastYearDue +(int)$find->property_rate_without_gst+ round($lastYearDue * 0.25, 2)  - 0), 2); 
	    $ins->text_val = $find->text_val;

	    $ins->save();

	    return 'Row cloned successfully For Property id : ' .  $find->property_id;


}







public function propertyAssessmentSaveYearly()
{
    $currentYear = Carbon::now()->year;

    // process properties in chunks of 50
    Property::chunk(50, function ($properties) use ($currentYear) {
        foreach ($properties as $property) {
            $propertyId = $property->id;

            // Check if data for this property and year already exists
            $exists = PropertyAssessmentDetail::where('property_id', $propertyId)
                ->whereYear('created_at', $currentYear)
                ->exists();

            if ($exists) {
                continue; // skip if already exists
            }

            // Fetch the latest data for this property
            $find = PropertyAssessmentDetail::where('property_id', $propertyId)
                ->latest('created_at')
                ->first();

            if (!$find) {
                continue; // skip if no previous data
            }

            $ins = new PropertyAssessmentDetail();

            // Copy all fields
            $ins->property_id = $find->property_id;
            $ins->property_categories = $find->property_categories;
            $ins->property_wall_materials = $find->property_wall_materials;
            $ins->roofs_materials = $find->roofs_materials;
            $ins->property_window_type = $find->property_window_type;
            $ins->property_dimension = $find->property_dimension;
            $ins->length = $find->length;
            $ins->breadth = $find->breadth;
            $ins->square_meter = $find->square_meter;
            $ins->property_rate_without_gst = $find->property_rate_without_gst;
            $ins->property_gst = $find->property_gst;
            $ins->property_rate_with_gst = $find->property_rate_with_gst;
            $ins->property_use = $find->property_use;
            $ins->zone = $find->zone;
            $ins->no_of_mast = $find->no_of_mast;
            $ins->no_of_shop = $find->no_of_shop;
            $ins->no_of_compound_house = $find->no_of_compound_house;
            $ins->compound_name = $find->compound_name;
            $ins->gated_community = $find->gated_community;
            $ins->swimming_id = $find->swimming_id;
            $ins->assessment_images_2 = $find->assessment_images_2;
            $ins->assessment_images_1 = $find->assessment_images_1;
            $ins->demand_note_delivered_at = $find->demand_note_delivered_at;
            $ins->demand_note_recipient_name = $find->demand_note_recipient_name;
            $ins->demand_note_recipient_mobile = $find->demand_note_recipient_mobile;
            $ins->demand_note_recipient_photo = $find->demand_note_recipient_photo;
            $ins->last_printed_at = $find->last_printed_at;
            $ins->window_type_type = $find->window_type_type;
            $ins->total_adjustment_percent = $find->total_adjustment_percent;
            $ins->group_name = $find->group_name;
            $ins->mill_rate = $find->mill_rate;
            $ins->wall_material_percentage = $find->wall_material_percentage;
            $ins->wall_material_type = $find->wall_material_type;
            $ins->roof_material_percentage = $find->roof_material_percentage;
            $ins->roof_material_type = $find->roof_material_type;
            $ins->value_added_percentage = $find->value_added_percentage;
            $ins->value_added_type = $find->value_added_type;
            $ins->window_type_percentage = $find->window_type_percentage;
            $ins->is_map_set = $find->is_map_set;
            $ins->water_percentage = $find->water_percentage;
            $ins->electricity_percentage = $find->electricity_percentage;
            $ins->waste_management_percentage = $find->waste_management_percentage;
            $ins->market_percentage = $find->market_percentage;
            $ins->hazardous_precentage = $find->hazardous_precentage;
            $ins->drainage_percentage = $find->drainage_percentage;
            $ins->informal_settlement_percentage = $find->informal_settlement_percentage;
            $ins->easy_street_access_percentage = $find->easy_street_access_percentage;
            $ins->paved_tarred_street_percentage = $find->paved_tarred_street_percentage;
            $ins->pensioner_discount = $find->pensioner_discount;
            $ins->disability_discount = $find->disability_discount;
            $ins->sanitation = $find->sanitation;
            $ins->is_rejected_pensioner = $find->is_rejected_pensioner;
            $ins->is_rejected_disability = $find->is_rejected_disability;
            $ins->council_group_name = $find->council_group_name;

            // Arrears / penalty logic
            $lastYearDue = $find->due !== null ? $find->due : $find->property_rate_without_gst;
            $ins->arrear_calc = $lastYearDue;
            $ins->penalty = round($lastYearDue * 0.25, 2);
             $ins->due = round(max(0, $lastYearDue +(int)$find->property_rate_without_gst+ round($lastYearDue * 0.25, 2)  - 0), 2); 
            $ins->text_val = $find->text_val;

            $ins->save();

            // log each processed property
            \Log::info("Cloned yearly assessment for property {$propertyId}");
        }
    });

    return response()->json(['success' => true, 'message' => 'Bulk yearly assessment save completed.']);
}

















































// update existing propery array, due etc.. loop //not req
public function propertyAssessmentUpdate(Request $request){


     $propertyId=$request->propertyId;  // need foreach loop of property

   	// Step 1: Get all assessment rows for this property
		$assessments = PropertyAssessmentDetail::where('property_id', $propertyId)
		    ->orderBy('created_at', 'asc')
		    ->get();

     // Step 1: Get first and last year from assessments
		$firstYear = Carbon::parse($assessments->first()->created_at)->year;
		$lastYear = Carbon::parse($assessments->last()->created_at)->year;

		// Step 2: Get payments grouped by year
		$rawPayments = PropertyPayment::where('property_id', $propertyId)
		    ->selectRaw('YEAR(created_at) as year, SUM(total) as total')
		    ->groupBy('year')
		    ->pluck('total', 'year')
		    ->toArray();

		// Step 3: Fill missing years with 0
		$paymentsByYear = [];
		for ($y = $firstYear; $y <= $lastYear; $y++) {
		    $paymentsByYear[$y] = isset($rawPayments[$y]) ? (float)$rawPayments[$y] : 0;
		}

		// dd($firstYear,$lastYear,$paymentsByYear);

	// Step 3: Initialize variables
	$previousDue = null;

	foreach ($assessments as $index => $row) {
	    $year = Carbon::parse($row->created_at)->format('Y');

	    // Get property rate
	    $rate = (float)$row->property_rate_without_gst;

	    // Get amount paid for this year (0 if not found)
	    $amountPaid = isset($paymentsByYear[$year]) ? (float)$paymentsByYear[$year] : 0;

	    if ($index === 0) {
	        // First year
	        $arrears = 0;
	        $penalty = 0;
	        $due = round($rate - $amountPaid, 2);
	    } else {
	        // From second year onward
	        $arrears = $previousDue;
	        $penalty = round($arrears * 0.25, 2); // 0.25%
	        $due = round($rate + $arrears + $penalty - $amountPaid, 2);  // +rate hobe
	    }

	    // Set calculated values
	    $row->arrear_calc = $index === 0 ? 0 : $arrears;
	    $row->penalty = $index === 0 ? 0 : $penalty;
	    $row->due = $due;

	    // Save the row
	    $row->save();

	    // Update $previousDue for next loop
	    $previousDue = $due;
	}


}




// that code is in command part
//app\Console\Commands\UpdatePropertyAssessment.php
public function propertyAssessmentUpdateNew(Request $request)
{

	 // Increase max execution time (0 = unlimited)
    ini_set('max_execution_time', 0);


    // Process properties in chunks of 50
    Property::chunk(50, function ($properties) {
        foreach ($properties as $property) {
            $propertyId = $property->id;

            // === original update logic ===
            $assessments = PropertyAssessmentDetail::where('property_id', $propertyId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($assessments->isEmpty()) {
                continue;
            }

            $firstYear = Carbon::parse($assessments->first()->created_at)->year;
            $lastYear  = Carbon::parse($assessments->last()->created_at)->year;

            $rawPayments = PropertyPayment::where('property_id', $propertyId)
                ->selectRaw('YEAR(created_at) as year, SUM(total) as total')
                ->groupBy('year')
                ->pluck('total', 'year')
                ->toArray();

            $paymentsByYear = [];
            for ($y = $firstYear; $y <= $lastYear; $y++) {
                $paymentsByYear[$y] = isset($rawPayments[$y]) ? (float) $rawPayments[$y] : 0;
            }

            $previousDue = null;

            foreach ($assessments as $index => $row) {
                $year       = Carbon::parse($row->created_at)->format('Y');
                $rate       = (float) $row->property_rate_without_gst;
                $amountPaid = $paymentsByYear[$year] ?? 0;

                if ($index === 0) {
                    $arrears = 0;
                    $penalty = 0;
                    $due     = round($rate - $amountPaid, 2);
                } else {
                    $arrears = $previousDue;
                    $penalty = round($arrears * 0.25, 2); // 0.25%
                    $due     = round($rate + $arrears + $penalty - $amountPaid, 2);
                }

                $row->arrear_calc = $index === 0 ? 0 : $arrears;
                $row->penalty     = $index === 0 ? 0 : $penalty;
                $row->due         = $due;
                $row->save();

                $previousDue = $due;
            }
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'All properties updated successfully in chunks of 50.'
    ]);
}











	
    





}
