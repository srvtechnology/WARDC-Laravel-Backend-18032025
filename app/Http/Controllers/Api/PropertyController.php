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
use App\Models\OccupancyDetail;
use App\Models\Property_occupancies;

ini_set('memory_limit','512M');

class PropertyController extends Controller
{
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
            'user:id,first_name,last_name',
            'landlordFew',
            'geoRegistry',
            'occupancies',
            'propertyInaccessible',
            'payments',
            'districts',
            'images',
            'assessment',
        ]);




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
        if ($request->filled('occupancy_type')) {
            $query->whereHas('occupancies', fn($q) => $q->where('type', $request->occupancy_type));
        }

        // Filter by Council Adjustment (Key: council_adjustment) //done // 2
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

        // // Occupancy Filters  done 31, 32,33
        $query->whereHas('occupancy', function ($q) use ($request) {
            if ($request->filled('tenant_first_name')) {
                $q->where('tenant_first_name', 'like', "%{$request->tenant_first_name}%");
            }

            if ($request->filled('tenant_middle_name')) {
                $q->where('middle_name', 'like', "%{$request->tenant_middle_name}%");
            }

            if ($request->filled('tenant_last_name')) {
                $q->where('surname', 'like', "%{$request->tenant_last_name}%");
            }
        });

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
                $data['chiefdom'] = BoundaryDelimitation::distinct()->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort()->prepend('Select Chiefdom', '');
                $data['constituency'] = BoundaryDelimitation::distinct()->orderBy('constituency')->pluck('constituency', 'constituency')->sort()->prepend('Select Constituency', '');
            } elseif ($user->super_admin_cus != 1) {
                $data['district'] = BoundaryDelimitation::distinct()->orderBy('district')->pluck('district', 'district')->sort()->prepend('Select District', '');
                $data['province'] = BoundaryDelimitation::distinct()->orderBy('province')->pluck('province', 'province')->sort()->prepend('Select Province', '');
                $data['ward'] = BoundaryDelimitation::distinct()->orderBy('ward')->pluck('ward', 'ward')->sort()->prepend('Select All Ward', '');
                $data['chiefdom'] = BoundaryDelimitation::distinct()->orderBy('chiefdom')->pluck('chiefdom', 'chiefdom')->sort()->prepend('Select Chiefdom', '');
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

                $data['chiefdom'] = BoundaryDelimitation::where('district', $user->assign_district)
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
        $properties = $query->paginate(10);

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


       $allAssesments = PropertyAssessmentDetail::where('property_id', $request->property_id)
            ->select('id','created_at','arrear_calc', 'penalty as penalty_amount','due','property_rate_without_gst','property_rate_with_gst','demand_note_recipient_photo')
            ->get();


        foreach ($allAssesments as $key => $val) {
            $year = \Carbon\Carbon::parse($val->created_at)->year;

            $payment = PropertyPayment::where('property_id', $request->property_id)
                ->whereYear('created_at', $year)
                ->value('total'); 

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
        'postcode' => 'required|string|max:255',
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
            'postcode' => $request->postcode,
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
        'postcode' => 'required|string|max:255',
        
        'is_draft_delivered' => 'required',
        'delivered_name' => 'required',
        'delivered_number' => 'required',
        'delivered_image' => 'required',
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
             $property->street_number = $request->street_number;
              $property->street_name = $request->street_name;
            $property->ward = $request->ward;
            $property->constituency = $request->constituency;
            $property->section = $request->section;
            $property->chiefdom = $request->chiefdom;

            $property->district = $request->district;
            $property->province = $request->province;
            $property->postcode = $request->postcode;
            $property->chiefdom = $request->chiefdom;

            $property->is_property_inaccessible = ($propertyInaccessible && count($propertyInaccessible)) ? true : false;
            $property->is_draft_delivered = $request->is_draft_delivered;
            $property->delivered_name = $request->delivered_name;
            $property->delivered_number = $request->delivered_number;

             if ($request->hasFile('delivered_image')) {
                $file = $request->file('delivered_image');

                // Define a unique name with directory structure
                $filePath = 'property/delivered/image';
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension(); // e.g., 7Y83SbHt7r.jpg

                // Store the file under storage/app/public/property/delivered/image
                $path = $file->storeAs($filePath, $fileName, 'public');

                // Optionally: save the path to DB
                $property->delivered_image = $path;
            
            }


            $property->save();

             // $property->propertyInaccessible()->sync($propertyInaccessible);
             //store to Property_property_inaccessibles model first delete and then insert

            $dltall=Property_property_inaccessibles::where('property_id',$request->property_id)->delete();

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
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
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





}
