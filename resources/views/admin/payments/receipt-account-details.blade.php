<div class="receipt-second">
    <div class="container">
        <div class="receipt-content">
            <div class="row">
                <div class="col-lg-12">
                    <p class="font-weight-bold" style="font-size: 12px;text-transform: uppercase;">THE {{$district->council_name}} DEMANDS
                        PAYMENT OF MUNICIPAL RATE IN
                        RESPECT OF THE PERIOD COMMENCING 1ST JANUARY TO 31ST
                        DECEMBER {{ $assessment->created_at->year }} IN 2
                        INSTALLMENTS ON OR
                        BEFORE THE FOLLOWING DATES</p>

                    <table width="100%">
                        <tr>
                            <td align="left" style="text-align: left; font-weight:bold;" class="installment-section">
                                <ul>
                                    <li><span>FIRST INSTALLMENT</span></li>
                                    <li><span>SECOND INSTALLMENT</span></li>
                                    <!-- <li><span>THIRD INSTALLMENT</span></li>
                                    <li><span>FINAL INSTALLMENT</span></li> -->
                                </ul>
                            </td>
                            <td align="left" style="text-align: left; font-weight:bold;" class="installment-section">
                                <ul>
                                    <li><span>01-01-2025 - 31-03-{{ $assessment->created_at->year }}</span></li>
                                    <li><span>01-04-2025 - 30-06-{{ $assessment->created_at->year }}</span></li>
                                    <!-- <li><span>- 30-09-{{ $assessment->created_at->year }}</span></li>
                                    <li><span>- 31-12-{{ $assessment->created_at->year }}</span></li> -->
                                </ul>
                            </td>
<!--                             <td width="40%" align="right" style="text-align: right;">
                                 {{--  <img style="margin-right: 5px;" src="{{$assessment->getImageAnyUrl(85,85)}}">  --}}
                            </td> -->
               

                         
                        </tr>
                    </table>

                    <p class="mb-0 special-text font-weight-bold">
                        <!-- WARNING: {{-- $district->warning_note --}} -->
                        PLEASE NOTE: A SURCHARGE OF 25% WILL BE LEVIED ON THE TOTAL UNPAID OR ARREARS AMOUNT DUE AFTER 31 DECEMBER OF EVERY CALENDAR YEAR
                    </p>
                    <p class="font-weight-bold">BANK ACCOUNTS FOR COLLECTION OF MUNICIPAL RATE REVENUE</p>

                    <table width="100%">
                        <tr style="vertical-align: top;">
                            <td>
                                <table class="mb-4 table-bordered table" style="width:100%;">
                                    <thead>
                                    <tr>
                                        <th style="border:1px solid lightgray;"></th>
                                        <th style="border:1px solid lightgray;text-align: left;">Location</th>
                                        <th style="border:1px solid lightgray;">BANK</th>
                                        <th style="border:1px solid lightgray;">ACCOUNT NAME</th>
                                        <th style="border:1px solid lightgray;">ACCOUNT NUMBER</th>

                                    </tr>
                                    </thead>
                                    <tbody>
                                    @if($district->bank_details)
                                        @foreach($district->bank_details as $bkey => $bank_detail)
                                            @if(!($bank_detail['location'] == '' && $bank_detail['name'] == '' && $bank_detail['account_name'] == '' && $bank_detail['account_number'] == ''))
                                           

                                            <tr>
                                                <td style="border:1px solid lightgray;color: red;">1</td>
                                                <td style="border:1px solid lightgray;color: red;text-align: left;"><strong>{{$bank_detail['location']}}</strong></td>
                                                <td style="border:1px solid lightgray;color: red;">UNITED BANK FOR AFRICA (UBA)</td>
                                                <td style="border:1px solid lightgray;color: red;">{{$bank_detail['account_name']}}</td>
                                                <td style="border:1px solid lightgray;color: red;">540910060000010</td>

                                            </tr>

                                             <tr>
                                            
                                                <td style="border:1px solid lightgray;color: red;">2</td>
                                                <td style="border:1px solid lightgray;color: red;text-align: left;"><strong>{{$bank_detail['location']}}</strong></td>
                                                <td style="border:1px solid lightgray;color: red;">ZENITH BANK</td>
                                                <td style="border:1px solid lightgray;color: red;">{{$bank_detail['account_name']}}</td>
                                                <td style="border:1px solid lightgray;color: red;">6010497604</td>

                                            </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </td>

                        </tr>
                    </table>
                    
                    <!-- <p class="mb-0 special-text font-weight-bold">Pay your WARDC rate:

1. with Orange Money:
Dial #144*3*5# and follow the instructions 



