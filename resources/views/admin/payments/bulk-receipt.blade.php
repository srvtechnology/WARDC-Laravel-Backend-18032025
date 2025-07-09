<!doctype html>
<html lang="en">
<head>
    @include('admin.payments.print-head')
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>



<div class="page" style="width:100%;">
    @foreach($properties as $property)
  data {{$property->id}}
        @php
        $property->assessment->setPrinted();
        $district = App\Models\District::where('name', $property->district)->first();
        @endphp

        @include('admin.payments.receipt-content', ['property' => @$property, 'assessment' => @$property->assessment, 'paymentInQuarter' => @$property->getPaymentsInQuarter($year), 'year' => $year,'district'=>$district])

        @include('admin.payments.receipt-account-details', ['property' => @$property,  'assessment' => @$property->assessment, 'paymentInQuarter' => @$property->getPaymentsInQuarter($year), 'year' => $year,'district'=>@$district])

        <div class="page-break"></div>
        @include('admin.payments.receipt-policy',['property' => @$property, 'assessment' => @$property->assessment,'district'=>@$district, 'year' => $year])

         @include('admin.payments.repict_assement_property_payment',['property' => @$property])

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</div>

</body>
</html>
