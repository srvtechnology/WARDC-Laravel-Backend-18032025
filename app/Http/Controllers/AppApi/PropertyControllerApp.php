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
use App\Models\OccupancyDetail;
use App\Models\Property_occupancies;
use App\Models\Property_property_category;
use App\Models\Property_property_type;
use App\Models\Property_property_value_added;
use App\Models\Property_property_inaccessibles;
ini_set('memory_limit','512M');

class PropertyControllerApp extends Controller
{




	public function propertySave(Request $request){
		// dd(1);
		$assessment_images = new PropertyAssessmentDetail();
		$user = Auth::guard('sanctum')->user();
		// dd($user);
		Log::debug($request->all());

		$propertyId = @$request->input('property_id');

		
		if ($propertyId && ($property = Property::find($propertyId))) {
		    $assessment_images = $property->assessment()->first();
		} elseif (
		    $property = Property::where('random_id', @$request->random_id)
		        ->where('random_id', '<>', '')
		        ->first()
		) {
		    $propertyId = $property->id;
		    $assessment_images = $property->assessment()->first();
		}

		
		 // \DB::beginTransaction();
        $rate = $this->calculateNewRate($request);  //confussed
        // dd($rate);











        /** @var Property $property */
		$property = Property::firstOrNew([
					      'id' => $propertyId,
					    'user_id' => $user->id,
					]);

		$property->assessment_area = @$request->assessment_area;
		$property->street_number = @$request->property_street_number;
		$property->street_numbernew = @$request->property_street_numbernew;
		$property->street_name = @$request->property_street_name;
		$property->ward = @$request->property_ward;
		$property->constituency = @$request->property_constituency;
		$property->section = @$request->property_section;
		$property->chiefdom = @$request->property_chiefdom;
		$property->district = @$request->property_district ?? $user->assign_district;
		$property->province = @$request->property_province;
		$property->postcode = @$request->property_postcode;

		$property->organization_addresss = @$request->organization_address ?? null;
		$property->organization_tin = @$request->organization_tin ?? null;
		$property->organization_type = @$request->organization_type ?? null;
		$property->organization_name = @$request->organization_name ?? null;

		$property->is_organization = @$request->boolean('is_organization');
		$property->is_completed = @$request->boolean('is_completed');
		// $property->is_property_inaccessible = @$request->boolean('is_property_inaccessible');
		$property->is_draft_delivered = @$request->boolean('is_draft_delivered');

		$property->delivered_name = @$request->input('delivered_name');
		$property->delivered_number = @$request->input('delivered_number');
		$property->random_id = @$request->input('random_id');
		$property->is_admin_created = 0;

		$recipient_photo = null;

        if ($request->hasFile('delivered_image')) {
            $recipient_photo = @$request->delivered_image->store(Property::DELIVERED_IMAGE);
            $property->delivered_image = $recipient_photo;
        }
        $propertyInaccessible = array_map('intval', $request->property_inaccessable);
        $property->is_property_inaccessible = ($propertyInaccessible && count($propertyInaccessible)) ? true : false;

        $property->save();

        
        foreach($propertyInaccessible as $val){

                $insInacc=new Property_property_inaccessibles;
                $insInacc->property_id=$property->id;
                $insInacc->property_inaccessible_id=$val;
                $insInacc->save();
            }


        // $property->propertyInaccessible()->sync($request->property_inaccessible); //confussed













        // =========================== LANDLOARD PART =========================//
        // “Give me the first landlord for this property, and if there isn’t one yet, create a new blank landlord instance.”
        $landlord = $property->landlord()->firstOrNew([]);

			// Handle landlord image
			$landlordImagePath = @$landlord->image;

			if ($request->hasFile('landlord_image')) {
			    if ($landlord->hasImage()) {
			        @unlink($landlord->getImage()); // Use @unlink to suppress error if file doesn't exist
			    }

			    $landlordImagePath = @$request->file('landlord_image')->store(Property::ASSESSMENT_IMAGE);
			}

			// Get landlord title label
			$landlordTitleLabel = UserTitleTypes::where('id', @$request->landlord_ownerTitle_id)->value('label');

			// Assign each field individually
			$landlord->ownerTitle = @$request->landlord_ownerTitle_id;
			$landlord->first_name = @$request->landlord_first_name;
			$landlord->middle_name = @$request->landlord_middle_name;
			$landlord->surname = @$request->landlord_surname;
			$landlord->sex = @$request->landlord_sex;
			$landlord->street_number = @$request->landlord_street_number;
			$landlord->street_numbernew = @$request->landlord_street_numbernew;
			$landlord->street_name = @$request->landlord_street_name;
			$landlord->email = @$request->landlord_email;
			$landlord->image = $landlordImagePath;
			$landlord->id_number = @$request->landlord_id_number;
			$landlord->id_type = @$request->landlord_id_type;
			$landlord->tin = @$request->landlord_tin;
			$landlord->ward = @$request->landlord_ward;
			$landlord->constituency = @$request->landlord_constituency;
			$landlord->section = @$request->landlord_section;
			$landlord->chiefdom = @$request->landlord_chiefdom;
			$landlord->district = @$request->landlord_district;
			$landlord->province = @$request->landlord_province;
			$landlord->postcode = @$request->landlord_postcode;
			$landlord->mobile_1 = @$request->landlord_mobile_1;
			$landlord->mobile_2 = @$request->landlord_mobile_2;
			$landlord->temp_street_name = "";

			// Save landlord
			$landlord->save();











			//======== Save/Update single occupancy details=============//
			$occupancy = $property->occupancy()->firstOrNew([]);

			$tenantTitleLabel = UserTitleTypes::where('id', @$request->tenant_ownerTitle_id)->first();

			// Assign values directly
			// dd($request->occupancy_type[0]);
			$occupancy->type = @$request->occupancy_type[0];
			$occupancy->ownerTenantTitle_id = @$tenantTitleLabel->id;
			$occupancy->tenant_first_name = @$request->occupancy_tenant_first_name;
			$occupancy->middle_name = @$request->occupancy_middle_name;
			$occupancy->surname = @$request->occupancy_surname;
			$occupancy->mobile_1 = @$request->occupancy_mobile_1;
			$occupancy->mobile_2 = @$request->occupancy_mobile_2;

			$occupancy->save();

			// Handle multiple occupancy types
			$requestedTypes = array_filter($request->occupancy_type ?? []);

			// if (!empty($requestedTypes)) {
			//     foreach ($requestedTypes as $type) {
			//         $property->occupancies()->firstOrCreate([
			//             'occupancy_type' => $type
			//         ]);
			//     }

			//     $property->occupancies()
			//         ->whereNotIn('occupancy_type', @$requestedTypes)
			//         ->delete();
			// }
			foreach($requestedTypes as $val){

                $insOcc=new Property_occupancies;
                $insOcc->property_id=$property->id;
                $insOcc->occupancy_type=$val;
                $insOcc->save();
            }













			//========== Save/Update assessment details==============//
			if ($property->assessment()->exists()) {
			    $assessmentModel = $property->generateAssessments();
			} else {
			    $assessmentModel = $property->assessment()->firstOrNew([]);
			}

			// Initialize all adjustment percentages
			$water_percentage = 0;
			$electrical_percentage = 0;
			$waster_percentage = 0;
			$market_percentage = 0;
			$hazardous_percentage = 0;
			$drainage_percentage = 0;
			$informal_settlement_percentage = 0;
			$easy_street_access_percentage = 0;
			$paved_tarred_street_percentage = 0;

			$groupName = $request->group_name;
			// $adjustmentPercentage = [];

			// if (is_array($request->adjustment_ids) && !empty($request->adjustment_ids)) {

			//     // Get all adjustment values in a single query
			//     $adjustmentValues = AdjustmentValue::where('group_name', $groupName)
			//         ->whereIn('adjustment_id', $request->adjustment_ids)
			//         ->pluck('percentage', 'adjustment_id');

			//     foreach ($request->adjustment_ids as $id) {
			//         $percentage = $adjustmentValues[$id] ?? 0;

			//         switch ($id) {
			//             case 1:
			//                 $water_percentage = $percentage;
			//                 break;
			//             case 2:
			//                 $electrical_percentage = $percentage;
			//                 break;
			//             case 3:
			//                 $waster_percentage = $percentage;
			//                 break;
			//             case 4:
			//                 $market_percentage = $percentage;
			//                 break;
			//             case 5:
			//                 $hazardous_percentage = $percentage;
			//                 break;
			//             case 6:
			//                 $informal_settlement_percentage = $percentage;
			//                 break;
			//             case 7:
			//                 $easy_street_access_percentage = $percentage;
			//                 break;
			//             case 8:
			//                 $paved_tarred_street_percentage = $percentage;
			//                 break;
			//             default:
			//                 $drainage_percentage = $percentage;
			//                 break;
			//         }
			//     }

			//     // Collect all percentages into an array if needed later
			//     //take the data of luck 1st parameter which is value and 2nd param is key
			//     $adjustmentPercentage = $adjustmentValues->values()->toArray();
			// }

			//  $totalAdjustmentPercent = array_sum($adjustmentPercentage);







			$district = District::where('name', $request->property_district)->first();
			$millRateGroupName = $request->group_name;
			$millRateModel = MillRate::where('group_name', $millRateGroupName)->first();
			$millRate = $millRateModel ? $millRateModel->rate : 2.25;

			// Set assessment data
			$assessmentModel->property_wall_materials        = $request->assessment_wall_materials_id;
			$assessmentModel->roofs_materials                = $request->assessment_roofs_materials_id;
			$assessmentModel->property_window_type           = $request->assessment_window_type_id;
			$assessmentModel->property_dimension             = $request->assessment_dimension_id;
			$assessmentModel->length                         = $request->assessment_length;
			$assessmentModel->breadth                        = $request->assessment_breadth;
			$assessmentModel->square_meter                   = $request->assessment_square_meter;

			$assessmentModel->property_rate_without_gst      = $request->assessmentRateWithoutGST > 0 ? $request->assessmentRateWithoutGST : $rate['rateWithoutGST'];
			$assessmentModel->property_gst                   = $request->assessmentRateWithGST > 0 ? $request->assessmentRateWithGST : $rate['GST'];
			$assessmentModel->property_rate_with_gst         = $rate['rateWithGST'];

			$assessmentModel->property_use                   = $request->assessment_use_id;
			$assessmentModel->zone                           = $request->assessment_zone_id;
			$assessmentModel->no_of_mast                     = $request->total_mast;
			$assessmentModel->no_of_shop                     = $request->total_shops;
			$assessmentModel->no_of_compound_house           = $request->total_compound_house;
			$assessmentModel->compound_name                  = $request->compound_name;
			$assessmentModel->gated_community                = $request->gated_community ? getSystemConfig(SystemConfig::OPTION_GATED_COMMUNITY) : null;

			$assessmentModel->total_adjustment_percent       =  0;
			$assessmentModel->group_name                     = $millRateGroupName;
			$assessmentModel->mill_rate                      = $millRate;

			// Material percentages & types
			$assessmentModel->wall_material_percentage       = $request->wallPer ?? 0;
			$assessmentModel->wall_material_type             = $request->wallType ?? 'A';

			$assessmentModel->roof_material_percentage       = $request->roofPer ?? 0;
			$assessmentModel->roof_material_type             = $request->roofType ?? 'A';

			$assessmentModel->value_added_percentage         = $request->valuePer ?? 0;
			$assessmentModel->value_added_type               = $request->valueType ?? 'A';

			$assessmentModel->window_type_percentage         = $request->windowPer ?? 0;
			$assessmentModel->window_type_type               = $request->windowType ?? 'A';

			// Service-related percentages not req
			$assessmentModel->water_percentage               = $water_percentage;
			$assessmentModel->electricity_percentage         = $electrical_percentage;
			$assessmentModel->waste_management_percentage    = $waster_percentage;
			$assessmentModel->market_percentage              = $market_percentage;
			$assessmentModel->hazardous_precentage           = $hazardous_percentage;
			$assessmentModel->drainage_percentage            = $drainage_percentage;
			$assessmentModel->informal_settlement_percentage = $informal_settlement_percentage;
			$assessmentModel->easy_street_access_percentage  = $easy_street_access_percentage;
			$assessmentModel->paved_tarred_street_percentage = $paved_tarred_street_percentage;
			$assessmentModel->sanitation                     = $request->sanitation;

			// Handle Image 1
			if ($request->hasFile('assessment_images_1')) {
			    if ($assessment_images->hasImageOne()) {
			        @unlink($assessment_images->getImageOne());
			    }
			    $assessmentModel->assessment_images_1 = $request->file('assessment_images_1')->store(Property::ASSESSMENT_IMAGE);
			}

			// Handle Image 2
			if ($request->hasFile('assessment_images_2')) {
			    if ($assessment_images->hasImageTwo()) {
			        @unlink($assessment_images->getImageTwo());
			    }
			    $assessmentModel->assessment_images_2 = $request->file('assessment_images_2')->store(Property::ASSESSMENT_IMAGE);
			}

			$assessmentModel->due      = $request->assessmentRateWithoutGST > 0 ? $request->assessmentRateWithoutGST : $rate['rateWithoutGST'];
			$assessmentModel->arrear_calc=0;
			$assessmentModel->penalty=0;






			// made it dynamic as per year  sms code
			if ($request->input('isDraftDelivered')) {

			    // === Part 1: Initialize Variables ===
			    $CurrentYearAssessmentAmount = 0;
			    $PastPayableDue = 0;
			    $Penalty = 0;
			    $CurrentYearTotalPayment = 0;
			    $CurrentYearTotalDue = 0;

			    // Get the current year dynamically
			    $currentYear = now()->year;

			    // === Part 2: Loop through Assessment History ===
			    foreach ($property->assessmentHistory ?? [] as $history) {
			        $AssessmentYear = $history->created_at->year;
			        $CurrentYearAssessmentAmount = $history->current_year_assessment_amount;

			        // Calculate penalty
			        $Penalty = $PastPayableDue > 0 ? $PastPayableDue * 0.25 : 0;

			        // Get payment and due
			        $CurrentYearTotalPayment = $history->getCurrentYearTotalPayment();
			        $CurrentYearTotalDue = $CurrentYearAssessmentAmount + $PastPayableDue + $Penalty - $CurrentYearTotalPayment;

			        // Carry forward past due if not current year
			        if ($AssessmentYear != $currentYear) {
			            $PastPayableDue = $CurrentYearTotalDue;
			        }
			    }

			    // === Part 3: Prepare Summary Array ===
			    $arr = [];
			    $arr['property_id'] = $property->id;
			    $arr['AssessmentYear'] = $AssessmentYear ?? '';
			    $arr['CurrentYearAssessmentAmount'] = number_format($CurrentYearAssessmentAmount, 2);
			    $arr['PastPayableDue'] = number_format($PastPayableDue, 2);
			    $arr['Penalty'] = number_format($Penalty, 2);
			    $arr['CurrentYearTotalPayment'] = number_format($CurrentYearTotalPayment ?? 0, 2);
			    $arr['CurrentYearTotalDue'] = number_format($CurrentYearTotalDue ?? 0, 2);

			    $string = implode(",", $arr); // For debug/log if needed

			    // === Part 4: Send SMS if mobile number exists ===
			    if ($mobile_number = $property->landlord->mobile_1) {

			        // (new \App\Helper\CustomHelper)->send_sms($arr, $mobile_number);

			        $name = $request->input('delivered_name');
			        $year = now()->format('Y');

			        // Validate mobile format before sending notification
			        if (preg_match('/^\+([1-9]{3})(\d{8})$/', $mobile_number)) {
			            // $property->landlord->notify(
			            //     new DraftDeliveredSMSNotification($property, $mobile_number, $name, $year)
			            // );
			        }
			    }

			    // === Update Assessment Delivery Info ===
			    $assessmentModel->demand_note_delivered_at = now();
			    $assessmentModel->demand_note_recipient_name = $request->input('delivered_name');
			    $assessmentModel->demand_note_recipient_mobile = $request->input('delivered_number');
			    $assessmentModel->demand_note_recipient_photo = $recipient_photo;
			    // dd(1);
			}

			if ($request->input('swimming_pool')) {
			    $assessmentModel->swimming()->associate($request->input('swimming_pool'));
			}


			// Finally save the model
			$assessmentModel->save();















			// Property Categories  
			// confussed codeing part

			// $categories = getSyncArray($request->input('assessment_categories_id'), ['property_id' => $property->id]);
			// $assessment->categories()->sync($categories);

			// // Property Types (Habitat) - Multiple
			// $types = getSyncArray($request->input('property_types'), ['property_id' => $property->id]);
			// $assessment->types()->sync($types);

			// // Property Type Totals (if exists) - Multiple
			// if ($request->filled('property_types_total')) {
			//     $typesTotal = getSyncArray($request->input('property_types_total'), ['property_id' => $property->id]);
			//     $assessment->typesTotal()->sync($typesTotal);
			// }

			// // Value Added Properties - Multiple
			// $valuesAdded = getSyncArray($request->input('assessment_value_added_id'), ['property_id' => $property->id]);
			// $assessment->valuesAdded()->sync($valuesAdded);














			// Geo Registry Data
			$geoRegistry = $property->geoRegistry()->firstOrNew([]);
			// dd($geoRegistry);
			$geoData=[];

			// Assign each field manually instead of using fill()
			$geoRegistry->point1 =  $request->registry_point1;
			$geoRegistry->point2 = $request->registry_point2;
			$geoRegistry->point3 = $request->registry_point3;
			$geoRegistry->point4 =$request->registry_point4;
			$geoRegistry->point5 =$request->registry_point5;
			$geoRegistry->point6 =$request->registry_point6;
			$geoRegistry->point7 =$request->registry_point7;
			$geoRegistry->point8 =$request->registry_point8;
			$geoRegistry->digital_address = $request->registry_digital_address;
			$geoRegistry->dor_lat_long = str_replace(',', ', ', $request->dor_lat_long);

			// Assign open_location_code if it exists
			
			 if ($request->dor_lat_long && count(explode(',', $request->dor_lat_long)) === 2) {
		            list($lat, $lng) = explode(',', $request->dor_lat_long);
		            // $geoRegistry->open_location_code = \OpenLocationCode\OpenLocationCode::encode($lat, $lng);
		           $geoRegistry->open_location_code = encodePlusCode($lat, $lng);


		       }

			$geoRegistry->save();














			/* --------------------------------------------------------------------------
			 | Save / Update Registry Meter Images
			 *-------------------------------------------------------------------------*/
			$registryImageIds = [];
			$existingRegistryImageIds = $property->registryMeters()->pluck('id')->toArray();

			if ($request->has('registry') && is_array($request->registry) && count($request->registry)) {
			    foreach (array_filter($request->registry) as $key => $registry) {
			        $registryId = isset($registry['id']) ? (int) $registry['id'] : null;
			        $registryImageIds[] = $registryId;

			        $imagePath = null;

			        // If new image uploaded
			        if ($request->hasFile("registry.$key.meter_image")) {
			            $existingRegistry = $property->registryMeters()->find($registryId);

			            // Delete old image if exists
			            if ($existingRegistry && $existingRegistry->image && $existingRegistry->hasImage()) {
			                @unlink($existingRegistry->getImage());
			            }

			            $imagePath = $registry['meter_image']->store(Property::METER_IMAGE);
			        }

			        // Save or update record
			        $property->registryMeters()->updateOrCreate(
			            ['id' => $registryId],
			            [
			                'number' => $registry['meter_number'],
			                'image' => $imagePath ?? null,
			            ]
			        );
			    }
			}

			/* --------------------------------------------------------------------------
			 | Delete Registry Meter Records Not Present in Current Submission
			 *-------------------------------------------------------------------------*/
			$registryIdsToDelete = array_diff($existingRegistryImageIds, array_filter($registryImageIds));

			foreach ($registryIdsToDelete as $idToDelete) {
			    $registryToDelete = $property->registryMeters()->find($idToDelete);

			    if ($registryToDelete) {
			        // Delete image file if it exists
			        if ($registryToDelete->image && $registryToDelete->hasImage()) {
			            @unlink($registryToDelete->getImage());
			        }

			        // Delete the registry meter record
			        $registryToDelete->delete();
			    }
			}









			 $getProperty = $property->with('landlord', 'occupancy', 'assessment', 'geoRegistry', 'registryMeters', 'occupancies', 'categories', 'propertyInaccessible')->where('id', $property->id)->get();

                $adjustments = [
            
                [ 'id' => '1',
                 'name' => 'Water Supply',
                 'percentage' => '3',
                 'group_name' => '"A"'
                ],
                [ 'id' => '2',
                 'name' => 'Electricity',
                 'percentage' => '3',
                 'group_name' => '"A"'
                ],
                [
                    'id'=> '3',
                    'name'=> 'Waste Management Services/Points/Locations',
                    'percentage'=> '5',
                    'group_name'=> '"A"'
                ],
                [
                    'id'=> '5',
                    'name'=> 'Hazardous Location/Environment',
                    'percentage'=> '5',
                    'group_name'=> '"A"'
                ],
                [
                    'id'=> '7',
                    'name'=> 'Easy Street Access',
                    'percentage'=> '5',
                    'group_name'=> '"A"'
                ]
     
             ];










             // -----------------------------------------------------------------------------
			// 1. Delete Previous Yearly Adjustment Entries for the Property
			// -----------------------------------------------------------------------------
			$currentYear = date("Y");

			PropertyToCounsilGroupA::where('property_id', $property->id)
			    ->where('year', $currentYear)
			    ->delete();

			// -----------------------------------------------------------------------------
			// 2. Insert New Adjustments and Calculate Total Adjustment Percentage
			// -----------------------------------------------------------------------------
			$totalAdjustmentPercent = 0;

			if (!empty($request->newAdjustmentIds)) {
			    $adjustments = json_decode($request->newAdjustmentIds);

			    $sumOfPercentage=0;
			     foreach(json_decode(@$request->newAdjustmentIds) as $val ){
		               if(@$val->amount || @$val->value){
		                }else{
		                if($val->sign=="+"){
		                 $sumOfPercentage=$sumOfPercentage+(int)$val->percentage;
		                }else{
		                  $sumOfPercentage=$sumOfPercentage-(int)$val->percentage;
		                }

		                 $insData=new PropertyToCounsilGroupA;
		                 $insData->property_id=$property->id;
		                 $insData->adjustment_id=$val->id;
		                  $insData->year=date("Y");
		                 $insData->save();
		             }// end if for amount
		            }//foreach end
			}

			// -----------------------------------------------------------------------------
			// 3. Update the Total Adjustment Percentage in Property Assessment Details
			// -----------------------------------------------------------------------------
			// dd($totalAdjustmentPercent,$sumOfPercentage);
			$totalAdjustmentPercent=$sumOfPercentage;
			PropertyAssessmentDetail::where('property_id', $property->id)
			    ->whereYear('created_at', $currentYear)
			    ->update(['total_adjustment_percent' => $totalAdjustmentPercent]);

			// -----------------------------------------------------------------------------
			// 4. Save or Update Property Type ID in PropertyToPropertyType Table
			// -----------------------------------------------------------------------------
			if (!empty($request->property_type_id)) {
			    $latestRecord = PropertyToPropertyType::latest('id')->first();

			    if ($latestRecord) {
			        $latestRecord->update([
			            'property_id' => $property->id,
			            'type_id'     => $request->property_type_id,
			        ]);
			    } else {
			        PropertyToPropertyType::create([
			            'property_id' => $property->id,
			            'type_id'     => $request->property_type_id,
			        ]);
			    }
			}









			// category,type, value added
        //Property_property_category  Property_property_type  Property_property_value_added 
			//payload [1,2,3]

        $dltallcat=Property_property_category::where('property_id',$property->id)->where('assessment_id',$assessmentModel->id)->delete();

        $propertyCategories = $request->assessment_categories_id;
        if (is_string($propertyCategories)) {
            $propertyCategories = json_decode($propertyCategories, true);
        }
        $propertyCategories = is_array($propertyCategories) ? $propertyCategories : [];

       foreach ($propertyCategories as $val) {
            $inscat=new Property_property_category;
            $inscat->property_id=$property->id;
            $inscat->property_category_id=$val;
            $inscat->assessment_id=$assessmentModel->id;
            $inscat->save();
        }





        $dltalltype=Property_property_type::where('property_id',$property->id)->where('assessment_id',$assessmentModel->id)->delete();

        $propertyTypes = $request->property_types;
        // Decode if it's a JSON string
        if (is_string($propertyTypes)) {
            $propertyTypes = json_decode($propertyTypes, true);
        }
        // Ensure it's an array
        $propertyTypes = is_array($propertyTypes) ? $propertyTypes : [];

        foreach ($propertyTypes as $val) {

            $instype=new Property_property_type;
            $instype->property_id=$property->id;
            $instype->property_type_id=$val;
            $instype->assessment_id=$assessmentModel->id;
            $instype->save();
        }





        $dltallvalue=Property_property_value_added::where('property_id',$property->id)->where('assessment_id',$assessmentModel->id)->delete();

        $propertyValueAdded = $request->assessment_value_added_id;
        // Decode if it's a JSON string like "[1,2,3]"
        if (is_string($propertyValueAdded)) {
            $propertyValueAdded = json_decode($propertyValueAdded, true);
        }
        // Ensure it's an array
        $propertyValueAdded = is_array($propertyValueAdded) ? $propertyValueAdded : [];

        // Now loop safely
        foreach ($propertyValueAdded as $val) {

            $instype=new Property_property_value_added;
            $instype->property_id=$property->id;
            $instype->property_value_added_id=$val;
            $instype->assessment_id=$assessmentModel->id;
            $instype->save();
        }





			return response()->json([
			    'success' => true,
			    'message' => 'Property data retrieved successfully.',
			    'data' => [
			        'property_id'         => $property->id,
			        'sink'                => 1,
			        'is_completed'        => $property->is_completed,
			        'property'            => $getProperty,
			        'values_adjustment'   => $adjustments,
			        'sum_of_percentage'   => $sumOfPercentage,
			        'rate'                => $rate,
			    ]
			], 200);

}




    


