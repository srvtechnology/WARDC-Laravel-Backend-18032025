<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use App\Models\Property;
use App\Models\District;

class PdfGeneratorController extends Controller
{



public function pdfGenerator(Request $request)
{
    try {
        $ids = $request->input('ids');
        $year = $request->input('year');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID array'], 422);
        }

        if (!$year) {
            $year = 2019;
        }

        // Filter properties with assessments created in the given year
        $properties = Property::whereIn('id', $ids)
            ->whereHas('assessment', function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
            ->with([
                'assessment' => function ($query) use ($year) {
                    $query->whereYear('created_at', $year)
                        ->with('categories', 'types', 'valuesAdded', 'dimension', 'wallMaterial', 'roofMaterial', 'payments');
                }
            ])
            ->latest()
            ->get();

        if ($properties->isEmpty()) {
            return response()->json(['error' => 'No properties Assessment found for year ' . $year], 404);
        }

        $html = view('admin.payments.bulk-receipt', [
            'properties' => $properties,
            'year' => $year,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4')->setOptions(['isRemoteEnabled' => true]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bulk-receipt-' . now()->format('Ymd-His') . '.pdf"',
        ]);

    } catch (\Exception $e) {
        \Log::error('PDF generation failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'PDF generation failed',
            'message' => $e->getMessage()
        ], 500);
    }
}







public function pdfEnvelope(Request $request)
{
    try {
        $ids = $request->input('ids');
        $year = $request->input('year');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID array'], 422);
        }

        if (!$year) {
            $year = date('Y');
        }

        // Filter properties with assessments in the given year
        $properties = Property::whereIn('id', $ids)
            ->whereHas('assessment', function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
            ->with([
                'assessment' => function ($query) use ($year) {
                    $query->whereYear('created_at', $year)
                        ->with('categories', 'types', 'valuesAdded', 'dimension', 'wallMaterial', 'roofMaterial', 'zone', 'swimming');
                }
            ])
            ->latest()
            ->get();

        if ($properties->isEmpty()) {
            return response()->json(['error' => 'No properties assessment found for year ' . $year], 404);
        }

        $html = view('admin.envelope.bulk-envelope', [
            'properties' => $properties,
            'year' => $year,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4')
            ->setOptions(['isRemoteEnabled' => true]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bulk-envelope-' . now()->format('Ymd-His') . '.pdf"',
        ]);

    } catch (\Exception $e) {
        \Log::error('Envelope PDF generation failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'Envelope PDF generation failed',
            'message' => $e->getMessage()
        ], 500);
    }
}







