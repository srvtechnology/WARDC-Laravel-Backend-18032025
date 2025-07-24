{{-- repict_assement_property_payment.blade.php
{{$property->id}} --}}
<style>
    table,
    th,
    td {
        border: 1px solid black;
    }
</style>

<body>
    <h2>
        Assessment History
    </h2>
    <table style="width:100%">
        <tr>
            <th>Assessment Year</th>
            <th>Assessment amount</th>
            <th>Arrears</th>
            <th>Penalty</th>
            <th>Amount Paid</th>
            <th>Due</th>
        </tr>
        <tbody>
            @php
                $CurrentYearAssessmentAmount = 0;
                $PastPayableDue = 0;
                $Penalty = 0;
                $CurrentYearTotalPayment2021 = 0;
                $CurrentYearTotalDue = 0;
                $CurrentYearTotalDue2021 = 0;
                $PastPayableDue2022 = 0;
                $CurrentYearTotalPayment2022 = 0;
                $Penalty2022 = 0;
                $CurrentYearTotalDue2022 = 0;
                $PastPayableDue = 0;
            @endphp
            {{-- new code ---------------------------- --}}
            @for ($i = 0; $i < count(@$property->assessmentHistory); $i++)
                {{-- {{dd($property->assessmentHistory[$i]->getCurrentYearTotalPayment())}} --}}
                @php
                    $AssessmentYear = $property->assessmentHistory[$i]->created_at->year;
                    $CurrentYearAssessmentAmount = $property->assessmentHistory[$i]->current_year_assessment_amount;
                    if ($PastPayableDue > 0) {
                        $Penalty = $PastPayableDue * 0.25;
                    } else {
                        $Penalty = 0;
                    }
                    $CurrentYearTotalPayment = $property->assessmentHistory[$i]->getCurrentYearTotalPayment();
                    $CurrentYearTotalDue =
                        $CurrentYearAssessmentAmount + $PastPayableDue + $Penalty - $CurrentYearTotalPayment;
                @endphp

                <tr>
                    <td>{!! $AssessmentYear !!}</td>
                    <td>{!! number_format($CurrentYearAssessmentAmount, 2) !!}</td>
                    <td>{!! number_format($PastPayableDue, 2) !!}</td>
                    <td>{!! number_format($Penalty, 2) !!}</td>
                    <td>{!! number_format($CurrentYearTotalPayment, 2) !!}</td>
					{{-- @dd($CurrentYearTotalDue) --}}
                    <td>{!! number_format($CurrentYearTotalDue, 2) !!}</td>
                </tr>
                @php
                    $PastPayableDue = $CurrentYearTotalDue;
                @endphp
            @endfor
            {{-- end new code ---------------------------- --}}
            <div style="display:none">
                {{ $Penalty2022 = $property->assessmentHistory[0]->balance * 0.25 }}
                {{ $CurrentYearTotalPayment = $property->assessmentHistory[0]->amount }}
                {{ $CurrentYearTotalDue2022 = $property->assessmentHistory[0]->current_year_assessment_amount + $CurrentYearTotalDue2021 + $Penalty2022 }}
                {{-- for pdf values pass 2022 --}}
                {{ $assed_val_for_pdf_2022 = $property->assessmentHistory[0]->current_year_assessment_amount }}
                {{ $array_val_for_pdf_2022 = $property->assessmentHistory[0]->balance }}
                {{ $Penalty_for_pdf_2022 = 0 }}
                {{ $due_for_pdf_2022 = $CurrentYearTotalDue2022 }}
                {{ $CurrentYearTotalDue2022 = $CurrentYearAssessmentAmount ?: $property->assessmentHistory[0]->current_year_assessment_amount + $property->assessmentHistory[0]->balance + $property->assessmentHistory[0]->balance * 0.25 }}
            </div>
        </tbody>
    </table>




















    {{--  <br>
	<br>
	<h2>
	Transactions History
	</h2>
	@if ($property->payments()->count())
	
	<table style="width:100%">
		
		<tr>
			<th>Property ID</th>
			<th>Transaction ID</th>
			<th>Cashier Name</th>
			<th>Amount Due</th>  --}}
    {{-- <th>Amount</th> --}}
    {{-- <th>Penalty</th> --}}
    {{--  <th>Amount Paid</th>
			<th>Remaining Balance</th>
			<th>Payment Type</th>
			<th>Cheque Number</th>
			<th>Payee Name</th>
			<th>Transaction Date</th>  --}}
    {{-- <th>Action</th> --}}
    {{--  </tr>
		<tbody>
			@foreach ($property->payments()->latest()->get() as $payment)
			
			<tr>
				<td>{{ $payment->property_id }}</td>
				<td>{{ $payment->id }}</td>
				<td>{{ $payment->admin->getName() }}</td>
				<td>{{ number_format($payment->assessment) }}</td>  --}}
    {{-- <td>{{ number_format($payment->amount) }}</td>
				<td>{{ number_format($payment->penalty) }}</td> --}}
    {{--  <td>{{ number_format(@$payment->total) }}</td>
				<td>{{ number_format(@$payment->balance < 0 ? 0 : $payment->balance) }}</td>
				<td>{{ ucwords(@$payment->payment_type) }}</td>
				<td>{{ @$payment->cheque_number }}</td>
				<td>{{ @$payment->payee_name }}</td>
				<td>{{ \Carbon\Carbon::parse(@$payment->created_at)->format('Y M, d H:i A') }}</td>
				
			</tr>
			@endforeach
		</tbody>
		
	</table>
	@else
	<strong>NO PAYMENT DATA</strong>
	@endif  --}}











    {{-- 


	<br>
	<br>
	<style>
		.tables, .tables th, .tables td {
			border: none !important;
			text-align: left !important;
		}
	</style>
	<h2>Property Details</h2>
	<table style="width:100%;" class="tables" >
		<tr>
			<th>Street Number</th>
			<th>Street Name</th>
			<th>Ward</th>
			<th>Constituency</th>
		</tr>
		<tr>
			<td>{{$property->street_number}}</td>
			<td>{{$property->street_name}}</td>
			<td>{{$property->ward}}</td>
			<td>{{$property->constituency}}</td>
		</tr>
	</table>


<br>
<br>
<hr>


	<table style="width:100%;" class="tables" >
		<tr>
			<th>Section </th>
			<th>Chiefdom </th>
			<th>District</th>
			<th>Province</th>
		</tr>
		<tr>
			<td>{{$property->section}}</td>
			<td>{{$property->chiefdom}}</td>
			<td>{{$property->district}}</td>
			<td>{{$property->province}}</td>
		</tr>
	</table>



<br>
<br>
<hr>


	<table style="width:100%;" class="tables" >
		<tr>
			<th>Postcode </th>
			<th>Is Property Inaccessible </th>
			<th>Property Inaccessible</th>
			<th>Demand Note Delivered</th>
		</tr>
		<tr>
			<td>{{$property->postcode}}</td>
			<td>{{ $property->is_property_inaccessible ? 'Yes' : 'No' }}</td>
			<td>{{ $property->propertyInaccessible->pluck('label')->implode(', ') }}</td>
			<td>{{ $property->is_draft_delivered ? 'Yes' : 'No' }}</td>
		</tr>
	</table>




<br>
<br>
<hr>

	<table style="width:100%;" class="tables" >
		<tr>
			<th>Recipient Name </th>
			<th>Recipient Number</th>
			<th>Recipient Image</th>
		</tr>
		<tr>
			<td>{{ $property->delivered_name ?: 'Un-specified' }}</td>
			<td>{{ $property->delivered_number ?: 'Un-specified' }}</td>
			<td>@if ($property->delivered_image)
				<a href="{{$property->getDeliveredImagePath(800,800)}}" data-sub-html="">
					<img class="img-responsive thumbnail"
					src="{{$property->getDeliveredImagePath(50,50)}}">
				</a>
			@endif</td>
		</tr>
	</table> --}}