  //   public function calculateNewRate($request){
  //   	$result = [
		//     'rateWithoutGST' => 0,
		//     'GST' => 0,
		//     'rateWithGST' => 0,
		//     'percent_of_adjustments' => 0
		// ];

		// // Initialize default values
		// $defaultValues = [
		//     'property_category' => 0,
		//     'rate_square_meter' => 2750.00,
		//     'wall_material' => 0,
		//     'window_val' => 0,
		//     'roof_material' => 0,
		//     'value_added_val' => 0,
		//     'property_type_val' => 0,
		//     'property_dimension' => 0,
		//     'property_use' => 0,
		//     'zones' => 0,
		//     'no_of_shops' => $request->total_shops ?: 0,
		//     'no_of_mast' => $request->total_mast ?: 0,
		//     'shopValue' => 0,
		//     'mastValue' => 0,
		// ];
		// extract($defaultValues);

		// // Handle special value-added items (shops and masts)
		// $specialValueAddedIds = [8, 9];
		// $valueAdded = [];

		// if (isset($request->assessment_value_added_id) && is_array($request->assessment_value_added_id)) {
		//     foreach ($specialValueAddedIds as $value) {
		//         if (in_array($value, $request->assessment_value_added_id)) {
		//             $amount = PropertyValueAdded::where('id', $value)->value('value');
		//             ${$value == 9 ? 'shopValue' : 'mastValue'} = $amount;
		//         }
		//     }
		//     $valueAdded = array_diff($request->assessment_value_added_id, $specialValueAddedIds);
		// }

