<div class="receipt">
    <div class="container">
        <div class="receipt-content">
            <div class="row">
                <div class="col-lg-12">
                    <p class="text-uppercase text-center" style="text-align:center; font-size: 12px; margin-bottom: 5px;">
                        <strong>PLEASE
                            BRING THIS DEMAND NOTE
                            TOGETHER
                            WITH
                            RECEIPTS FOR ALL PREVIOUS INSTALMENTS AT TIME OF PAYMENT</strong></p>
                </div>
                {{-- {{dd($district)}} --}}

                <table class="" width="100%">
                    <tr>
                        <td align="left" style="width: 18%;">
                            <img src="http://wardc.online/images/logo1.png" style="padding: 0 15px;" alt="">

                           {{--  <img style="padding: 0 15px;" src="{{ @$district->getPrimaryLogoUrl(0, 0, true) }}"
                                alt=""> --}}
                        </td>
                        <td style="text-align:center;">
                            <h1 class="text-center" style="font-weight:500;">{{ @$district->council_name }} -
                                {{ @$district->council_short_name }}</h1>
                            <h6 class="text-center mb-4" style="margin:0;font-weight:500;">
                                {{ @$district->council_address }}</h6>
                            <h4 class="text-center font-weight-600" style="margin-bottom:5px;font-weight:500;">PROPERTY
                                RATE DEMAND NOTE</h4>
                            <h6 class="text-center font-weight-bold mb-3" style="margin:0; font-weight:bold;">JANUARY –
                                DECEMBER {{ @$assessment->created_at->year }}</h6>
                        </td>
                        <td align="right" style="width: 18%;">
                            {{-- <img style="padding: 0 15px;" src="http://wardc.online/images/logo2.jpg" alt=""> --}}
                            @if (@$assessment->property_id == 250)
                                <img style="padding: 0 15px;"
                                    src="https://i.ibb.co/Y8Rsxhz/copy-QGl-Rq-CNh-Qmd-Jchssde-DNa-DNUmhlx-Aypzf-Ur-J8-AWy-image-85x85.jpg"
                                    alt="">
                            @else
                            @if(@$assessment->assessment_images_1)
                                <img style="padding: 0 15px;" src="https://wardc.srvtechnology.com/storage/app/public/{{ @$assessment->assessment_images_1 }}"
                                    alt="">
                                @else
                                <img style="padding: 0 15px;" src="https://wardc.srvtechnology.com/storage/app/public/{{ @$assessment->assessment_images_2 }}"
                                    alt="">
                                @endif
                            @endif

                        </td>
                    </tr>
                </table>

                <div class="col-lg-12">
                    <table class="table table-bordered"
                        style=" border:1px solid lightgray;width:100%; margin-bottom:15px;margin-top:5px;">
                        <thead>
                            <tr>
                                <th scope="col" colspan="7">ASSESSMENT DETAILS</th>
                            </tr>
                        </thead>
                    </table>

                    <table class="table table-bordered" style="width:100%;margin-bottom:15px;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #ccc; text-align:left;width: 10%" scope="col"
                                    class="text-left">
                                    <span
                                        style="white-space: pre">{{ @$property->is_organization ? 'ORGANIZATION' : 'OWNER' }} NAME</span>
                                </th>
                                <th style="border:1px solid #ccc;" scope="col">
                                    {{ @$property->is_organization ? @$property->organization_name : optional(@$property->landlord)->first_name . ' ' . optional(@$property->landlord)->middle_name . ' ' . optional(@$property->landlord)->surname }}
                                </th>
                                <th style="border:1px solid #ccc;width: 5%" scope="col">
                                    <span style="white-space: pre">TEL:</span>
                                </th>
                                <th style="border:1px solid #ccc; width: 10%" scope="col">
                                    <span>
                                        {{ @$property->landlord->mobile_1 }}
                                        <!-- {{ strlen(@$property->landlord->mobile_2) > 5 ? ', ' . @$property->landlord->mobile_2 : '' }} -->
                                    </span>
                                </th>
                            </tr>
                        </thead>
                    </table>

                    <table class="table table-bordered" style="width:100%;margin-bottom:15px;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #ccc;text-align:left; width: 10%" scope="col"
                                    class="text-left">
                                    <span style="white-space: pre">PROPERTY ADDRESS</span>
                                </th>
                                <th style="border:1px solid #ccc;" scope="col">{{ @$property->street_number }}
                                    , {{ @$property->street_name }}, Ward:{{ @$property->ward }},
                                    Constituency:{{ @$property->constituency }}, {{ @$property->section }}
                                    , {{ @$property->chiefdom }}
                                    , {{ str_replace('Province', 'Area', @$property->province) }}</th>
                            </tr>
                        </thead>
                    </table>
                    <table class="table table-bordered" style=" width:100%;margin-bottom:15px;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #ccc; text-align: left" scope="col">CATEGORY</th>
                                <th style="border:1px solid #ccc;" scope="col">PROPERTY TYPE</th>
                                <th style="border:1px solid #ccc;white-space: pre" scope="col">PROPERTY AREA
                                    (M<sup>2</sup>)</th>
                                <th style="border:1px solid #ccc;" scope="col">WALL MATERIAL</th>
                                <th style="border:1px solid #ccc;" scope="col">ROOF MATERIAL</th>
                                <th style="border:1px solid #ccc; text-align: left" scope="col">PROPERTY USE</th>
                                <!-- <th style="border:1px solid #ccc;" scope="col" width="20%">WINDOW TYPE</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border:1px solid lightgray;">
                                    {{ strtoupper(@$assessment->categories->pluck('label')->implode(', ')) }}</td>
                                <td style="border:1px solid lightgray;">
                                    {{ strtoupper(@$assessment->types->pluck('label')->implode(', ')) }}</td>
                                <td style="border:1px solid lightgray;">
                                    {{ strtoupper(optional(@$assessment)->square_meter) }}
                                    {{ optional(@$assessment)->square_meter ? ' SQ METERS' : '' }}</td>
                                <td style="border:1px solid lightgray;">
                                    {{ strtoupper(optional(optional(@$assessment)->wallMaterial)->label) }}</td>
                                <td style="border:1px solid lightgray;">
                                    {{ strtoupper(optional(optional(@$assessment)->roofMaterial)->label) }}</td>
                                <!-- <td style="border:1px solid lightgray; ">{{ strtoupper(optional(optional(@$assessment)->windowType)->label) }}</td> -->
                                <td style="border:1px solid lightgray; ">
                                    {{ strtoupper(@$property->occupancies->pluck('occupancy_type')->implode(', ')) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table table-bordered" style=" width:100%;margin-bottom:15px;">
                        <thead>
                            <tr>

                                <th style="border:1px solid #ccc;" scope="col">VALUE ADDED</th>
                                <td style="border:1px solid #ccc;" scope="col">
                                    {{ strtoupper(@$assessment->valuesAdded->pluck('label')->implode(', ')) }}</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                            </tr>
                        </tbody>
                    </table>


                    <table class="table table-bordered" style="width:100%;margin-bottom:15px;" cellspacing="0"
                        cellpadding="0">
                        <thead>
                            <tr>
                                <th style="border:1px solid #ccc;" scope="col">RATE DUE {{ @$yr ? @$yr : @$year }}
                                </th>
                                <th style="border:1px solid #ccc;" scope="col">ARREARS<br />Past Year(s)</th>
                                <th style="border:1px solid #ccc;" scope="col">PENALTY</th>
                                <th style="border:1px solid #ccc;" scope="col">1st INSTALMENT<br />DUE
                                    31-03-{{ @$yr ? @$yr : @$year }}</th>
                                <th style="border:1px solid #ccc;" scope="col">2nd INSTALMENT<br />DUE
                                    30-06-{{ @$yr ? @$yr : @$year }}</th>
                                {{-- <th style="border:1px solid #ccc;" scope="col">3rd INSTALMENT<br/>DUE 30-09-{{ @$yr ? @$yr :  @$year   }}</th>
                            <th style="border:1px solid #ccc;" scope="col">4th INSTALMENT<br/>DUE 31-12-{{ @$yr ? @$yr :  @$year   }}</th> --}}
                                <th style="border:1px solid #ccc;" scope="col">TOTAL DUE
                                    31-12-{{ @$yr ? @$yr : @$year }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                {{-- {{dd($property->assessmentHistory)}} --}}



                                {{-- <td style="border:1px solid lightgray;">{!! number_format(@$assessment->getPropertyTaxPayable()) !!}</td> --}}
                                @if (!@$assed_value)
                                    @php
                                        @$assesment_value_new = 0;
                                        for ($i = 0; $i < count(@$property->assessmentHistory); $i++) {
                                            if (@$property->assessmentHistory[$i]->created_at->format('Y') == @$year) {
                                                @$assesment_value_new = @$property->assessmentHistory[$i]
                                                    ->current_year_assessment_amount;
                                            }
                                        }

                                        // dd($assesment_value_new);

                                    @endphp
                                    <td style="border:1px solid lightgray;color: red;">NLe {!! number_format(@$assesment_value_new, 2) !!}</td>
                                @else
                                    <td style="border:1px solid lightgray;color: red;">NLe {!! number_format(@$assed_value, 2) !!}</td>
                                @endif



                                {{-- <td style="border:1px solid lightgray;">{!! number_format(@$assessment->getPastPayableDue()) !!}</td> --}}
                                @for ($i = 0; $i < count(@$property->assessmentHistory) - 1; $i++)
                                    @if ($property->assessmentHistory[$i]->created_at->year < @$year)
                                        @php
                                            $tot_paid = 0;
                                        @endphp
                                        @for ($j = 0; $j < count(@$assessment->payments); $j++)
                                            @if ($property->assessmentHistory[$i]->created_at->year == @$assessment->payments[$j]->created_at->year)
                                                @php
                                                    @$tot_paid += @$assessment->payments[$j]->total;
                                                @endphp
                                            @endif
                                        @endfor
                                        @if ($i == 0)
                                            @php
                                                @$arrear_amt +=
                                                    $property->assessmentHistory[$i]->current_year_assessment_amount -
                                                    @$tot_paid;
                                            @endphp
                                        @else
                                            @php
                                                @$arrear_amt =
                                                    $property->assessmentHistory[$i]->current_year_assessment_amount +
                                                    @$arrear_amt +
                                                    0.25 * @$arrear_amt -
                                                    @$tot_paid;
                                            @endphp
                                        @endif
                                    @endif
                                    {{-- {{dd(@$property->id)}} --}}
                                @endfor

                                @if (!@$arrays)
                                    <td style="border:1px solid lightgray;">NLe {!! number_format(@$arrear_amt, 2) !!} </td>
                                @else
                                    <td style="border:1px solid lightgray;"> NLe {!! number_format(@$arrays, 2) !!}</td>
                                @endif




                                {{-- @if ($property->id = '15596') --}}
                                @if (@$panelty_new)
                                    <td style="border:1px solid lightgray;"> NLe {!! number_format(@$panelty_new, 2) !!}</td>
                                    {{--  <td style="border:1px solid lightgray;">Le 0</td> --}}
                                @elseif(!@$panelty_new)
                                    <td style="border:1px solid lightgray;">NLe {!! number_format(@$arrear_amt * 0.25, 2) !!}</td>
                                @else
                                    {{-- <td style="border:1px solid lightgray;"> Le {!! number_format(@$panelty_new,2)  !!}</td> --}}
                                    <td style="border:1px solid lightgray;">NLe 0</td>
                                @endif











                                @php
                                    @$pay_year = @$assessment->payments[0]->payment_made_year;
                                    @$total_paid_amount = 0;
                                    for ($i = 0; $i < count(@$assessment->payments); $i++) {
                                        if (@$assessment->payments[$i]->created_at->format('Y') == @$year) {
                                            @$total_paid_amount += @$assessment->payments[$i]->total;
                                        }
                                    }

                                @endphp
                                <td style="border:1px solid lightgray;">NLe {!! number_format(@$total_paid_amount, 2) !!} </td>
                                <td style="border:1px solid lightgray;"> </td>

                                {{-- {{dd($assessment->payments[0]->total)}} --}}




                                @for ($i = 0; $i < count(@$property->assessmentHistory); $i++)
                                    @if ($property->assessmentHistory[$i]->created_at->year <= @$year)
                                        @php
                                            $tot_paid = 0;
                                        @endphp
                                        @for ($j = 0; $j < count(@$assessment->payments); $j++)
                                            @if ($property->assessmentHistory[$i]->created_at->year == @$assessment->payments[$j]->created_at->year)
                                                @php
                                                    @$tot_paid += @$assessment->payments[$j]->total;
                                                @endphp
                                            @endif
                                        @endfor
                                        {{-- {{@$tot_paid}} --}}
                                        @if ($i == 0)
                                            @php
                                                @$total_due +=
                                                    $property->assessmentHistory[$i]->current_year_assessment_amount -
                                                    @$tot_paid;
                                            @endphp
                                        @else
                                            @php
                                                @$total_due =
                                                    $property->assessmentHistory[$i]->current_year_assessment_amount +
                                                    $total_due +
                                                    0.25 * $total_due -
                                                    @$tot_paid;
                                            @endphp
                                        @endif

                                        {{-- {{dd(@$total_due)}} --}}
                                    @endif
                                @endfor
                                @if (@$due)
                                    {{-- <td style="border:1px solid lightgray;">NLe 3525.35 </td> --}}
                                    <td class="font-weight-bold"
                                        style="border:1px solid lightgray;color: red;font-size: 12px"> NLe
                                        {!! number_format(@$due, 2) !!}</td>
                                @elseif(!@$due)
                                    <td class="font-weight-bold"
                                        style="border:1px solid lightgray;color: red;font-size: 12px">NLe
                                        {!! number_format(@$total_due, 2) !!} </td>
                                @else
                                    <td class="font-weight-bold"
                                        style="border:1px solid lightgray;color: red;font-size: 12px">NLe 3525.35 </td>
                                    {{--  <td style="border:1px solid lightgray;"> Le1 {!! number_format(@$due,2)  !!}</td>
                           --}}
                                @endif


                            </tr>
                        </tbody>
                    </table>

                    <table class="total" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <!-- Column 1: "PLEASE DISREGARD ARREARS IF PAID" -->
                            <td style="text-align: left; padding-right: 10px; width: 35%; border: 1px solid #ccc;">
                                <h6 class="font-weight-bold" style="white-space: pre;">PLEASE DISREGARD ARREARS IF PAID</h6>
                            </td>
                            <!-- Column 2: "ID" -->
                            <td style="border: 1px solid #ccc; width: 20%; text-align: center;">
                                <h5 class="red" style="font-size: 12px; margin: 0; white-space: nowrap;">
                                    ID: {{ @$property->getPrintableId() }}
                                </h5>
                            </td>
                            <!-- Column 3: "NOTICE NUMBER" -->
                            <td style="border: 1px solid #ccc; width: 20%; text-align: center;">
                                <h5 style="font-size: 12px; margin: 0; white-space: nowrap;">
                                    NOTICE NUMBER:
                                </h5>
                            </td>
                            <!-- Column 4: Digital Address -->
                            <td style="border: 1px solid #ccc; width: 25%; text-align: center;">
                                <h5 style="font-size: 12px; margin: 0; white-space: nowrap;">
                                    {{ @$property->newDigitalAddress() }}
                                </h5>
                            </td>
                        </tr>
                    </table>



                    <div class="clearfix"></div>
                    <p class="notice-text" style="font-size: 9px !important; margin-bottom: 5px; margin-top: 8px">
                        {{ @$district->penalties_note }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
