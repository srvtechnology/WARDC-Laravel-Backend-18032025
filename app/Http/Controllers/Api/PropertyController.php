<?php

namespace App\Http\Controllers\Api;

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
use App\Models\Property_property_inaccessibles;
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
use App\Traits\FileUploadTrait;
use App\Models\User;
use App\Models\UserMain;
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
use App\Models\OccupancyDetail;
use App\Models\Property_occupancies;
use App\Models\Property_property_category;
use App\Models\Property_property_type;
use App\Models\Property_property_value_added;
use App\Models\PropertyRates;

ini_set('memory_limit','512M');

class PropertyController extends Controller
{
    use FileUploadTrait;
    // public function index()
    // {
    //     $response = [];
    //     try {
    //         $response['property'] = Property::with([
    //             'user' => function ($query) {
    //                 $query->select('id', 'first_name', 'last_name');
    //             },
    //             'landlordFew',
    //             'geoRegistry',
    //             'occupancies',
    //             'propertyInaccessible',
    //             'payments',
    //             'districts',
    //             'images',
    //             'assessment',
    //         ])->paginate(10);




    //         $response['success'] = true;
    //         return $response;
            
    //     } catch (Exception $e) {
    //         $response['error'] = $e->getMessage();
    //         return Response::json($response); 
    //     }
    // }


// public function index2(Request $request): JsonResponse
// {
//     return response()->json(['message' => abc()]);
// }


public function index(Request $request): JsonResponse
{
    try {
        $user = Auth::guard('sanctum')->user();
        $data=[];
        //  return response()->json([
        //     'user' => $user->super_admin,
        // ]);
        $query = Property::with([
            'userDetails',
            'user:id,first_name,last_name',
            'landlordFew',
            'landlord',
            'geoRegistry',
            'registryMeters',
            'occupancies',
            'occupancy',
            'propertyInaccessible',
            'payments',
            'districts',
            'images',
            'assessment',
            'assessmentsObject' => function ($query) {
                $query->with(['types', 'valuesAdded', 'categories','propertyCategoryDetails'])->latest();
            },
            'assessments.payments',
            'assessments.propertyCategoryNew',
        ])->orderBy('id','desc');

           //filter by user_id
        if ($request->filled('mobile_app')) {
            // $query->where('user_id', $user->id);
            $query->where('properties.user_id', $user->id);

            if($request->filled('property_id')){
                $query->where('properties.id', $request->property_id);
            }

            // Paginate results
           $properties = $query->paginate(10);

             return response()->json([
                    'success' => true,
                    'property' => $properties,
                    'user'=>$user->id,
                ]);
        }




        // Role-based district filter (Key: role_based_district)
        // if (!request()->user()->hasRole('Super Admin') && !request()->user()->hasRole('Super Admin Cus')) {
        //     $query->where('district', $user->assign_district);
        // }
        if (!$user || ($user->super_admin != 1 && $user->super_admin_cus != 1)) {
            $query->where('district', $user?->assign_district);
        }

        // Filter by Demand Draft Year (Key: demand_draft_year)  Done 27
        if ($request->filled('demand_draft_year')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->whereYear('created_at', $request->demand_draft_year);
            });
        }