		// // Fetch property characteristics
		// $window_val = optional(PropertyWindowType::find($request->assessment_window_type_id))->value;
		// $wall_material = optional(PropertyWallMaterials::find($request->assessment_wall_materials_id))->value;
		// $roof_material = optional(PropertyRoofsMaterials::find($request->assessment_roofs_materials_id))->value;
		// $property_use = optional(PropertyUse::find($request->assessment_use_id))->value;
		// $zones = optional(PropertyZones::find($request->assessment_zone_id))->value;

		// // Calculate property dimension based on either length*breadth or direct area
		// if (isset($request->assessment_length) && isset($request->assessment_breadth)) {
		//     $property_dimension = ($request->assessment_length * $request->assessment_breadth) * $rate_square_meter;
		// } elseif (isset($request->assessment_area)) {
		//     $property_dimension = $request->assessment_area * $rate_square_meter;
		// }

		// // Calculate value added components
		// $value_added_val = PropertyValueAdded::whereIn('id', $valueAdded)->sum('value');
		// $property_type_val = PropertyType::whereIn('id', $request->property_types ?? [])->sum('value');

		// // Add shop and mast values if they exist
		// if ($shopValue > 0) {
		//     $value_added_val += ($shopValue * $no_of_shops);
		// }
		// if ($mastValue > 0) {
		//     $value_added_val += ($mastValue * $no_of_mast);
		// }