2. With Afrimoney:
Dial*161#, select Pay Bill, select WARDC and follow the instructions”</p> -->

                    @if($district->collection_point)
                    <p class="font-weight-bold">CONTACT CENTERS</p>

                    @php
                        $maxNoOfTd = max(count(array_filter($district->collection_point)), count(array_filter($district->collection_point2)));
                    @endphp
                    <table class="table-bordered table" style="width:100%;">
                        <tbody>
                        @if(array_filter($district->collection_point))    
                        <tr>
                        @php
                            $point1TdCount = count(array_filter($district->collection_point));
                        @endphp

                            @foreach($district->collection_point as $ekey => $collection)
                                @if ($loop->first)
                                    <td style="border:1px solid lightgray;text-align: left;"><strong>{{$collection}}</strong></td>
                                @else
                                    <td style="border:1px solid lightgray;">{{$collection}}</td>
                                @endif
                            @endforeach

                            @php
                                $remainingPoint1TdCount = $maxNoOfTd - $point1TdCount;
                            @endphp                            

                            @for ($i = 0; $i < $remainingPoint1TdCount; $i++)
                                <td style="border:1px solid lightgray;">&nbsp;</td>
                            @endfor

                        </tr>
                        @endif

                        @if(array_filter($district->collection_point2))
                        <tr>
                        @php
                            $point2TdCount = count(array_filter($district->collection_point2));
                        @endphp                            
                            @foreach(array_filter($district->collection_point2) as $ekey => $collection2)
                                @if ($loop->first)
                                    <td style="border:1px solid lightgray;text-align: left;"><strong>{{$collection2}}</strong>
                                    </td>
                                @else
                                    <td style="border:1px solid lightgray;">{{$collection2}}</td>
                                @endif

                            @endforeach

                            @php
                                $remainingPoint2TdCount = $maxNoOfTd - $point2TdCount;
                            @endphp                            

                            @for ($i = 0; $i < $remainingPoint2TdCount; $i++)
                                <td style="border:1px solid lightgray;">&nbsp;</td>
                            @endfor

                        </tr>
                        @endif
                        </tbody>
                    </table>
                    @endif

                    <p class="mb-2 special-text font-weight-bold">PLEASE QUOTE ACCOUNT NAME AND NUMBER ABOVE ON BANK
                        SLIPS WHEN MAKING CHEQUE AND CASH PAYMENTS.</p>
                    <p class="mb-0 special-text font-weight-bold">PAYMENT IS DUE 4 WEEKS AFTER RECIEPT OF THIS NOTICE
                        AND MUST COVER PREVIOUS/PAST INSTALLMENT DATES SHOWN ABOVE.</p>


                </div>
            </div>

            <table width="100%">
                <tr>
                    <td style="text-align: left; width: 60%">
                        <table style="width: 100%; text-align: left;">
                            <tr>
                                <td style="text-align: left; padding-left: 47px;" >
                                  <div style="text-align:center;">
  <img
    src="https://www.wardc.online/apis/storage/app/public/property/ca_signature2.png"
    alt="Signature"
    style="max-height: 60px; width: auto;"
  >
</div>

                                 {{--    <img src="{{  $district->getChifAdministratorSignUrl(0,0,true) }}" alt=""
                                         style="height: 70px;"> --}}
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">....................................................</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; font-size: 12px">CHIEF ADMINISTRATOR
                                    ({{  $district->council_short_name }})
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style=" width: 40%;">
                        {{-- <table style="width: 100%;   ">
                            <tr>
                                <td align="center" style=" text-align: right; padding-right: 65px;">
                                    <img src="{{  $district->getCeoSignUrl(0,0,true) }}" alt="" style="width: 70px; ">
                                </td>
                            </tr>
                            <tr>
                                <td style=" text-align: right; padding-right: 5px;">
                                    ...............................................
                                </td>
                            </tr>
                            <tr>
<!--                                 <td style=" text-align: right; margin-right: 30px;font-size: 12px">CEO (SIGMA VENTURES
                                    LTD.)
                                </td>   -->                              
                                <td style=" text-align: right; margin-right: 30px;font-size: 12px">
                                    <span style="margin-right: 35px;">CHAIRMAN ({{  $district->council_short_name }})</span>
                                </td>
                            </tr>
                        </table> --}}
                    </td>
                </tr>
            </table>

            <table width="100%">
                <tr>
                    <td>
                        <p style="text-align: left;font-size: 13px;">

                            <span style="display: block; margin-bottom: 4px">Enquiries: Send E-mails to <a
                                    href="mailto:{{  $district->enquiries_email }}"> {{  $district->enquiries_email }}</a></span>
                            {{-- @if($district->enquiries_phone!='')    --}}     
                            <span style="display: block;">
                            <span style="color: red; font-weight: bold;">FOR MOBILE</span>
                            <!-- <span style="font-size: 14px; font-weight: bold">{{  $district->enquiries_phone }}</span></span> -->
                            <span style="font-size: 14px; font-weight: bold">+23278218248</span></span>
                           {{--  @endif
                            @if($district->enquiries_phone2!='')   --}}
                            <span style="display: block;">
                            <span style="color: red; font-weight: bold;">FOR MOBILE</span>
                            <!-- <span style="font-size: 14px; font-weight: bold">{{  $district->enquiries_phone2 }}</span></span> -->
                            <span style="font-size: 14px; font-weight: bold">+23231887449</span></span>
                          {{--   @endif --}}
                        </p>
                    </td>
                    <td>
                        <p style="text-align: right;" class="officer-text">
                            Enumerator: {{ $property->userDetails->name ?? 'Unknown' }}
                        </p>
                    </td>
                </tr>
            </table>

        </div>
    </div>
</div>
