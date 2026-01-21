@php
    use App\Models\PropertyAssessmentDetail;
    use App\Models\PropertyPayment;
    use Carbon\Carbon;

    $propertyId = $property->id;

    // Fetch assessments
    $allAssesments = PropertyAssessmentDetail::where('property_id', $propertyId)
        ->select(
            'id',
            'created_at',
            'arrear_calc',
            'penalty as penalty_amount',
            'due',
            'property_rate_without_gst',
            'property_rate_with_gst',
            'demand_note_recipient_photo'
        )
        ->orderBy('created_at')
        ->get();

    // Attach payment amount year-wise
    foreach ($allAssesments as $assessment) {
        $year = Carbon::parse($assessment->created_at)->year;

        $payment = PropertyPayment::where('property_id', $propertyId)
            ->whereYear('created_at', $year)
            ->sum('total');

        $assessment->paymentAmount = $payment ?? 0;
    }
@endphp


<style>
    table, th, td {
        border: 1px solid black;
    }
</style>

<h2>Assessment History</h2>

<table style="width:100%">
    <thead>
        <tr>
            <th>Assessment Year</th>
            <th>Assessment Amount</th>
            <th>Arrears</th>
            <th>Penalty</th>
            <th>Amount Paid</th>
            <th>Due</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($allAssesments as $assessment)
            <tr>
                <td>{{ \Carbon\Carbon::parse($assessment->created_at)->year }}</td>

                <td>{{ number_format($assessment->property_rate_without_gst ?? 0, 2) }}</td>

                <td>{{ number_format($assessment->arrear_calc ?? 0, 2) }}</td>

                <td>{{ number_format($assessment->penalty_amount ?? 0, 2) }}</td>

                <td>{{ number_format($assessment->paymentAmount ?? 0, 2) }}</td>

                <td>{{ number_format($assessment->due ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