public function paymentExal(Request $request)
{
    try {
        $ids = $request->input('ids');
        $year = $request->input('year') ?? date('Y');

        $allProperty = Property::whereIn('id', $ids)
            ->whereHas('assessment', function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
            ->with('assessment', 'landlord')
            ->orderBy('id', 'desc')
            ->get();

        if ($allProperty->isEmpty()) {
            return response()->json(['error' => 'No data found for export'], 404);
        }

        $data = '<table>
            <tr>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Property Id</th>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Ward</th>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Landlord Name</th>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Mobile</th>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Assessment Amount</th>
                <th style="border:1px solid white;background-color:#cc00cc;color:white;">Assessment Year</th>
            </tr>';

        foreach ($allProperty as $value) {
            $landlord = $value->landlord;
            $landlordName = trim(($landlord->first_name ?? '') . ' ' . ($landlord->middle_name ?? '') . ' ' . ($landlord->surname ?? ''));

            $data .= '
                <tr>
                    <td style="border:1px solid black;">' . $value->id . '</td>
                    <td style="border:1px solid black;">' . $value->ward . '</td>
                    <td style="border:1px solid black;">' . $landlordName . '</td>
                    <td style="border:1px solid black;">' . ($landlord->mobile_1 ?? '') . '</td>
                    <td style="border:1px solid black;">' . ($value->assessment->property_rate_without_gst ?? '') . '</td>
                    <td style="border:1px solid black;">' . $year . '</td>
                </tr>';
        }

        $data .= '</table>';

        // Create a response object
        $response = response($data, 200);
        $response->header('Content-Type', 'application/vnd.ms-excel');
        $response->header('Content-Disposition', 'attachment; filename=payment-details-' . now()->format('Ymd-His') . '.xls');
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Expose-Headers', 'Content-Disposition');
        $response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;

    } catch (\Exception $e) {
        \Log::error('Excel download failed: ' . $e->getMessage());

        return response()->json([
            'error' => 'Failed to generate Excel file',
            'message' => $e->getMessage(),
        ], 500)->header('Access-Control-Allow-Origin', '*');
    }
}








public function waybill(Request $request)
{
    try {
         $ids = $request->input('ids');
        $year = $request->input('year') ?? date('Y');

        $properties = Property::with([
            'landlord',
            'assessment',
            'assessments' => function ($query) {
                $query->with('types', 'valuesAdded', 'categories')->latest();
            },
            'payments',
            'geoRegistry',
        ])
        ->whereHas('assessment', function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
        ->whereIn('id', $ids)
        ->get();

        if ($properties->isEmpty()) {
            return response()->json(['error' => 'No data found for export'], 404);
        }

        $data = '<table>
                <tr>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">No</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Owner Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Middle Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Last Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">ID</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount Paid</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount Due</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">RECEIPIENT Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Contact</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Address</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Location</th>
                </tr>';

            $counter = 1;

            foreach ($properties as $property) {
                $landlord = $property->landlord;
                $assessment = $property->assessment;
                $geo = $property->geoRegistry;

                $first = $landlord->first_name ?? '';
                $middle = $landlord->middle_name ?? '';
                $last = $landlord->surname ?? '';
                $amount = $assessment->property_rate_without_gst ?? 0;

                // Payment info
                $yearr =  $year  ;//$request->input('demand_draft_year', date('Y'));
                $amountPaid = 0;
                $recipientName = 'N/A';
                $dateOfPayment = 'N/A';

                foreach ($property->payments as $payment) {
                    if ($payment->created_at->format('Y') == $yearr) {
                        $amountPaid += $payment->amount;
                        $recipientName = $payment->payee_name || 'N/A';
                        $dateOfPayment = $payment->created_at->format('Y-m-d') || 'N/A';
                    }
                     // return response()->json(['data' => $payment], 404);
                }

                // Dues
                $due = $assessment->due;
                // return response()->json(['amountPaid' => $amountPaid,$payment->created_at->format('Y') , $yearr], 404);
                

                $data .= '
                    <tr>
                        <td style="border:1px solid black;">' . $counter . '</td>
                        <td style="border:1px solid black;">' . $first . '</td>
                        <td style="border:1px solid black;">' . $middle . '</td>
                        <td style="border:1px solid black;">' . $last . '</td>
                        <td style="border:1px solid black;">' . $property->id . '</td>
                        <td style="border:1px solid black;">' . $amount . '</td>
                        <td style="border:1px solid black;">' . $amountPaid . '</td>
                        <td style="border:1px solid black;">' . $due . '</td>
                        <td style="border:1px solid black;">' . $recipientName . '</td>
                        <td style="border:1px solid black;">' . ($landlord->mobile_1 ?? '') . '</td>
                        <td style="border:1px solid black;">' . ($landlord->street_name ?? '') . '</td>
                        <td style="border:1px solid black;">' . ($geo->digital_address ?? '') . '</td>
                    </tr>';

                $counter++;
            }

            $data .= '</table>';


        // Return the response as an Excel file
        return response($data, 200)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename=waybill-details-' . now()->format('Ymd-His') . '.xls')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    } catch (\Exception $e) {
        \Log::error('Excel download failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to generate Excel file',
            'message' => $e->getMessage(),
        ], 500)->header('Access-Control-Allow-Origin', '*');
    }
}





public function propertySummery(Request $request)
{
    try {
         $ids = $request->input('ids');
        $year = $request->input('year') ?? date('Y');

        $properties = Property::with([
            'landlord',
            'assessment',
            'assessments' => function ($query) {
                $query->with('types', 'valuesAdded', 'categories')->latest();
            },
            'payments',
            'geoRegistry',
        ])
        ->whereHas('assessment', function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
        ->whereIn('id', $ids)
        ->get();

        if ($properties->isEmpty()) {
            return response()->json(['error' => 'No data found for export'], 404);
        }

        $data = '<table>
                <tr>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">No</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Owner Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Middle Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Last Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">ID</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount Paid</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Amount Due</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">RECEIPIENT Name</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Contact</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Address</th>
                    <th style="border:1px solid white;background-color:#cc00cc;color:white;">Location</th>
                </tr>';

            $counter = 1;

            foreach ($properties as $property) {
                $landlord = $property->landlord;
                $assessment = $property->assessment;
                $geo = $property->geoRegistry;

                $first = $landlord->first_name ?? '';
                $middle = $landlord->middle_name ?? '';
                $last = $landlord->surname ?? '';
                $amount = $assessment->property_rate_without_gst ?? 0;

                // Payment info
                $yearr =  $year  ;//$request->input('demand_draft_year', date('Y'));
                $amountPaid = 0;
                $recipientName = 'N/A';
                $dateOfPayment = 'N/A';

                foreach ($property->payments as $payment) {
                    if ($payment->created_at->format('Y') == $yearr) {
                        $amountPaid += $payment->amount;
                        $recipientName = $payment->payee_name || 'N/A';
                        $dateOfPayment = $payment->created_at->format('Y-m-d') || 'N/A';
                    }
                     // return response()->json(['data' => $payment], 404);
                }

                // Dues
                $due = $assessment->due;
                // return response()->json(['amountPaid' => $amountPaid,$payment->created_at->format('Y') , $yearr], 404);
                

                $data .= '
                    <tr>
                        <td style="border:1px solid black;">' . $counter . '</td>
                        <td style="border:1px solid black;">' . $first . '</td>
                        <td style="border:1px solid black;">' . $middle . '</td>
                        <td style="border:1px solid black;">' . $last . '</td>
                        <td style="border:1px solid black;">' . $property->id . '</td>
                        <td style="border:1px solid black;">' . $amount . '</td>
                        <td style="border:1px solid black;">' . $amountPaid . '</td>
                        <td style="border:1px solid black;">' . $due . '</td>
                        <td style="border:1px solid black;">' . $recipientName . '</td>
                        <td style="border:1px solid black;">' . ($landlord->mobile_1 ?? '') . '</td>
                        <td style="border:1px solid black;">' . ($landlord->street_name ?? '') . '</td>
                        <td style="border:1px solid black;">' . ($geo->digital_address ?? '') . '</td>
                    </tr>';

                $counter++;
            }

            $data .= '</table>';


        // Return the response as an Excel file
        return response($data, 200)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename=waybill-details-' . now()->format('Ymd-His') . '.xls')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    } catch (\Exception $e) {
        \Log::error('Excel download failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to generate Excel file',
            'message' => $e->getMessage(),
        ], 500)->header('Access-Control-Allow-Origin', '*');
    }
}







public function singleEnvelopeGenerator(Request $request){
  try {
        $id = $request->input('id');
        $year = $request->input('year');

        if (!$year) {
            $year = date('Y');
        }

        // Filter property with assessments in the given year
       $property = Property::where('id', $id)
                ->with([
                    'occupancy',
                    'types',
                    'geoRegistry',
                    'user',
                    'assessments' => function ($query) use ($year) {
                        $query->whereYear('created_at', $year);
                    }
                ])
                ->first();

        if (!$property) {
            return response()->json(['error' => 'No property found for year ' . $year], 404);
        }

        $assessment = $property->assessments()->whereYear('created_at', $year)->first();
        if (!$assessment) {
            return response()->json(['error' => 'No property assesment found for year ' . $year], 404);
        }

        $paymentInQuarter = $property->getPaymentsInQuarter($year);
        $district = District::where('name', $property->district)->first();

        $html = view('admin.envelope.single-envelope', [
            'property' => $property,
            'year' => $year,
            'paymentInQuarter'=>$paymentInQuarter,
            'assessment'=>$assessment,
            'district'=>$district,

        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4')
            ->setOptions(['isRemoteEnabled' => true]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bulk-envelope-' . now()->format('Ymd-His') . '.pdf"',
        ]);

    } catch (\Exception $e) {
        \Log::error('Envelope PDF generation failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'Envelope PDF generation failed',
            'message' => $e->getMessage()
        ], 500);
    }
}





}