		// // Get property categories if they exist
		// $property_categories = PropertyCategory::whereIn('id', $request->assessment_categories_id ?? [])->get();

		// // Calculate the steps for final assessment
		// $swimming_pool = optional(Swimming::find($request->swimming_pool))->value;
		// $step1 = $wall_material + $roof_material + $value_added_val + $window_val + ($swimming_pool ?: 0);
		// $step2 = $property_use;
		// $step3 = $zones;
		// $step4 = $property_type_val;
		// $step0 = $property_dimension;

		// // Calculate gated community factor
		// $gated_community = $request->gated_community ? getSystemConfig(SystemConfig::OPTION_GATED_COMMUNITY) : 1;

		// // Calculate category multiplier
		// $step6 = 1;
		// if ($property_categories->isNotEmpty()) {
		//     $step6 = $property_categories->reduce(fn($carry, $item) => $carry * $item->value, 1);
		// }

		// // Calculate base rate without GST
		// $result['rateWithoutGST'] = (($step0 + ($step1 * $step2 * $step3 * $step4)) * $gated_community + ($swimming_pool ?: 0)) * $step6;

		// // Apply property characteristic percentages if they exist
		// $percentages = [
		//     'wallPer' => $request->wallPer ?: 0,
		//     'roofPer' => $request->roofPer ?: 0,
		//     'valuePer' => $request->valuePer ?: 0,
		//     'windowPer' => $request->windowPer ?: 0
		// ];

