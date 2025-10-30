<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\PropertyAssessmentDetail;
use App\Models\PropertyPayment;
use Carbon\Carbon;
use Log;

class UpdatePropertyAssessment extends Command
{
    // Command signature (name you will call later)
    // php artisan property:assessment-update

    protected $signature = 'property:assessment-update';

    protected $description = 'Update property assessment calculations in chunks';

    public function handle()
    {
        ini_set('max_execution_time', 0); // no timeout

        Property::where('id', '>', 115400)->chunk(50, function ($properties) {
            foreach ($properties as $property) {
                $propertyId = $property->id;

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
                        $penalty = $arrears > 0 ? round($arrears * 0.25, 2) : 0; // 0.25% = 0.25

                        $due     = round($rate + $arrears + $penalty - $amountPaid, 2);
                    }

                    $row->arrear_calc = $index === 0 ? 0 : $arrears;
                    $row->penalty     = $index === 0 ? 0 : $penalty;
                    $row->due         = $due;
                    $row->save();

                    $previousDue = $due;
                }
                Log::info("Processed property {$propertyId}");
            }
        });

        $this->info(' All properties updated successfully in chunks of 50.');
    }
}
