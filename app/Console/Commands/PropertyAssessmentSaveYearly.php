<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\PropertyAssessmentDetail;
use Carbon\Carbon;
use Log;

class PropertyAssessmentSaveYearly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run using: php artisan property:assessment-save-yearly
     */

    // php artisan property:assessment-save-yearly


    protected $signature = 'property:assessment-save-yearly';

    /**
     * The console command description.
     */
    protected $description = 'Clone last year’s property assessments into current year with arrears and penalties.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentYear = Carbon::now()->year;
        $this->info("Starting yearly property assessment cloning for {$currentYear}...");

        Property::chunk(50, function ($properties) use ($currentYear) {
            foreach ($properties as $property) {
                $propertyId = $property->id;

                // Check if data already exists for this year
                $exists = PropertyAssessmentDetail::where('property_id', $propertyId)
                    ->whereYear('created_at', $currentYear)
                    ->exists();

                if ($exists) {
                    $this->line("Skipping property {$propertyId} (already exists).");
                    continue;
                }

                // Fetch the latest record
                $find = PropertyAssessmentDetail::where('property_id', $propertyId)
                    ->latest('created_at')
                    ->first();

                if (!$find) {
                    $this->line("No previous data for property {$propertyId}, skipping...");
                    continue;
                }

                // Clone record
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
                $ins->due = round(max(0, $lastYearDue +(int)$find->property_rate_without_gst+ round($lastYearDue * 0.25, 2)  - 0), 2); // as amount paid in 1 day will be 0
                $ins->text_val = $find->text_val;

                $ins->save();

                $this->info(" Cloned yearly assessment for property {$propertyId}");
            }
        });

        $this->info("Bulk yearly assessment save completed.");
    }
}