		// $totalPercentage = array_sum($percentages);
		// if ($totalPercentage) {
		//     $result['rateWithoutGST'] += $result['rateWithoutGST'] * ($totalPercentage / 100);
		// }

		// // Calculate adjustment percentages
		// $sumOfPercentage = 0;
		// if ($request->newAdjustmentIds) {
		//     $adjustments = json_decode($request->newAdjustmentIds);
		//     foreach ($adjustments as $val) {
		//         if (!isset($val->amount) && !isset($val->value)) {
		//             $sumOfPercentage += ($val->sign == "+" ? (int)$val->percentage : -(int)$val->percentage);
		//         }
		//     }
		// }

		// $result['percent_of_adjustments'] = $sumOfPercentage;

		// // Apply adjustment percentages if they exist
		// if ($request->newAdjustmentIds && count(json_decode($request->newAdjustmentIds)) > 0) {
		//     $result['rateWithoutGST'] *= (100 + $sumOfPercentage) / 100;
		// }

		// // Calculate final values with GST
		// $result['GST'] = $result['rateWithoutGST'] * 0.15;
		// $result['rateWithGST'] = round($result['rateWithoutGST'] + $result['GST'], 4);
		// $result['rateWithoutGST'] = $result['rateWithoutGST'] / 1000;

