<!doctype html>
<html lang="en">
<head>
    @include('admin.envelope.print-head')
</head>
<body>

<div class="page" style="width:90%;margin-left:auto;margin-right:auto">
    @foreach($properties as $property)

        @php
        $district = App\Models\District::where('name', $property->district)->first();
        @endphp

        @include('admin.envelope.receipt-content', ['property' => $property, 'assessment' => $property->assessment, 'paymentInQuarter' => $property->getPaymentsInQuarter($year), 'year' => $year,'district'=>$district])

        <!-- <div class="page-break"></div> -->
        @if(!$loop->last)
            {{-- <div class="page-break"></div> --}}
        @endif
    @endforeach
</div>

</body>
</html>