        // Filter by Printed Status (Key: is_printed)  done 46
        if ($request->filled('is_printed')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->when($request->input('is_printed') == '1', fn($q) => $q->whereNotNull('last_printed_at'));
                $q->when($request->input('is_printed') == '0', fn($q) => $q->whereNull('last_printed_at'));
            });
        }

        // Filter by Gated Community Status (Key: gated_community)  done 47, 48
        if ($request->filled('gated_community')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('gated_community', $request->gated_community);
            });
        }

        // Filter by District ID (Fixed to 13) (Key: fixed_district_13)
        // $query->whereHas('districts', function ($q) {
        //     $q->where('id', 13);
        // });

        // Filter by Property Creation Date  //done 36, 37
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('properties.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->start_date || $request->end_date) {
            $query->when($request->start_date, fn($q) => $q->whereBetween('properties.created_at', [Carbon::parse($request->start_date), Carbon::now()]));
            $query->when($request->end_date, fn($q) => $q->whereBetween('properties.created_at', [Carbon::now()->subYear(5), Carbon::parse($request->end_date)->endOfDay()]));
        }

        // Filter by Unpaid Properties Done 38,39
       if ($request->unpaid_start_date && $request->unpaid_end_date) {
            $year = date('Y', strtotime($request->unpaid_start_date));
            $query->whereYear('created_at', $year)->doesntHave('payments');
        }


        // Filter by Payment Status (Key: payment_status) // done 42
        if ($payment_status = $request->input('paid')) {
            $query->{$payment_status == 'paid' ? 'whereHas' : 'doesntHave'}('payments');
        }

        // Filter by Paid Date Range (Key: paid_date_range) //done 40, 41
        if ($request->paid_start_date && $request->paid_end_date) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->whereBetween('property_payments.created_at', [
                    Carbon::parse($request->paid_start_date)->startOfDay(),
                    Carbon::parse($request->paid_end_date)->endOfDay()
                ]);
            });
        }

        // Filter by Occupancy Type (Key: occupancy_type)  Done 17
        // if ($request->filled('occupancy_type')) {
        //     $query->whereHas('occupancies', fn($q) => $q->where('type', $request->occupancy_type));
        // }

        // Filter by Council Adjustment (Key: counsil_adjustmnt) //done // 2
        if ($request->filled('counsil_adjustmnt')) {
            $allDataFromCounsilTable = PropertyToCounsilGroupA::pluck('property_id')->toArray();
            $query->{$request->counsil_adjustmnt == 'Yes' ? 'whereIn' : 'whereNotIn'}('properties.id', $allDataFromCounsilTable);
        }

        // Filter by Property ID (Key: property_id) //done // 1
        if ($request->filled('property_id')) {
            $property_ids = explode(",", $request->property_id);
            $query->whereIn('properties.id', $property_ids);
        }




        
        // Apply filters based on request parameters
        // done 14
        if ($request->filled('town')) {
            $query->where('properties.section', $request->town);
        }
         
         //done 10
        if ($request->filled('street_name')) {
            $query->where('properties.street_name', 'like', "%{$request->street_name}%");
        }
         
         // done 11
        if ($request->filled('street_number')) {
            $query->where('properties.street_number', $request->street_number);
        }
        
        // done 12
        if ($request->filled('postcode')) {
            $query->where('properties.postcode', $request->postcode);
        }
         
         // done 13
        if ($request->filled('ward')) {
            $query->where('properties.ward', $request->ward);
        }

        if ($request->filled('district')) {
            $query->where('properties.district', $request->district);
        }

        if ($request->filled('province')) {
            $query->where('properties.province', $request->province);
        }
         

         //done 18
        if ($request->filled('chiefdom')) {
            $query->where('properties.chiefdom', $request->chiefdom);
        }
        
        //done 19
        if ($request->filled('constituency')) {
            $query->where('properties.constituency', $request->constituency);
        }

        // Property Accessibility Filter  // done 15
        if ($request->is_accessible == "0") {
            $query->where('is_property_inaccessible', 0);
        }

        if ($request->is_accessible == "1") {
            $query->where('is_property_inaccessible', 1);
        }

         if ($request->propertyCategoryType && $request->propertyCategoryType!="all" ) {
            $query->where('category', $request->propertyCategoryType);
        }

        // Demand Draft Delivery Status Filter //done 43
        if ($request->is_draft_delivered == "0") {
            $query->whereHas('assessment', function ($q) {
                $q->whereYear('created_at', now()->format('Y'))
                  ->whereNull('demand_note_delivered_at');
            });
        }

        if ($request->is_draft_delivered == "1") { //done 43 , 44, 45
            $query->whereHas('assessment', function ($q) use ($request) {
                $year = now()->format('Y');

                if ($request->dd_start_date && $request->dd_end_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          Carbon::parse($request->dd_start_date),
                          Carbon::parse($request->dd_end_date)
                      ]);
                } elseif ($request->dd_start_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          Carbon::parse($request->dd_start_date),
                          now()
                      ]);
                } elseif ($request->dd_end_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          now()->subYear(5),
                          Carbon::parse($request->dd_end_date)
                      ]);
                } else {
                    $q->whereYear('created_at', $year)
                      ->whereNotNull('demand_note_delivered_at');
                }
            });
        }










        // Apply filters based on request parameters
        if ($request->filled('town')) {
            $query->where('properties.section', $request->town);
        }
         
         // done 10
        if ($request->filled('street_name')) {
            $query->where('properties.street_name', 'like', "%{$request->street_name}%");
        }

        if ($request->filled('street_number')) {
            $query->where('properties.street_number', $request->street_number);
        }

        if ($request->filled('postcode')) {
            $query->where('properties.postcode', $request->postcode);
        }

        if ($request->filled('ward')) {
            $query->where('properties.ward', $request->ward);
        }
        
        // done 20
        if ($request->filled('district')) {
            $query->where('properties.district', $request->district);
        }
        
        // done 21
        if ($request->filled('province')) {
            $query->where('properties.province', $request->province);
        }

        if ($request->filled('chiefdom')) {
            $query->where('properties.chiefdom', $request->chiefdom);
        }

        if ($request->filled('constituency')) {
            $query->where('properties.constituency', $request->constituency);
        }

        // Digital Address Filters
        if ($request->filled('digital_address')) {
            $query->where('properties.id', $request->digital_address);
        }

        if ($request->filled('old_digital_address')) {
            $query->where('properties.id', $request->old_digital_address);
        }

        // Property Completion Status Done 26
        if ($request->filled('is_completed')) {
            $query->where('properties.is_completed', $request->is_completed == 'yes');
        }

        // Property Type  Done 22
        if ($request->filled('type')) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('id', $request->type);
            });
        }

        // Wall Material // done 23
        if ($request->filled('wall_material')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('property_wall_materials', $request->wall_material);
            });
        }

        // Compound Name  //Done 28
        if ($request->filled('compound_name')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('compound_name', 'like', "%{$request->compound_name}%");
            });
        }

        // Property Price Range // Done 6 and 7
        if ($request->filled('form_price') && $request->filled('to_price')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->whereBetween('property_rate_without_gst', [$request->form_price, $request->to_price])
                  ->whereYear('created_at', $request->demand_draft_year);
            });
        }

        // Payee Name //done 8
        if ($request->filled('payee_name')) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('payee_name', 'like', "%{$request->payee_name}%");
            });
        }

        // Payment Method //Done 9
        if ($request->filled('payment_method')) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('payment_type', 'like', "%{$request->payment_method}%");
            });
        }

        // Roof Material //done 24
        if ($request->filled('roof_material')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('roofs_materials', $request->roof_material);
            });
        }

        // Property Dimension
        if ($request->filled('property_dimension')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('property_dimension', $request->property_dimension);
            });
        }

        // Value Added  done 25
        if ($request->filled('value_added')) {
            $query->whereHas('valueAdded', function ($q) use ($request) {
                $q->where('id', $request->value_added);
            });
        }

        // Property Inaccessible
        // done 16
        if ($request->filled('property_inaccessible')) {
            $query->whereHas('propertyInaccessible', function ($q) use ($request) {
                $q->where('id', $request->property_inaccessible);
            });
        }

        // Landlord Filters //done 29 , 30
        $query->whereHas('landlord', function ($q) use ($request) {
            if ($request->filled('owner_first_name')) {
                $q->where('first_name', 'like', "%{$request->owner_first_name}%");
            }

            if ($request->filled('owner_last_name')) {
                $q->where('surname', 'like', "%{$request->owner_last_name}%");
            }

            if ($request->filled('mobile')) {
                $q->where('mobile_1', $request->mobile);
            }
        });

        // // // Occupancy Filters  done 31, 32,33  error 1633
        // $query->whereHas('occupancy', function ($q) use ($request) {
        //     if ($request->filled('tenant_first_name')) {
        //         $q->where('tenant_first_name', 'like', "%{$request->tenant_first_name}%");
        //     }

        //     if ($request->filled('tenant_middle_name')) {
        //         $q->where('middle_name', 'like', "%{$request->tenant_middle_name}%");
        //     }

        //     if ($request->filled('tenant_last_name')) {
        //         $q->where('surname', 'like', "%{$request->tenant_last_name}%");
        //     }
        // });

        // Landlord Telephone Number  done 34
        if ($request->filled('telephone_number')) {
            $query->whereHas('landlord', function ($q) use ($request) {
                $q->where('mobile_1', 'like', "%{$request->telephone_number}%");
            });
        }

        // Open Location Code  //done // 3
        if ($request->filled('open_location_code')) {
            $query->whereHas('geoRegistry', function ($q) use ($request) {
                $q->where('open_location_code', $request->open_location_code);
            });
        }




        // Filter by user name //done 35
        if ($request->filled('name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        // Organization Type Filter  Done // 4 AND 5
        if ($request->input('is_organization') == 1 && $request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type)
                  ->where('is_organization', true);
        }

        // Non-organization Filter
        if ($request->input('is_organization') == '0') {
            $query->where('is_organization', false);
        }






        //  data to load the page with
        // $organizationTypes = collect(json_decode(file_get_contents(storage_path('data/organizationTypes.json')), true))->pluck('label', 'value');
        $data['organizationTypes'] = [];//$organizationTypes;

        $data['types'] = PropertyType::pluck('label', 'id')->prepend('Property Type', '')->toArray();
        $data['wallMaterial'] = PropertyWallMaterials::pluck('label', 'id')->prepend('Wall Material', '')->toArray();
        $data['roofMaterial'] = PropertyRoofsMaterials::pluck('label', 'id')->prepend('Roof Material', '')->toArray();
        $data['propertyDimension'] = PropertyDimension::pluck('label', 'id')->prepend('Dimensions', '')->toArray();
        $data['valueAdded'] = PropertyValueAdded::where('is_active', true)->pluck('label', 'id')->prepend('Value Added', '')->toArray();
        $data['town'] = BoundaryDelimitation::distinct()->orderBy('section')->pluck('section', 'section')->prepend('Select Town', '')->toArray();
         $data['occupancy_type'] = ['Owned Tenancy' => 'Owned Tenancy', 'Rented House' => 'Rented House', 'Unoccupied House' => 'Unoccupied House'];





          // if ($user->hasRole('Super Admin')) {
            if ($user && $user->super_admin == 1) {
                if (getPropertyWardPermission('property_filter')) {
                    $user_assigned_property = UserAssignedProperty::where('user_id', \Auth::guard('admin')->user()->id)->pluck('ward_id');
                    $data['ward'] = BoundaryDelimitation::whereIn('ward', $user_assigned_property)
                        ->orderBy('ward')
                        ->pluck('ward', 'ward')
                        ->sort()
                        ->prepend('Select All Ward', '');
                } else {
                    $data['ward'] = BoundaryDelimitation::distinct()
                        ->orderBy('ward')
                        ->pluck('ward', 'ward')
                        ->sort()
                        ->prepend('Select All Ward', '');
                }

                $data['district'] = BoundaryDelimitation::distinct()->orderBy('district')->pluck('district', 'district')->sort()->prepend('Select District', '');
                $data['province'] = BoundaryDelimitation::distinct()->orderBy('province')->pluck('province', 'province')->sort()->prepend('Select Province', '');
                $data['chiefdom'] = BoundaryDelimitation::where('chiefdom', '!=', 'Kaffu Bullom')->distinct()->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort()->prepend('Select Chiefdom', '');
                $data['constituency'] = BoundaryDelimitation::distinct()->orderBy('constituency')->pluck('constituency', 'constituency')->sort()->prepend('Select Constituency', '');
            } elseif ($user->super_admin_cus != 1) {
                $data['district'] = BoundaryDelimitation::distinct()->orderBy('district')->pluck('district', 'district')->sort()->prepend('Select District', '');
                $data['province'] = BoundaryDelimitation::distinct()->orderBy('province')->pluck('province', 'province')->sort()->prepend('Select Province', '');
                $data['ward'] = BoundaryDelimitation::distinct()->orderBy('ward')->pluck('ward', 'ward')->sort()->prepend('Select All Ward', '');
                // $data['chiefdom'] = BoundaryDelimitation::distinct()->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort()->prepend('Select Chiefdom', '');
                $data['chiefdom'] = BoundaryDelimitation::where('chiefdom', '!=', 'Kaffu Bullom')
                    ->distinct()
                    ->orderBy('chiefdom')
                    ->pluck('chiefdom', 'chiefdom')
                    ->sort()
                    ->prepend('Select Chiefdom', '');

                $data['constituency'] = BoundaryDelimitation::distinct()->orderBy('constituency')->pluck('constituency', 'constituency')->sort()->prepend('Select Constituency', '');
            } else {
                $data['district'] = BoundaryDelimitation::where('district', $user->assign_district)
                    ->distinct()
                    ->orderBy('district')
                    ->pluck('district', 'district')
                    ->sort()
                    ->prepend('Select District', '');

                $data['province'] = BoundaryDelimitation::where('district', $user->assign_district)
                    ->distinct()
                    ->orderBy('province')
                    ->pluck('province', 'province')
                    ->sort()
                    ->prepend('Select Province', '');

                $data['ward'] = BoundaryDelimitation::where('district', $user->assign_district)
                    ->distinct()
                    ->orderBy('ward')
                    ->pluck('ward', 'ward')
                    ->sort()
                    ->prepend('Select All Ward', '');

                $data['chiefdom'] = BoundaryDelimitation::where('district', $user->assign_district)->where('chiefdom', '!=', 'Kaffu Bullom')
                    ->distinct()
                    ->orderBy('chiefdom')
                    ->pluck('chiefdom', 'chiefdom')
                    ->sort()
                    ->prepend('Select Chiefdom', '');

                $data['constituency'] = BoundaryDelimitation::where('district', $user->assign_district)
                    ->distinct()
                    ->orderBy('constituency')
                    ->pluck('constituency', 'constituency')
                    ->sort()
                    ->prepend('Select Constituency', '');
            }



            $data['digital_address'] = PropertyGeoRegistry::distinct()
                ->orderBy('property_id')
                ->pluck('digital_address', 'digital_address')
                ->sort()
                ->prepend('Select Digital Address', '');

            $data['request'] = $request->all();

            $data['property_inaccessibles'] = PropertyInaccessible::where('is_active', 1)
                ->pluck('label', 'id')
                ->prepend('Select Property Inaccessible');

            $data['street_names'] = Property::distinct('street_name')
                ->orderBy('street_name')
                ->pluck('street_name', 'street_name');

            $data['street_numbers'] = Property::distinct('street_number')
                ->orderBy('street_number')
                ->pluck('street_number', 'street_number');

            $data['postcodes'] = Property::distinct('postcode')
                ->orderBy('postcode')
                ->pluck('postcode', 'postcode');

            // $data['organizationTypes'] = $organizationTypes ?? [];



                if ($request->download_pdf_in_bulk == 1) {
                $bulkDemand = new PropertyInBulk();
                return $bulkDemand->handle(Property::all(), $request->demand_draft_year);
            }

            if ($request->download_stickers == 1) {
                $stickers = new PropertyStickers();

                $nProperty = Property::withAssessmentCalculation($request->input('demand_draft_year'))
                    ->having('current_year_payment', '>', 0)
                    ->having('total_payable_due', 0)
                    ->orderBy('total_payable_due')
                    ->get();

                return $stickers->handle($nProperty, $request);
            }

            if ($request->download_notice == 1) {
                $notices = new PropertyNotice();
                return $notices->handle(Property::latest()->get());
            }

            if ($request->download_excel_in_bulk == 1) {
                $properties = Property::with([
                    'assessment' => function ($query) use ($request) {
                        $query->whereYear('created_at', $request->input('demand_draft_year'))
                            ->with(
                                'categories', 'types', 'valuesAdded', 
                                'dimension', 'wallMaterial', 'roofMaterial', 
                                'zone', 'swimming'
                            );
                    },
                ])->whereHas('assessment', function ($query) use ($request) {
                    $query->whereYear('created_at', $request->input('demand_draft_year'));
                })->get();

                return \Excel::download(new PropertyExport($properties), now()->format('Y-m-d-H-i-s') . '-mod-properties.xlsx');
            }

            if ($request->bulk_demand == 2 && Property::count() > 0) {
                $coordinates = $this->getMapCoordinates();
                $points = $coordinates[0];
                $center = $coordinates[1];

                return response()->json([
                    'success' => true,
                    'message' => 'Map coordinates retrieved successfully',
                    'points' => $points,
                    'center' => $center,
                ]);
            }

           

        
        

        

        // Paginate results
        $properties = $query->paginate(50);

        return response()->json([
            'success' => true,
            'property' => $properties,
            'data'=>$data,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

















public function propertyDetails(Request $request){
     // try {
        $user = Auth::guard('sanctum')->user();
        $property = Property::where('id',$request->property_id)->first();
        if(!$property){
            return response()->json([
                    'success' => false,                   
                    'message'=>"Property not found.",
                ]);
        }

        // Generate current year assessment if missing
        // $property->generateAssessments(); //confussed

         // load sub modals
         $property = Property::with([
                'images',
                'occupancy',
                'assessments' => function ($query) {
                    $query->with(['types', 'valuesAdded', 'categories','propertyCategoryDetails'])->latest();
                },
                'assessments.payments',
                'assessments.propertyCategoryNew',
                // 'assessments.propertyCategoryDetails',
                'geoRegistry',
                'payments',
                'payments.admin',
                'landlord',
                'propertyInaccessible',
                'registryMeters'
            ])->where('id',$request->property_id)->first();

        $data=[];
        
        if($user->super_admin == 1){
            $data['town'] = BoundaryDelimitation::distinct()->orderBy('section')->pluck('section', 'section');
            $data['chiefdom'] = BoundaryDelimitation::distinct()->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort();
            $data['district'] = BoundaryDelimitation::distinct()->orderBy('district')->pluck('district', 'district')->sort();
            $data['province'] = BoundaryDelimitation::distinct()->orderBy('province')->pluck('province', 'province')->sort();
            $data['ward'] = BoundaryDelimitation::distinct()->orderBy('ward')->pluck('ward', 'ward')->sort();
            $data['constituency'] = BoundaryDelimitation::distinct()->orderBy('constituency')->pluck('constituency', 'constituency')->sort();
        }else{
             $data['town'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('section')->pluck('section', 'section');
            $data['chiefdom'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort();
            $data['district'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('district')->pluck('district', 'district')->sort();
            $data['province'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('province')->pluck('province', 'province')->sort();
            $data['ward'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('ward')->pluck('ward', 'ward')->sort();
            $data['constituency'] = BoundaryDelimitation::distinct()->where('district', $user->assign_district)->orderBy('constituency')->pluck('constituency', 'constituency')->sort();
        }




        $data['categories'] = PropertyCategory::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['types'] = PropertyType::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['window_types'] = PropertyWindowType::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['wall_materials'] = PropertyWallMaterials::distinct()->where('is_active', 1)->pluck('label','id');
        $data['sanitation'] = PropertySanitationType::pluck('label','id');
        $data['adjustment_values'] = Adjustment::pluck('name','id');
        $data['roofs_materials'] = PropertyRoofsMaterials::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['property_dimension'] = PropertyDimension::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['value_added'] = PropertyValueAdded::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['property_use'] = PropertyUse::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['zone'] = PropertyZones::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['occupancy_type'] = ['Owned Tenancy' => 'Owned Tenancy', 'Rented House' => 'Rented House', 'Unoccupied House' => 'Unoccupied House'];
        $data['id_type'] = ['National ID' => 'National ID', 'Passport' => 'Passport', 'Driver’s License' => 'Driver’s License', 'Voter ID' => 'Voter ID', 'other' => 'Other'];
        $data['org_type'] = ['Government' => 'Government', 'NGO' => 'NGO', 'Business' => 'Business', 'School' => 'School', 'Religious' => 'Religious', 'Diplomatic Mission' => 'Diplomatic Mission', 'Hospital' => 'Hospital', 'Other' => 'Other'];
        $data['gender'] = ['m' => 'Male', 'f' => 'Female'];
        $data['usertitles'] = UserTitleTypes::distinct()->where('is_active', 1)->pluck('label', 'id');
        $data['title'] = 'Details';
        $data['property'] = $property;
        $data['selected_occupancies'] = $property->occupancies->pluck('occupancy_type')->toArray();

        $data['property_inaccessable'] = PropertyInaccessible::where('is_active', 1)->pluck('label', 'id')->toArray();
        // $data['selected_property_inaccessable'] = $property->propertyInaccessible()->pluck('id')->toArray();
        $data['swimmings'] = Swimming::where('is_active', 1)->pluck('label', 'id')->prepend('Select', '')->toArray();
        
        $data['ab_2020_data']=PropertyAssessmentDetail::where('created_at', '>', '2019-12-12')->where('property_id',$request->property)->first();

        $data['gated_community'] = [1 => 'Yes', 0 => 'No'];
        $data['all_counsil']=DB::table('counsil_adjustment_group_a')->get();


       $allAssesments = PropertyAssessmentDetail::where('property_id', $request->property_id)
            ->select('id','created_at','arrear_calc', 'penalty as penalty_amount','due','property_rate_without_gst','property_rate_with_gst','demand_note_recipient_photo')->orderBy('created_at')
            ->get();


        foreach ($allAssesments as $key => $val) {
            $year = \Carbon\Carbon::parse($val->created_at)->year;

            $payment = PropertyPayment::where('property_id', $request->property_id)
                ->whereYear('created_at', $year)
                ->sum('total'); 

            $allAssesments[$key]['paymentAmount'] = $payment ?? 0;
        }


       // Council adjustments
            $adjustments = DB::table('property_to_counsil_adjustment_group_a')
                ->where('property_id', $request->property_id)
                ->get();

            foreach ($adjustments as $key => $val) {
                $adjustmentsDetails = DB::table('counsil_adjustment_group_a')
                    ->where('id', $val->adjustment_id) // assuming this column exists
                    ->get();

                // Attach the details directly, no need to json_encode manually
                $adjustments[$key]->adjustmentsDetails = $adjustmentsDetails;
            }

            



         return response()->json([
            'success' => true,
            'property' => $property,
            'data'=>$data,
            'allAssesments'=>$allAssesments,
            'allAdjustments'=>@$adjustments
        ]);
    // } catch (\Throwable $e) {
    //     return response()->json([
    //         'success' => false,
    //         'error' => $e->getMessage()
    //     ], 500);
    // }

}


public function destroy(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => "Property not found.",
            ], 404);
        }

        $property->delete();

        return response()->json([
            'success' => true,
            'message' => "Property deleted successfully.",
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}























public function updateLandlord(Request $request)
{
    $validator = Validator::make($request->all(), [
        'property_id' => 'required|integer',
        'landlord_id' => 'required|integer',
        'is_organization' => 'required',

        // Organization fields
        'organization_name' => 'nullable|string|max:255',
        'organization_type' => 'nullable|string|max:255',
        'organization_tin' => 'nullable|string|max:255',
        'organization_addresss' => 'nullable|string|max:255',

        // Personal fields
        'first_name' => 'required_if:is_organization,false|nullable|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'surname' => 'required_if:is_organization,false|nullable|string|max:255',
        'sex' => 'required_if:is_organization,false|nullable|string|max:255',

        // Common fields
        'street_number' => 'required|string',
        'street_name' => 'nullable|string|max:255',
        'email' => 'nullable|email',
        'tin' => 'nullable|string|max:255',
        'id_type' => 'nullable|string|max:255',
        'id_number' => 'nullable|string|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',

        'ward' => 'required|string',
        'constituency' => 'required|string',
        'section' => 'required|string|max:255',
        'chiefdom' => 'required|string|max:255',
        'district' => 'required|string|max:255',
        'province' => 'required|string|max:255',
        // 'postcode' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
            'message' => 'Validation failed'
        ], 422);
    }

    // Convert is_organization to boolean
    $isOrganization = filter_var($request->is_organization, FILTER_VALIDATE_BOOLEAN);

    try {
        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $landlord = LandlordDetail::find($request->landlord_id);
        if (!$landlord) {
            return response()->json(['status' => false, 'message' => 'Landlord not found'], 404);
        }

        // ---------------------
        // Update Property Data
        // ---------------------
        $property->is_organization = $isOrganization;

        if ($isOrganization) {
            $property->organization_name = $request->organization_name;
            $property->organization_type = $request->organization_type;
            $property->organization_tin = $request->organization_tin;
            $property->organization_addresss = $request->organization_addresss;
        } else {
            // Clear organization fields
            $property->organization_name = null;
            $property->organization_type = null;
            $property->organization_tin = null;
            $property->organization_addresss = null;
        }

        $property->save();

        // ----------------------
        // Update Landlord Data
        // ----------------------
        $landlordData = [
            'street_number' => $request->street_number,
            'street_name' => $request->street_name,
            'email' => $request->email,
            'tin' => $request->tin,
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'ward' => $request->ward,
            'constituency' => $request->constituency,
            'section' => $request->section,
            'chiefdom' => $request->chiefdom,
            'district' => $request->district,
            'province' => $request->province,
            'postcode' => @$request->postcode,
            'email'=>@$request->email,
            'id_number'=>@$request->id_number,
            'id_type'=>@$request->id_type,
        ];

        if (!$isOrganization) {
            $landlordData['first_name'] = $request->first_name;
            $landlordData['middle_name'] = $request->middle_name;
            $landlordData['surname'] = $request->surname;
            $landlordData['sex'] = $request->sex;
        }

       

        $landlord->update($landlordData);

        return response()->json([
            'status' => true,
            'message' => 'Landlord details updated successfully',
            'data' => [
                'property' => $property,
                'landlord' => $landlord
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
    }
}

















public function updateProperty(Request $request)
{
    $validator = Validator::make($request->all(), [
        'property_id' => 'required|integer',
      

        'ward' => 'required|string',
        'constituency' => 'required|string',
        'section' => 'required|string|max:255',
        'chiefdom' => 'required|string|max:255',
        'district' => 'required|string|max:255',
        'province' => 'required|string|max:255',
        // 'postcode' => 'required|string|max:255',
        
        'is_draft_delivered' => 'required',
        // 'delivered_name' => 'required',
        // 'delivered_number' => 'required',
        // 'delivered_image' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
            'message' => 'Validation failed'
        ], 422);
    }

    // Convert is_organization to boolean
    $isOrganization = filter_var($request->is_organization, FILTER_VALIDATE_BOOLEAN);

    try {
        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $propertyInaccessible= array_map('intval', explode(',', $request->property_inaccessable));

      

        // ---------------------
        // Update Property Data
        // ---------------------
             $property->street_number = @$request->street_number;
              $property->street_name = @$request->street_name;
            $property->ward = @$request->ward;
            $property->constituency = @$request->constituency;
            $property->section = @$request->section;
            $property->chiefdom = @$request->chiefdom;

            $property->district = @$request->district;
            $property->province = @$request->province;
            $property->postcode = @$request->postcode;
            $property->chiefdom = @$request->chiefdom;

            $property->is_property_inaccessible = ($propertyInaccessible && count($propertyInaccessible)) ? true : false;
            $property->is_draft_delivered = @$request->is_draft_delivered;
            $property->delivered_name = @$request->delivered_name;
            $property->delivered_number = @$request->delivered_number;

             if (@$request->hasFile('delivered_image')) {
                $file = @$request->file('delivered_image');

                // Define a unique name with directory structure
                $filePath = 'property/delivered/image';
                $path = $this->uploadFile($file, $filePath);

                // Optionally: save the path to DB
                $property->delivered_image = $path;
            
            }


            $property->save();

             // $property->propertyInaccessible()->sync($propertyInaccessible);
             //store to Property_property_inaccessibles model first delete and then insert

            $dltall=Property_property_inaccessibles::where('property_id',@$request->property_id)->delete();

            foreach($propertyInaccessible as $val){

                $insInacc=new Property_property_inaccessibles;
                $insInacc->property_id=$request->property_id;
                $insInacc->property_inaccessible_id=$val;
                $insInacc->save();
            }


      

        return response()->json([
            'status' => true,
            'message' => 'Property details updated successfully',
            
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ], 500);
    }
}

















// updateOccupency

public function updateOccupency(Request $request)
{
    // return response()->json(['data' => $request->occupancy_type, 'message' => 'Property not found']);
    $validator = Validator::make($request->all(), [
        "occupancy_id" => "required",
        "property_id" => "required",
        'occupancy_type' => 'nullable|array',
        'occupancy_type.*' => 'nullable|in:Owned Tenancy,Rented House,Unoccupied House',
        "tenant_first_name" => "nullable|string|max:50",
        "middle_name" => "nullable|string|max:40",
        "surname" => "nullable|string|max:30",
        "mobile_1" => "nullable|string|max:15",
        "mobile_2" => "nullable|string|max:15"
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $data = $request->all();

    try {
        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $occupancy =OccupancyDetail::find($request->occupancy_id);
        if (!$occupancy) {
            return response()->json(['status' => false, 'message' => 'Occupency not found'], 404);
        }

        $occupancy->tenant_first_name=$request->tenant_first_name;
        $occupancy->middle_name=$request->middle_name;
        $occupancy->surname=$request->surname;
        $occupancy->mobile_1=$request->mobile_1;
        $occupancy->mobile_2=$request->mobile_2;
        $occupancy->save();

        // Sync occupancy types
        //delete and then insert
        $dltall=Property_occupancies::where('property_id',$request->property_id)->delete();

            foreach($request->occupancy_type as $val){

                $insOcc=new Property_occupancies;
                $insOcc->property_id=$request->property_id;
                $insOcc->occupancy_type=$val;
                $insOcc->save();
            }

        

        return response()->json([
            'status' => true,
            'message' => 'Occupancy details updated successfully.',
            'data' => $occupancy
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
             'status' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}



















public function updateAssessment(Request $request)
{
    // return response()->json(['data' => $request->occupancy_type, 'message' => 'Property not found']);
    $validator = Validator::make($request->all(), [
           "assessment_id" => "required|integer",
            "property_id" => "required|integer",
            'property_categories' => 'nullable',
            'property_categories.*' => 'nullable',
            // "property_types" => "required|max:2",
            // "property_types.*" => 'required|exists:property_types,id',
            "property_types_total" => "nullable|max:2",
            // "property_types_total.*" => 'nullable',
            "property_wall_materials" => "required|integer",
            "roofs_materials" => "required|integer",
            "property_dimension" => "nullable|integer",
            "property_value_added.*" => "required",
            "property_use" => "required|integer",
            "zone" => "required|integer",
            'assessment_images_1' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'assessment_images_2' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $data = $request->all();
    $rate = $this->calculateNewRate($request);

    // return response()->json(['rate'=>$rate]);

    // try {
        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $assessment =PropertyAssessmentDetail::find($request->assessment_id);
        if (!$assessment) {
            return response()->json(['status' => false, 'message' => 'Assesment not found'], 404);
        }

        $assessment->gated_community=$request->gated_community;
        $assessment->length=$request->length;
        $assessment->breadth=$request->breadth;
        $assessment->square_meter=$request->length*$request->breadth;
        $assessment->roofs_materials=$request->roofs_materials;
        $assessment->property_wall_materials=$request->property_wall_materials;
        $assessment->swimming_id=$request->swimming_pool;

        $assessment->property_use=$request->property_use;
        $assessment->zone=$request->zone;
        $assessment->no_of_shop=$request->no_of_shop;
        $assessment->no_of_mast=$request->no_of_mast;
        $assessment->no_of_compound_house=$request->no_of_compound_house;
        $assessment->compound_name=$request->compound_name;
        $assessment->manual_edit=$request->manual_edit;
        $assessment->arrear_calc=$request->manual_edit =="Y" ? $request->arrear_calc:$assessment->arrear_calc;
        // $assessment->due=$request->due; // while update due will be chnage as per new assesmt value
        
       $paymentAmount = PropertyPayment::where('property_id', $request->property_id)
                ->whereYear('created_at', $assessment->created_at->year)
                ->sum('total');

        $arrear =$request->manual_edit =="Y"?  (float)$request->arrear_calc:  (float)$assessment->arrear_calc;;
        $rateWithoutGST = (float) ($rate['rateWithoutGST'] ?? 0);

        $assessment->due = $request->manual_edit =="Y" ? $request->due :  round(
            max(0, $rateWithoutGST + $arrear + ($arrear * 0.25) - $paymentAmount),
            2
        );

        $arrears = $request->manual_edit =="Y" ? $request->arrear_calc:$assessment->arrear_calc;
        $assessment->penalty = $arrears > 0 ? round($arrears * 0.25, 2) : 0;



        $assessment->property_rate_without_gst=$request->manual_edit =="Y" ?$request->property_rate_without_gst:$rate['rateWithoutGST'];
        // $assessment->property_rate_with_gst=$request->property_rate_with_gst; new cmt
        // $assessment->property_gst=$request->property_gst;  //new cmt

        // $assessment->property_rate_without_gst=$rate['rateWithoutGST'];
        $assessment->property_rate_with_gst=$rate['GST']; 
        $assessment->property_gst=$rate['rateWithGST']; 

       

       // ----start for new counsil adjustment step -1 for insert update and delete and add ---------//
             //first find and delete previous data
             $srch=PropertyToCounsilGroupA::where('property_id',$property->id)->where('year',$request->council_year)->first();

             if($srch){
                $dltall=PropertyToCounsilGroupA::where('property_id',$property->id)->where('year',$request->council_year)->delete();
             }
             //insert new data
             $sumOfPercentage=0;

            $councils = $request->council;
            // Decode JSON string if necessary
            if (is_string($councils)) {
                $councils = json_decode($councils, true);
            }
            // Ensure it's an array
            $councils = is_array($councils) ? $councils : [];


            if(@$request->council){
             foreach(@$councils as $val ){
               // if(@$val->amount || @$val->value){
               //  }else{
                //find counsil adjustment details
                $adjustmentDetails=CounsilAdjustmentGroupA::where('id',$val)->first();
                // dd($adjustmentDetails);

                if($adjustmentDetails->sign=="+"){
                 $sumOfPercentage=$sumOfPercentage+(int)$adjustmentDetails->percentage;
                }else{
                  $sumOfPercentage=$sumOfPercentage-(int)$adjustmentDetails->percentage;
                }

                 $insData=new PropertyToCounsilGroupA;
                 $insData->property_id=$property->id;
                 $insData->adjustment_id=$val;
                 $insData->year=$request->council_year;
                 $insData->save();

             // }// end if for amount
            }//foreach end
           }

           $prevData=PropertyAssessmentDetail::where('property_id',$property->id)->where('id',$request->assessment_id)->first();
           $prevPercent=$prevData->total_adjustment_percent;
           // dd($prevPercent);



            //update the percentage to propert assement details table
            $updt=PropertyAssessmentDetail::where('property_id',$property->id)->where('id',$request->assessment_id)->update(['total_adjustment_percent'=>$sumOfPercentage]);

            // $baseAmount=($request->property_rate_without_gst*100)/ (100+($prevPercent)); // new cmt
            // dd($data['property_rate_without_gst']);

                // $newRateAmount =  $baseAmount * ((100+($sumOfPercentage))/100);  //new cmt
         // dd($baseAmount,$prevPercent,$data['property_rate_without_gst'],$sumOfPercentage,$newRateAmount);
              
            // ------------------------------------ end-1 -----------------------------------------


        // $assessment->property_rate_without_gst = $newRateAmount; // new c        //image part
        if ($request->hasFile('image1')) {
            $file = $request->file('image1');
            $filePath = 'property/assessment/image';
            $path = $this->uploadFile($file, $filePath);
            if ($path) {
                $assessment->assessment_images_1 = $path;
            }
        }


        if ($request->hasFile('image2')) {
            $file = $request->file('image2');
            $filePath = 'property/assessment/image';
            $path = $this->uploadFile($file, $filePath);
            if ($path) {
                $assessment->assessment_images_2 = $path;
            }
        }

        $assessment->save();

        //delete and insert
        // category,type, value added
        //Property_property_category  Property_property_type  Property_property_value_added

        $dltallcat=Property_property_category::where('property_id',$request->property_id)->where('assessment_id',$request->assessment_id)->delete();

        $propertyCategories = $request->property_categories;
        if (is_string($propertyCategories)) {
            $propertyCategories = json_decode($propertyCategories, true);
        }
        $propertyCategories = is_array($propertyCategories) ? $propertyCategories : [];

       foreach ($propertyCategories as $val) {
            $inscat=new Property_property_category;
            $inscat->property_id=$request->property_id;
            $inscat->property_category_id=$val;
            $inscat->assessment_id=$request->assessment_id;
            $inscat->save();
        }





        $dltalltype=Property_property_type::where('property_id',$request->property_id)->where('assessment_id',$request->assessment_id)->delete();

        $propertyTypes = $request->property_types;
        // Decode if it's a JSON string
        if (is_string($propertyTypes)) {
            $propertyTypes = json_decode($propertyTypes, true);
        }
        // Ensure it's an array
        $propertyTypes = is_array($propertyTypes) ? $propertyTypes : [];

        foreach ($propertyTypes as $val) {

            $instype=new Property_property_type;
            $instype->property_id=$request->property_id;
            $instype->property_type_id=$val;
            $instype->assessment_id=$request->assessment_id;
            $instype->save();
        }





        $dltallvalue=Property_property_value_added::where('property_id',$request->property_id)->where('assessment_id',$request->assessment_id)->delete();

        $propertyValueAdded = $request->property_value_added;
        // Decode if it's a JSON string like "[1,2,3]"
        if (is_string($propertyValueAdded)) {
            $propertyValueAdded = json_decode($propertyValueAdded, true);
        }
        // Ensure it's an array
        $propertyValueAdded = is_array($propertyValueAdded) ? $propertyValueAdded : [];

        // return response()->json([
        // 'status' => true,
        // 'data' => $propertyValueAdded
        //  ], 200);

        // Now loop safely
        foreach ($propertyValueAdded as $val) {

            $instype=new Property_property_value_added;
            $instype->property_id=$request->property_id;
            $instype->property_value_added_id=$val;
            $instype->assessment_id=$request->assessment_id;
            $instype->save();
        }

 
        

        

        return response()->json([
            'status' => true,
            'message' => 'assessment details updated successfully.',
            'data' => $assessment
        ], 200);

    // } catch (\Exception $e) {
    //     return response()->json([
    //          'status' => false,
    //         'message' => 'Server error: ' . $e->getMessage()
    //     ], 500);
    // }
}
















  public function calculateNewRate($request)
    {
        $propertyDetails = Property::find($request->property_id);
        $categoryType=$propertyDetails->category;
        $rate_square_meter = 2750.00;
        $rateSqrMtr=PropertyRates::where('category',$categoryType)->first();
        if($rateSqrMtr){
         $rate_square_meter = round((float) $rateSqrMtr->value, 2);

        }

        $assessmentDetails=PropertyAssessmentDetail::find($request->assessment_id);
        $property_category = 0;
        // $rate_square_meter = 2750.00;
        $wall_material = 0;
        $window_val = 0;
        $roof_material = 0;
        $value_added_val = 0;
        $property_type_val = 0;
        $property_dimension = 0;
        $property_use = 0;
        $zones = 0;
        $no_of_shops = $request->total_shops ? $request->total_shops : $assessmentDetails->no_of_shop;
        $no_of_mast = $request->total_mast ? $request->total_mast : $assessmentDetails->no_of_mast;
        $shopValue = 0;
        $mastValue = 0;
        $valueAdded = [8, 9];
        $property_categories = [];
        // dd($request->property_value_added);
        // return response()->json([
        //     'data' =>  json_decode($request->property_types)
        // ], 200);

        if (isset($request->property_value_added) && is_array(json_decode($request->property_value_added))) {
            foreach ($valueAdded as $value) {
                if (in_array($value, json_decode($request->property_value_added))) {
                    $amount = PropertyValueAdded::select('value')->where('id', $value)->first();
                    if ($value == 9) {
                        $shopValue = $amount->value;
                    }
                    if ($value == 8) {
                        $mastValue = $amount->value;
                    }
                }
            }
            $valueAdded = array_diff(json_decode($request->property_value_added), $valueAdded);
            // dd($shopValue,$mastValue,$valueAdded);
        }
        
        if(isset($assessmentDetails->property_window_type) and $assessmentDetails->property_window_type != null){
            $window_val = PropertyWindowType::select('value')->find($assessmentDetails->property_window_type);
        }
        // dd($window_val);

        if (isset($request->property_categories) and $request->property_categories != null){
            $property_categories = PropertyCategory::whereIn('id', json_decode($request->property_categories))->get();
        }
        // dd($property_categories);
        //  return response()->json([
        //     'property_categories' => $property_categories  //0
        // ], 200);

        if (isset($request->property_wall_materials) and $request->property_wall_materials != null){
            $wall_material = PropertyWallMaterials::select('value')->find($request->property_wall_materials);
        }
        // dd($wall_material);

        if (isset($request->roofs_materials) and $request->roofs_materials != null){
            $roof_material = PropertyRoofsMaterials::select('value')->find($request->roofs_materials);
        }
        // dd($roof_material);

        if (is_array(json_decode($request->property_value_added)) and count(json_decode($request->property_value_added)) > 0){
            $value_added_val = PropertyValueAdded::whereIn('id', array_values($valueAdded))->sum('value');
        }

       if (is_array($propertyTypess = json_decode($request->property_types, true)) && count($propertyTypess) > 0) {
            $property_type_val = PropertyType::whereIn('id', $propertyTypess)->sum('value');
        }


        //  return response()->json([
        //     'property_type_val' => $property_type_val  //0
        // ], 200);
        // dd($property_type_val);

        if (isset($request->length) and $request->length != null and (isset($request->breadth) and $request->breadth != null) ) {

            if ($request->has('property_district')) {
                $district = District::where('name', $request->property_district)->first();
                if ($district->sq_meter_value) {
                    $rate_square_meter = $district->sq_meter_value;
                    // dd(1,$rate_square_meter);
                }
            }

            $property_dimension = ($request->length * $request->breadth) * $rate_square_meter;
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

            //$property_dimension = ($request->length * $request->breadth) * $rate_square_meter;
            $property_dimension = ($request->assessment_area) * $rate_square_meter;
            //$property_dimension = $request->property_dimension * getSystemConfig(SystemConfig::CURRENT_RATE);
            //$property_dimension = PropertyDimension::select('value')->find($request->property_dimension);
        }


        if (isset($request->property_use) and $request->property_use != null){
            $property_use = PropertyUse::select('value')->find($request->property_use);
        }

        if (isset($request->zone) and $request->zone != null){
            $zones = PropertyZones::select('value')->find($request->zone);
        }
        // dd($property_use,$zones);
        //   return response()->json([
        //     'valueadded' => $value_added_val  //0
        // ], 200);

        /*number of Shop available*/

        if ($shopValue > 0){
            $value_added_val = $value_added_val + ($shopValue * $no_of_shops);
        }
        // dd($value_added_val,$shopValue , $no_of_shops);

        /*number of mast available*/
        if ($mastValue > 0){
            $value_added_val = $value_added_val + ($mastValue * $no_of_mast);
        }
        
        // return response()->json([
        //     'valueadded' => $value_added_val
        // ], 200);


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
        $step0 = round($property_dimension);
        $step6 = 0;
        // dd($step4);
        
        // return response()->json([
        //     'ss'=>'1',
        //     'data' => json_decode(@$request->council)
        // ], 200);

        // return response()->json([
        //     'step1' => $step1,
        //     'step2' => $step2,
        //     'step3' => $step3,
        //     'step0' => $step0,
        //     'step6' => $step6,
        // ], 200);




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
         if(@$request->council){
             foreach(json_decode(@$request->council) as $val ){
             
                $counsDetails=CounsilAdjustmentGroupA::where('id',$val)->first();
                if(@$counsDetails->amount || @$counsDetails->value){
                }else{
                if($counsDetails->sign=="+"){
                 $sumOfPercentage=$sumOfPercentage+(int)$counsDetails->percentage;
                }else{
                  $sumOfPercentage=$sumOfPercentage-(int)$counsDetails->percentage;
                }
               }// end if for amont
            } // end foreach
         }
            
            $result['percent_of_adjustments'] =$sumOfPercentage;
            // //its minus or plus check that

        //If value added exist NEW CALCULATION
        
        if(@$request->council){
          if(count(json_decode(@$request->council))>0){
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


















function encodePlusCode($latitude, $longitude, $codeLength = 10)
{
    $codeAlphabet = '23456789CFGHJMPQRVWX';
    $encodingBase = strlen($codeAlphabet);

    $lat = ($latitude + 90.0) / 180.0;
    $lng = ($longitude + 180.0) / 360.0;

    $lat *= pow($encodingBase, $codeLength / 2);
    $lng *= pow($encodingBase, $codeLength / 2);

    $lat = floor($lat);
    $lng = floor($lng);

    $code = '';
    for ($i = 0; $i < $codeLength / 2; ++$i) {
        $latDigit = $lat % $encodingBase;
        $lngDigit = $lng % $encodingBase;
        $code = $codeAlphabet[$lngDigit] . $codeAlphabet[$latDigit] . $code;
        $lat = floor($lat / $encodingBase);
        $lng = floor($lng / $encodingBase);
    }

    $code = substr($code, 0, 8) . '+' . substr($code, 8); // Add '+' separator

    return $code;
}
















// updateGeoLocation
  public function updateGeoLocation(Request $request)
    {
        // Validate the required fields
        $validator = Validator::make($request->all(), [
            'property_geo_registry_id' => 'required|exists:property_geo_registry,id',
            'property_id' => 'required|exists:properties,id',
            'digital_address' => 'required',
            'dor_lat_long' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find the geo registry record
        $geoRegistry = PropertyGeoRegistry::findOrFail($request->input('property_geo_registry_id'));
        $geoRegistryData = PropertyGeoRegistry::findOrFail($request->input('property_geo_registry_id'));

       

         if ($request->dor_lat_long && count(explode(',', $request->dor_lat_long)) === 2) {
                
                 list($lat, $lng) = explode(',', $request->dor_lat_long);
                 $openlocationCode=$this->encodePlusCode($lat, $lng);
                 // return response()->json([
                 //    'success' => false,
                 //    'lat' => $lat,
                 //    'lng' => $lng,
                 //    'openlocationCode'=>$openlocationCode
                 // ], 422);

                $geoExist = PropertyGeoRegistry::where('id', '!=', $geoRegistry->id)
                    ->where("open_location_code", $openlocationCode)
                    ->first();

                if ($geoExist) {
                    // $validator->errors()->add('dor_lat_long', 'This dor lat lng already exists');
                    return response()->json([
                            'status' => false,
                            'errors' =>"This dor lat lng already exists",
                        ], 422);
                    }
            }


        // Update geo registry data
        $geoRegistry->point1=$request->point1;
        $geoRegistry->point2=$request->point2;
        $geoRegistry->point3=$request->point3;
        $geoRegistry->point4=$request->point4;
        $geoRegistry->point5=$request->point5;
        $geoRegistry->point6=$request->point6;
        $geoRegistry->point7=$request->point7;
        $geoRegistry->point8=$request->point8;
        $geoRegistry->digital_address=$request->digital_address;
        $geoRegistry->dor_lat_long=$request->dor_lat_long;
        $geoRegistry->old_digital_address=$geoRegistryData->digital_address;

        // Generate open location code
        if ($request->dor_lat_long && count(explode(',', $request->dor_lat_long)) === 2) {
            list($lat, $lng) = explode(',', $request->dor_lat_long);
            $geoRegistry->open_location_code = $this->encodePlusCode($lat, $lng);
        }

        $geoRegistry->save();

        // Handle meter data
        $property = Property::findOrFail($request->property_id);

    if ($request->has('meterData') && is_array($request->meterData)) {
            // Get all existing meter IDs for the property
            $all = RegistryMeter::where('property_id', $request->property_id)->pluck('id')->toArray();
            $comingIds = [];

            foreach ($request->meterData as $meter) {
                $imagePath = null;

                // Handle image upload if present
                if (isset($meter['imageFile']) && $meter['imageFile'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $meter['imageFile'];
                    $filePath = 'property/meter/image';
                    $imagePath = $this->uploadFile($file, $filePath);
                }

                if (isset($meter['id']) && $meter['id'] !== null) {
                    // Update existing meter
                    $comingIds[] = $meter['id']; 

                    $updateData = ['number' => $meter['number']];
                    if ($imagePath) {
                        $updateData['image'] = $imagePath;
                    }

                    RegistryMeter::where('id', $meter['id'])->update($updateData);
                } else {
                    // Create new meter
                    if (!empty($meter['number'])) {
                        $newMeter = new RegistryMeter;
                        $newMeter->property_id = $request->property_id;
                        $newMeter->number = $meter['number'];
                        if ($imagePath) {
                            $newMeter->image = $imagePath;
                        }
                        $newMeter->save();
                    }
                }
            }

            // Delete meters not coming from frontend
            $toDelete = array_diff($all, $comingIds);
            RegistryMeter::whereIn('id', $toDelete)->delete();
    }



        return response()->json([
            'status' => true,
            'message' => 'Geo registry and meter data updated successfully',
            'data' => $geoRegistry
        ]);
    }



















public function indexNew(Request $request): JsonResponse
{
    // try {
        $user = Auth::guard('sanctum')->user();
        $data=[];
      
        // $query = Property::with([
        //         'user:id,first_name,last_name',
        //         'landlordFew:id,property_id,first_name,middle_name,surname',
        //         'userDetails',
        //         'payments',
        //         'assessment:id,property_id,property_rate_without_gst,demand_note_recipient_photo',
        //     ])->where('chiefdom', '!=', 'Kaffu Bullom')->orderBy('id','desc');

        $query = Property::with([
                'user:id,first_name,last_name',
                'landlordFew:id,property_id,first_name,middle_name,surname',
                'userDetails',
                'payments',
                'assessment:id,property_id,property_rate_without_gst,demand_note_recipient_photo',
                ])
                ->where(function ($q) {
                    $q->where('chiefdom', '!=', 'Kaffu Bullom')
                      ->orWhereNull('chiefdom')
                      ->orWhere('chiefdom', '');
                })
                ->orderBy('id', 'desc');


      
        // Filter by Demand Draft Year (Key: demand_draft_year)  Done 27
        if ($request->filled('demand_draft_year')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->whereYear('created_at', $request->demand_draft_year);
            });
        }

        // Filter by Printed Status (Key: is_printed)  done 46
        if ($request->filled('is_printed')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->when($request->input('is_printed') == '1', fn($q) => $q->whereNotNull('last_printed_at'));
                $q->when($request->input('is_printed') == '0', fn($q) => $q->whereNull('last_printed_at'));
            });
        }

        // Filter by Gated Community Status (Key: gated_community)  done 47, 48
        // if ($request->filled('gated_community')) {
        //     $query->whereHas('assessment', function ($q) use ($request) {
        //         $q->where('gated_community', $request->gated_community);
        //     });
        // }

        // Filter by District ID (Fixed to 13) (Key: fixed_district_13)
        // $query->whereHas('districts', function ($q) {
        //     $q->where('id', 13);
        // });

        // Filter by Property Creation Date  //done 36, 37
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('properties.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->start_date || $request->end_date) {
            $query->when($request->start_date, fn($q) => $q->whereBetween('properties.created_at', [Carbon::parse($request->start_date), Carbon::now()]));
            $query->when($request->end_date, fn($q) => $q->whereBetween('properties.created_at', [Carbon::now()->subYear(5), Carbon::parse($request->end_date)->endOfDay()]));
        }

        // Filter by Unpaid Properties Done 38,39
       if ($request->unpaid_start_date && $request->unpaid_end_date) {
            $year = date('Y', strtotime($request->unpaid_start_date));
            $query->whereYear('created_at', $year)->doesntHave('payments');
        }


        // Filter by Payment Status (Key: payment_status) // done 42
        if ($payment_status = $request->input('paid')) {
            $query->{$payment_status == 'paid' ? 'whereHas' : 'doesntHave'}('payments');
        }

        // Filter by Paid Date Range (Key: paid_date_range) //done 40, 41
        if ($request->paid_start_date && $request->paid_end_date) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->whereBetween('property_payments.created_at', [
                    Carbon::parse($request->paid_start_date)->startOfDay(),
                    Carbon::parse($request->paid_end_date)->endOfDay()
                ]);
            });
        }

        // Filter by Occupancy Type (Key: occupancy_type)  Done 17
        // if ($request->filled('occupancy_type')) {
        //     $query->whereHas('occupancies', fn($q) => $q->where('type', $request->occupancy_type));
        // }

        // Filter by Council Adjustment (Key: counsil_adjustmnt) //done // 2
        if ($request->filled('counsil_adjustmnt')) {

    if ($request->counsil_adjustmnt === 'yes') {
        $query->whereIn('properties.id', function ($q) {
            $q->select('property_id')
              ->from('property_to_counsil_adjustment_group_a');
        });
    } else {
        $query->whereNotIn('properties.id', function ($q) {
            $q->select('property_id')
              ->from('property_to_counsil_adjustment_group_a');
        });
    }
}



        // Filter by Property ID (Key: property_id) //done // 1
        if ($request->filled('property_id')) {
            $property_ids = explode(",", $request->property_id);
            $query->whereIn('properties.id', $property_ids);
        }




        
        // Apply filters based on request parameters
        // done 14
        if ($request->filled('town')) {
            $query->where('properties.section', $request->town);
        }
         
         //done 10
        if ($request->filled('street_name')) {
            $query->where('properties.street_name', 'like', "%{$request->street_name}%");
        }
         
         // done 11
        if ($request->filled('street_number')) {
            $query->where('properties.street_number', $request->street_number);
        }
        
        // done 12
        if ($request->filled('postcode')) {
            $query->where('properties.postcode', $request->postcode);
        }
         
         // done 13
        if ($request->filled('ward')) {
            $query->where('properties.ward', $request->ward);
        }

        if ($request->filled('district')) {
            $query->where('properties.district', $request->district);
        }

        if ($request->filled('province')) {
            $query->where('properties.province', $request->province);
        }
         

         //done 18
        if ($request->filled('chiefdom')) {
            $query->where('properties.chiefdom', $request->chiefdom);
        }
        
        //done 19
        if ($request->filled('constituency')) {
            $query->where('properties.constituency', $request->constituency);
        }

        // Property Accessibility Filter  // done 15
        if ($request->is_accessible == "0") {
            $query->where('is_property_inaccessible', 0);
        }

        if ($request->is_accessible == "1") {
            $query->where('is_property_inaccessible', 1);
        }

         if ($request->propertyCategoryType && $request->propertyCategoryType!="all" ) {
            $query->where('category', $request->propertyCategoryType);
        }

        // Demand Draft Delivery Status Filter //done 43
        if ($request->is_draft_delivered == "0") {
            $query->whereHas('assessment', function ($q) {
                $q->whereYear('created_at', now()->format('Y'))
                  ->whereNull('demand_note_delivered_at');
            });
        }

        if ($request->is_draft_delivered == "1") { //done 43 , 44, 45
            $query->whereHas('assessment', function ($q) use ($request) {
                $year = now()->format('Y');

                if ($request->dd_start_date && $request->dd_end_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          Carbon::parse($request->dd_start_date),
                          Carbon::parse($request->dd_end_date)
                      ]);
                } elseif ($request->dd_start_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          Carbon::parse($request->dd_start_date),
                          now()
                      ]);
                } elseif ($request->dd_end_date) {
                    $q->whereYear('created_at', $year)
                      ->whereBetween('demand_note_delivered_at', [
                          now()->subYear(5),
                          Carbon::parse($request->dd_end_date)
                      ]);
                } else {
                    $q->whereYear('created_at', $year)
                      ->whereNotNull('demand_note_delivered_at');
                }
            });
        }



        // Apply filters based on request parameters
        if ($request->filled('town')) {
            $query->where('properties.section', $request->town);
        }
         
         // done 10
        if ($request->filled('street_name')) {
            $query->where('properties.street_name', 'like', "%{$request->street_name}%");
        }

        if ($request->filled('street_number')) {
            $query->where('properties.street_number', $request->street_number);
        }

        if ($request->filled('postcode')) {
            $query->where('properties.postcode', $request->postcode);
        }

        if ($request->filled('ward')) {
            $query->where('properties.ward', $request->ward);
        }
        
        // done 20
        if ($request->filled('district')) {
            $query->where('properties.district', $request->district);
        }
        
        // done 21
        if ($request->filled('province')) {
            $query->where('properties.province', $request->province);
        }

        if ($request->filled('chiefdom')) {
            $query->where('properties.chiefdom', $request->chiefdom);
        }

        if ($request->filled('constituency')) {
            $query->where('properties.constituency', $request->constituency);
        }

        // Digital Address Filters
        if ($request->filled('digital_address')) {
            $query->where('properties.id', $request->digital_address);
        }

        if ($request->filled('old_digital_address')) {
            $query->where('properties.id', $request->old_digital_address);
        }

        // Property Completion Status Done 26
        if ($request->filled('is_completed')) {
            $query->where('properties.is_completed', $request->is_completed == 'yes');
        }

        // Property Type  Done 22
        if ($request->filled('type')) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('id', $request->type);
            });
        }

        // Wall Material // done 23
        if ($request->filled('wall_material')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('property_wall_materials', $request->wall_material);
            });
        }

        // Compound Name  //Done 28
        if ($request->filled('compound_name')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('compound_name', 'like', "%{$request->compound_name}%");
            });
        }

        // Property Price Range // Done 6 and 7
        if ($request->filled('form_price') && $request->filled('to_price')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->whereBetween('property_rate_without_gst', [$request->form_price, $request->to_price])
                  ;
            });
        }

        // Payee Name //done 8
        if ($request->filled('payee_name')) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('payee_name', 'like', "%{$request->payee_name}%");
            });
        }

        // Payment Method //Done 9
        if ($request->filled('payment_method')) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('payment_type', 'like', "%{$request->payment_method}%");
            });
        }

        // Roof Material //done 24
        if ($request->filled('roof_material')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('roofs_materials', $request->roof_material);
            });
        }

        // Property Dimension
        if ($request->filled('property_dimension')) {
            $query->whereHas('assessment', function ($q) use ($request) {
                $q->where('property_dimension', $request->property_dimension);
            });
        }

        // Value Added  done 25
        if ($request->filled('value_added')) {
            $query->whereHas('valueAdded', function ($q) use ($request) {
                $q->where('id', $request->value_added);
            });
        }

        // Property Inaccessible
        // done 16
        // if ($request->filled('property_inaccessible')) {
        //     $query->whereHas('propertyInaccessible', function ($q) use ($request) {
        //         $q->where('id', $request->property_inaccessible);
        //     });
        // }

        // Landlord Filters //done 29 , 30
        $query->whereHas('landlord', function ($q) use ($request) {
            if ($request->filled('owner_first_name')) {
                $q->where('first_name', 'like', "%{$request->owner_first_name}%");
            }

            if ($request->filled('owner_last_name')) {
                $q->where('surname', 'like', "%{$request->owner_last_name}%");
            }

            if ($request->filled('mobile')) {
                $q->where('mobile_1', $request->mobile);
            }
        });

        // // // Occupancy Filters  done 31, 32,33  error 1633
        // $query->whereHas('occupancy', function ($q) use ($request) {
        //     if ($request->filled('tenant_first_name')) {
        //         $q->where('tenant_first_name', 'like', "%{$request->tenant_first_name}%");
        //     }

        //     if ($request->filled('tenant_middle_name')) {
        //         $q->where('middle_name', 'like', "%{$request->tenant_middle_name}%");
        //     }

        //     if ($request->filled('tenant_last_name')) {
        //         $q->where('surname', 'like', "%{$request->tenant_last_name}%");
        //     }
        // });

        // Landlord Telephone Number  done 34
        if ($request->filled('telephone_number')) {
            $query->whereHas('landlord', function ($q) use ($request) {
                $q->where('mobile_1', 'like', "%{$request->telephone_number}%")
                ->orWhere('mobile_2', 'like', "%{$request->telephone_number}%");;
            });
        }

        // if ($request->filled('telephone_number')) {
        //     $query->whereHas('landlord', function ($q) use ($request) {
        //         $q->where(function ($sub) use ($request) {
        //             $sub->where('mobile_1', 'like', "%{$request->telephone_number}%")
        //                 ->orWhere('mobile_2', 'like', "%{$request->telephone_number}%");
        //         });
        //     });
        // }


        // Open Location Code  //done // 3
        if ($request->filled('open_location_code')) {
            $query->whereHas('geoRegistry', function ($q) use ($request) {
                $q->where('open_location_code', $request->open_location_code);
            });
        }




        // Filter by user name //done 35
        if ($request->filled('name')) {
            $query->whereHas('userDetails', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        // Organization Type Filter  Done // 4 AND 5
        if ($request->input('is_organization') == 1 && $request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type)
                  ->where('is_organization', true);
        }

        // Non-organization Filter
        if ($request->input('is_organization') == '0') {
            $query->where('is_organization', false);
        }
       



            if ($request->download_pdf_in_bulk == 1) {
                $bulkDemand = new PropertyInBulk();
                return $bulkDemand->handle(Property::all(), $request->demand_draft_year);
            }

            if ($request->download_stickers == 1) {
                $stickers = new PropertyStickers();

                $nProperty = Property::withAssessmentCalculation($request->input('demand_draft_year'))
                    ->having('current_year_payment', '>', 0)
                    ->having('total_payable_due', 0)
                    ->orderBy('total_payable_due')
                    ->get();

                return $stickers->handle($nProperty, $request);
            }

          


        // Paginate results
        $properties = $query->paginate(50);

        return response()->json([
            'success' => true,
            'property' => $properties,
            'data'=>$data,
        ]);
    // } catch (\Throwable $e) {
    //     return response()->json([
    //         'success' => false,
    //         'error' => $e->getMessage()
    //     ], 500);
    // }
}










public function namefetch(Request $request){
     $assessmentUser = UserMain::select('name')->where('name', 'like', '' . strtolower($request->mask) . '%')->get();

        return response()->json([
            'success' => true,
            'assessmentUser' => $assessmentUser
        ], 200);


}











}