		// return $result;
  //   }




















  public function calculateNewRate($request)
    {
        $property_category = 0;
        $rate_square_meter = 2750.00;
        $wall_material = 0;
        $window_val = 0;
        $roof_material = 0;
        $value_added_val = 0;
        $property_type_val = 0;
        $property_dimension = 0;
        $property_use = 0;
        $zones = 0;
        $no_of_shops = $request->total_shops ? $request->total_shops : 0;
        $no_of_mast = $request->total_mast ? $request->total_mast : 0;
        $shopValue = 0;
        $mastValue = 0;
        $valueAdded = [8, 9];
        $property_categories = [];
        // dd($request->assessment_value_added_id);

        if (isset($request->assessment_value_added_id) && is_array($request->assessment_value_added_id)) {
            foreach ($valueAdded as $value) {
                if (in_array($value, $request->assessment_value_added_id)) {
                    $amount = PropertyValueAdded::select('value')->where('id', $value)->first();
                    if ($value == 9) {
                        $shopValue = $amount->value;
                    }
                    if ($value == 8) {
                        $mastValue = $amount->value;
                    }
                }
            }
            $valueAdded = array_diff($request->assessment_value_added_id, $valueAdded);
            // dd($shopValue,$mastValue,$valueAdded);
        }
        
        if(isset($request->assessment_window_type_id) and $request->assessment_window_type_id != null){
            $window_val = PropertyWindowType::select('value')->find($request->assessment_window_type_id);
        }
        // dd($window_val);

        if (isset($request->assessment_categories_id) and $request->assessment_categories_id != null){
            $property_categories = PropertyCategory::whereIn('id', $request->assessment_categories_id)->get();
        }
        // dd($property_categories);

        if (isset($request->assessment_wall_materials_id) and $request->assessment_wall_materials_id != null){
            $wall_material = PropertyWallMaterials::select('value')->find($request->assessment_wall_materials_id);
        }
        // dd($wall_material);

        if (isset($request->assessment_roofs_materials_id) and $request->assessment_roofs_materials_id != null){
            $roof_material = PropertyRoofsMaterials::select('value')->find($request->assessment_roofs_materials_id);
        }
        // dd($roof_material);

        if (is_array($request->assessment_value_added_id) and count($request->assessment_value_added_id) > 0){
            $value_added_val = PropertyValueAdded::whereIn('id', $valueAdded)->sum('value');
        }
        // dd($value_added_val);

        if (is_array($request->property_types) and count($request->property_types) > 0){
            $property_type_val = PropertyType::whereIn('id', $request->property_types)->sum('value');
        }
        // dd($property_type_val);

        if (isset($request->assessment_length) and $request->assessment_length != null and (isset($request->assessment_breadth) and $request->assessment_breadth != null) ) {

            if ($request->has('property_district')) {
                $district = District::where('name', $request->property_district)->first();
                if ($district->sq_meter_value) {
                    $rate_square_meter = $district->sq_meter_value;
                    // dd(1,$rate_square_meter);
                }
            }

            $property_dimension = ($request->assessment_length * $request->assessment_breadth) * $rate_square_meter;
            //$property_dimension = ($request->assessment_area) * $rate_square_meter;
            //$property_dimension = $request->property_dimension * getSystemConfig(SystemConfig::CURRENT_RATE);
            //$property_dimension = PropertyDimension::select('value')->find($request->property_dimension);
        }
        // dd($property_dimension);

        if (isset($request->assessment_area) and $request->assessment_area != null) {



            if ($request->has('property_district')) {
                $district = District::where('name', $request->property_district)->first();
                if ($district->sq_meter_value) {
                    //$rate_square_meter = $district->sq_meter_value;
                }
            }

            //$property_dimension = ($request->assessment_length * $request->assessment_breadth) * $rate_square_meter;
            $property_dimension = ($request->assessment_area) * $rate_square_meter;
            //$property_dimension = $request->property_dimension * getSystemConfig(SystemConfig::CURRENT_RATE);
            //$property_dimension = PropertyDimension::select('value')->find($request->property_dimension);
        }


        if (isset($request->assessment_use_id) and $request->assessment_use_id != null){
            $property_use = PropertyUse::select('value')->find($request->assessment_use_id);
        }

        if (isset($request->assessment_zone_id) and $request->assessment_zone_id != null){
            $zones = PropertyZones::select('value')->find($request->assessment_zone_id);
        }
        // dd($property_use,$zones);

        /*number of Shop available*/

        if ($shopValue > 0){
            $value_added_val = $value_added_val + ($shopValue * $no_of_shops);
        }
        // dd($value_added_val,$shopValue , $no_of_shops);

        /*number of mast available*/
        if ($mastValue > 0){
            $value_added_val = $value_added_val + ($mastValue * $no_of_mast);
        }
        

        // $step1 = $wall_material['value'] + $roof_material['value'] + $value_added_val;
        // $step2 = $property_type_val;
        // $step3 = $property_dimension['value'];
        // $step4 = $property_use['value'];
        // $step5 = $zones['value'];
        // $step6 = 0;
        $swimming_pool = optional(Swimming::find($request->swimming_pool))->value;
        // dd($swimming_pool);
        $step1 = optional($wall_material)->value + optional($roof_material)->value + $value_added_val + optional($window_val)->value + ($swimming_pool ? $swimming_pool : 0);
        $step2 = optional($property_use)->value;
        $step3 = optional($zones)->value;
        $step4 = $property_type_val;
        //$step3 = $property_dimension['value'];
        $step0 = $property_dimension;
        $step6 = 0;
        // dd($step4);

        // dd($step1,$step2,$step3,$step0,$step6);
        

        $gated_community = $request->gated_community ? getSystemConfig(SystemConfig::OPTION_GATED_COMMUNITY) : 1;

        if (count($property_categories) && $property_categories->count()) {
            $step6 = 1;

            foreach ($property_categories as $prop_category) {
                $step6 *= $prop_category->value;
            }
        }
        // dd($step6);

        //$result['rateWithoutGST'] = @(((($step1 * $step2 * $step3 * $step4) * $gated_community) + ($swimming_pool ? $swimming_pool : 0)) / ($step6 > 0 ? $step6 : 1));
        $result['rateWithoutGST'] = @((($step0 + ($step1 *  $step2 * $step3 * $step4)) * $gated_community)  + ($swimming_pool ? $swimming_pool : 0)) * ($step6 > 0 ? $step6 : 1);

        // dd($result['rateWithoutGST']);


        $wallMaterialPercentage = ($request->wallPer)? $request->wallPer : 0;
        $roofMaterialPercentage = ($request->roofPer)? $request->roofPer : 0;
        $valueAddedPercentage = ($request->valuePer)? $request->valuePer : 0;
        $windowTypePercentage = ($request->windowPer)? $request->windowPer : 0;

        //Total percentage of property characteristic
        $totalPercentage = array_sum([$wallMaterialPercentage, $roofMaterialPercentage, $valueAddedPercentage, $windowTypePercentage]);


        //If property characteristic exist
        if($totalPercentage){
            $result['rateWithoutGST'] = $result['rateWithoutGST'] + ($result['rateWithoutGST'] * ($totalPercentage/100));  
        }

        // dd($totalPercentage,$result);


         //----------------//new percentage code
        $sumOfPercentage=0;
         if(@$request->newAdjustmentIds){
             foreach(json_decode(@$request->newAdjustmentIds) as $val ){
                if(@$val->amount || @$val->value){
                }else{
                if($val->sign=="+"){
                 $sumOfPercentage=$sumOfPercentage+(int)$val->percentage;
                }else{
                  $sumOfPercentage=$sumOfPercentage-(int)$val->percentage;
                }
               }// end if for amont
            } // end foreach
         }
            
            $result['percent_of_adjustments'] =$sumOfPercentage;
            // //its minus or plus check that

        //If value added exist NEW CALCULATION
        
        if(@$request->newAdjustmentIds){
          if(count(json_decode(@$request->newAdjustmentIds))>0){
            $result['rateWithoutGST'] = $result['rateWithoutGST'] * ((100+($sumOfPercentage))/100); 
          }
        }


          //------------PREVIOUS CALCULATION --------------//
        // if(is_array($request->adjustment_ids) && count($request->adjustment_ids)){
        //     $adjustmentPercentage = AdjustmentValue::where('group_name', $request->group_name)->whereIn('adjustment_id', $request->adjustment_ids)->pluck('percentage')->toArray();

        //     $result['rateWithoutGST'] = $result['rateWithoutGST'] * ((100-array_sum($adjustmentPercentage))/100);            
        // }


        $result['GST'] = $result['rateWithoutGST'] * .15;

        $result['rateWithGST'] = round($result['rateWithoutGST'] + $result['GST'], 4);
        $result['rateWithoutGST'] = $result['rateWithoutGST'] / 1000;
         // dd($result);
        return $result;
    }





	
    





}
