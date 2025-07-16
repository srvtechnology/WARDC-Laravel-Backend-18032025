<!doctype html>
<html lang="en">
<head>
    @include('admin.envelope.print-head')
</head>
<body>

{{--<div class="print_row">--}}
{{--    <a onclick="myFunction()">Print this page</a>--}}
{{--</div>--}}
<div class="page">
    @include('admin.envelope.receipt-content', ['property' => $property, 'paymentInQuarter' => $paymentInQuarter,'district' => $district, 'year' => $year])

</div>

{{--</div>--}}


{{--<script>--}}
{{--    function myFunction() {--}}
{{--        window.print();--}}
{{--    }--}}
{{--</script>--}}

{{--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>--}}
{{--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>--}}
</body>
</html>
