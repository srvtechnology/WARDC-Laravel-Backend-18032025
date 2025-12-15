<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyPayment;
use App\Models\UserMain;
use App\Models\AdminUser;
use App\Models\PropertyAssessmentDetail;
use Carbon\Carbon;
use DB;
class ChartContoller extends Controller
{
    public function propertyAnalytics(Request $request)
    {
        $type = $request->type; 
        // return $type;
        if ($type === 'monthly') {
            $currentYear = Carbon::now()->year;
            $monthly = Property::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', $currentYear)
                ->groupBy('month')
                ->pluck('count', 'month');

            $data = [];

            for ($i = 1; $i <= 12; $i++) {
                $data[] = [
                    'month' => Carbon::create()->month($i)->format('F'),
                    'count' => $monthly[$i] ?? 0
                ];
            }

            return response()->json($data);
        } elseif ($type === 'yearly') {
            $minYear = Property::min(DB::raw('YEAR(created_at)'));
        $maxYear = Property::max(DB::raw('YEAR(created_at)'));

        $rawData = Property::selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('year')
            ->pluck('count', 'year');

        $filledData = [];
        for ($year = $minYear; $year <= $maxYear; $year++) {
            $filledData[] = [
                'year' => $year,
                'count' => $rawData[$year] ?? 0,
            ];
        }

        return response()->json($filledData);
        }

        return response()->json(['error' => 'Invalid type'], 400);
    }

    public function propertyPaymentCollection(Request $request)
    {
                $type = $request->type;

    if ($type === 'monthly') {
        $data = PropertyPayment::selectRaw('MONTH(created_at) as month, SUM(total) as total_amount')
            ->whereYear('created_at', now()->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        // Fill missing months with 0
        $monthlyData = collect(range(1, 12))->map(function ($month) use ($data) {
            $record = $data->firstWhere('month', $month);
            return [
                'month' => \Carbon\Carbon::create()->month($month)->format('F'),
                'total_amount' => $record ? (float) $record->total_amount : 0,
            ];
        });

        return response()->json($monthlyData);
    }

    if ($type === 'yearly') {
        $data = PropertyPayment::selectRaw('YEAR(created_at) as year, SUM(total) as total_amount')
    ->groupBy(DB::raw('YEAR(created_at)'))
    ->orderBy('year')
    ->get()
    ->map(function ($row) {
        return [
            'year' => $row->year,
            'total_amount' => round($row->total_amount, 2),
        ];
    });

        

        return response()->json($data);
    }

    return response()->json(['error' => 'Invalid type'], 400);
    }


    public function dashboardCounters(Request $request)
    {
        $currentYear = Carbon::now()->year;

        $propertyCount = Property::count();
        $userMainCount = UserMain::count();
        $adminUserCount = AdminUser::where('id', '!=', 1)->count();

        $propertyPaymentSum = PropertyPayment::whereYear('created_at', $currentYear)->sum('total');

        return response()->json([
            'property_count' => $propertyCount,
            'usermain_count' => $userMainCount,
            'adminuser_count' => $adminUserCount,
            'propertypayment_total' => $propertyPaymentSum,
        ]);
    }



    public function getFilters()
    {
        $users = UserMain::select('id','name')->get();
        $wards = range(1, 446);

        return response()->json([
        'wards' => $wards,
        'users' => $users,
        ]);
    }

public function getFilteredPayments(Request $request)
{
    $wardId = $request->ward_id;
    $userId = $request->user_id;
    $type = $request->type ?? 'monthly'; // 'monthly' or 'yearly'
    $year = Carbon::now()->year;

    $query = PropertyPayment::query();

    // Filter by Ward (through Property -> User)
    if ($wardId) {
        $query->whereHas('property', function ($q) use ($wardId) {
            $q->where('ward', $wardId);
        });
    }

    // Filter by User
    if ($userId) {
        $query->whereHas('property.user', function ($q) use ($userId) {
            $q->where('id', $userId);
        });
    }

    if ($type === 'monthly') {
        $rawResults = $query->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month'); // [1 => 123.45, 2 => 0, ...]

        // Build all months from Jan to current month
        $currentMonth = Carbon::now()->month;
        $results = [];

        for ($i = 1; $i <= $currentMonth; $i++) {
            $results[] = [
                'month' => Carbon::create()->month($i)->format('F'),
                'total' => round($rawResults[$i] ?? 0, 2),
            ];
        }

    } else {
        $results = $query->selectRaw('YEAR(created_at) as year, SUM(total) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($row) {
                return [
                    'year' => $row->year,
                    'total' => round($row->total, 2),
                ];
            });
    }

    return response()->json([
        'status' => true,
        'data' => $results,
    ]);
}


    public function yearlyAssessmentDueCollection()
    {
        // Get the oldest year based on created_at
        $startYear = PropertyAssessmentDetail::min(DB::raw('YEAR(created_at)')) ?? date('Y');
        $endYear = now()->year;

        $results = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $totalDue = PropertyAssessmentDetail::whereYear('created_at', $year)->sum('due');

            $results[] = [
                'year' => $year,
                'total_due' => round($totalDue,2),
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }   


    public function getFilteredAssessmentDue(Request $request)
    {
        $wardId = $request->ward_id;
        $userId = $request->user_id;

        $query = PropertyAssessmentDetail::query();

        // Filter by Ward (through Property -> User)
        if ($wardId) {
            $query->whereHas('property', function ($q) use ($wardId) {
                $q->where('ward', $wardId);
            });
        }

        // Filter by User
        if ($userId) {
            $query->whereHas('property.user', function ($q) use ($userId) {
                $q->where('id', $userId);
            });
        }

        // Group by year of created_at and sum due amount
        $results = $query->selectRaw('YEAR(created_at) as year, SUM(due) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($row) {
                return [
                    'year' => $row->year,
                    'total' => round($row->total, 2),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $results,
        ]);
    }



public function getFilteredArrears(Request $request)
{
    $wardId = $request->ward_id;
    $userId = $request->user_id;
    $type   = $request->type ?? 'yearly';

    $query = PropertyAssessmentDetail::query();

    // Ward filter
    if (!empty($wardId)) {
        $query->whereHas('property', function ($q) use ($wardId) {
            $q->where('ward', $wardId);
        });
    }

    // User filter
    if (!empty($userId)) {
        $query->whereHas('property.user', function ($q) use ($userId) {
            $q->where('id', $userId);
        });
    }

    // ======================
    // MONTHLY
    // ======================
    if ($type === 'monthly') {

        $results = $query
            ->selectRaw('
                MONTH(created_at) as month_no,
                DATE_FORMAT(created_at, "%b") as month,
                SUM(arrear_calc) as total
            ')
            ->groupBy('month_no', 'month')
            ->orderBy('month_no')
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $row->month,
                    'total' => round($row->total, 2),
                ];
            });

    }
    // ======================
    // YEARLY
    // ======================
    else {

        $results = $query
            ->selectRaw('YEAR(created_at) as year, SUM(arrear_calc) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($row) {
                return [
                    'year'  => $row->year,
                    'total' => round($row->total, 2),
                ];
            });
    }

    return response()->json([
        'status' => true,
        'data'   => $results,
    ]);
}




}
