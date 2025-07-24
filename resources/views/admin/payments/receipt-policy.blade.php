<div class="receipt-second policy-content">
    <div class="container">
        <div class="receipt-content receipt-description" style="color: #888888;">
            
            <div style="text-align: center;width: 100%;">
                <strong style="color: #888888;">WARDC MUNICIPAL RATE MANAGEMENT SYSTEM (RATE CALCULATION) </strong>
            </div>
            
            <p>
                <strong> </strong>
                <strong></strong>
            </p>
            
            @php
            @$assesment_value_new = 0;
            for ($i=0; $i < count(@$property->assessmentHistory) ; $i++) {
            if(@$property->assessmentHistory[$i]->created_at->format('Y') == @$yr){
            @$assesment_value_new = @$property->assessmentHistory[$i]->current_year_assessment_amount;
            }
            }
            // dd($assesment_value_new);
            @endphp



            {{-- for due payment --}}
            @php
            $id=$property->id;
            if(@$yr){
            $year=@$yr;
            }
            else{
            $year=@$year;
            }

             $blankArray=[];


            //part 1
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


            // part -2
            for($i=0; $i<count(@$property->assessmentHistory); $i++){
            
            $AssessmentYear = $property->assessmentHistory[$i]->created_at->year;
            $CurrentYearAssessmentAmount = $property->assessmentHistory[$i]->current_year_assessment_amount;
            if($PastPayableDue > 0){
            $Penalty = $PastPayableDue*0.25;
            }
            else{
            $Penalty =0;
            }
            
            $CurrentYearTotalPayment = $property->assessmentHistory[$i]->getCurrentYearTotalPayment();
            $CurrentYearTotalDue =$CurrentYearAssessmentAmount+$PastPayableDue+$Penalty - $CurrentYearTotalPayment;
            
            
            $arr2=[];
            $arr2['propertyId']= $id;
            
            $arr2['AssessmentYear']=$AssessmentYear ;
            $arr2['CurrentYearAssessmentAmount']= $CurrentYearAssessmentAmount;
            $arr2['PastPayableDue']= $PastPayableDue;
            $arr2['Penalty']= $Penalty;
            $arr2['CurrentYearTotalPayment']= $CurrentYearTotalPayment;
            $arr2['CurrentYearTotalDue']= $CurrentYearTotalDue;
            if($AssessmentYear!="2023"){
            $PastPayableDue = $CurrentYearTotalDue;
            }
            array_push($blankArray,$arr2);
            
            }



            //part-3
            foreach($blankArray as $val){
            if($val['AssessmentYear']==$year){
            // dd($val,$year);
            @$yr=$year;
            $assed_value=$val['CurrentYearAssessmentAmount'];
            $arrays=$val['PastPayableDue'];
            $panelty_new=$val['Penalty'];
            $payments=$val['CurrentYearTotalPayment'];
            $due=$val['CurrentYearTotalDue'];
            }
            }
            @endphp
           
           <style>
               .wrc-heading {
                    font-size: 16px;
               }
           </style>
            
            
            <hr>
       
                @php
                $adjustments=DB::table('property_to_counsil_adjustment_group_a')->where('property_id',$property->id)->where('year',$assessment->created_at->format('Y'))->pluck('adjustment_id')->toArray();
                if(count($adjustments)>0){
                $adjustmentsDetails=DB::table('counsil_adjustment_group_a')->whereIn('id',$adjustments)->get();
                }
                @endphp
                <table style="width:100%;">
                    <tr>
                     <th> <h6 class="wrc-heading">Council adjustments</h6></th>
                     <th><h6 class="wrc-heading" style="text-align: center;" >Council adjustments Total Percentage</h6></th>
                    </tr>
                    <tr>
                     <td>   <p>
                         @if(count($adjustments)>0)
                         @foreach(@$adjustmentsDetails as $val)
                         <span>{{$val->name}}-{{$val->type}}</span> <span>( {{$val->sign}}  {{$val->percentage}} %)</span>
                         <br>
                         @endforeach
                         @else
                         <p>No adjustment seleceted</p>
                         @endif
                     </p></td>
                     <td>     <p style="text-align: center;">{{$property->assessment->total_adjustment_percent}} %</p></td>
                    </tr>
                 </table>
                {{--  <div style="display:flex;margin:0px;padding:0px;">
                    <div>
                       <h6 class="wrc-heading">Council adjustments</h6>
                <p>
                    @if(count($adjustments)>0)
                    @foreach(@$adjustmentsDetails as $val)
                    <span>{{$val->name}}-{{$val->type}}</span> <span>( {{$val->sign}}  {{$val->percentage}} %)</span>
                    <br>
                    @endforeach
                    @else
                    <p>No adjustment seleceted</p>
                    @endif
                </p>  
                </div>
 
                <div>
                    <h6 class="wrc-heading" style="text-align: center;" >Council adjustments Total Percentage</h6>
                    
                <p style="text-align: center;">{{$property->assessment->total_adjustment_percent}} %</p>
                </div>
                </div>  --}}
<hr style="margin-top:0px;">
    
















{{-- rate table --}}
        {{--  <br>
    <br>  --}}
    <style>
         .tables th, .tables td {
                border: 1px solid #e3e3e3 !important;
    padding: 5px;
            text-align: left !important;
        }
    </style>
    {{--  <table style="width:100%;" class="tables" >
        <tr>
            <th>PROPERTY TYPE</th>
            <th> DIMENSION</th>
            <th>RATE PER SQ M</th>
            <th>HABITABLE FLOOR</th>
        </tr>
        <tr>
            <td>{{ $property->assessment->typesTotal->pluck('label')->implode(', ') }}</td>
            <td>{{ strtoupper(number_format(optional($assessment)->square_meter)) }} {{ (optional($assessment)->square_meter) ? ' SQ METERS' : '' }}
                @php
                $dimention=(int)$assessment->length * (int)$assessment->breadth;
                @endphp
                {{$dimention}}

            </td>
            <td>NLe {{ number_format((int)$district->sq_meter_value/1000) }}</td>
            <td>{{ strtoupper($assessment->types->pluck('label')->implode(', ')) }}, ({{ $assessment->types->pluck('value')->sum() }})</td>
        </tr>
    </table>  --}}


{{--  <br>  --}}



    {{--  <table style="width:100%;" class="tables" >
        <tr>
            <th>WALL MATERIAL </th>
            <th>ROOF TYPE </th>
            <th>WINDOW TYPE</th>
            <th>VALUE ADDED</th>
        </tr>
        <tr>
            <td>NLe {{ strtoupper(number_format(optional(optional($assessment)->wallMaterial)->value/1000)) }} {{($assessment->wall_material_type)? '('.strtoupper($assessment->wall_material_type).')': ''}}</td>
            <td>NLe {{ strtoupper(number_format(optional(optional($assessment)->roofMaterial)->value/1000)) }} {{($assessment->roof_material_type)? '('.strtoupper($assessment->roof_material_type).')': ''}}</td>
            <td>NLe {{ strtoupper(number_format(optional(optional($assessment)->windowType)->value/1000)) }} {{($assessment->roof_material_type)? '('.strtoupper($assessment->roof_material_type).')': ''}}</td>
           
            <td>NLe {{ number_format( array_sum( explode( ',', $assessment->valuesAdded->pluck('value')->implode(', ') ) )/1000 ) }} {{ strtoupper(($assessment->value_added_type)? '('.strtoupper($assessment->value_added_type).')': '') }}</td>
        </tr>
    </table>  --}}



{{--  <br>  --}}



    {{--  <table style="width:100%;" class="tables" >
        <tr>
            <th>SWIMMING POOL </th>
            <th>ZONE </th>
            <th>PROPERTY USE</th>
            <th>GATED COMMUNITY</th>
        </tr>
        <tr>
            <td>{!! strtoupper(optional(optional($assessment)->swimming)->label ? optional(optional($assessment)->swimming)->label : 'NO' ) !!} {!! strtoupper(optional(optional($assessment)->swimming)->value ? optional(optional($assessment)->swimming)->value/1000: '' ) !!} </td>
            <td>{{ strtoupper(optional(App\Models\PropertyZones::find($assessment->zone))->label) }} ({{ strtoupper(optional(App\Models\PropertyZones::find($assessment->zone))->value) }})</td>
            <td>{{ strtoupper(optional(App\Models\PropertyUse::find($assessment->property_use))->label) }} ({{ strtoupper(optional(App\Models\PropertyUse::find($assessment->property_use))->value) }})</td>
            <td>{{ strtoupper(optional($assessment)->gated_community ? 'Yes' : 'No') }}</td>
        </tr>
    </table>  --}}




{{--  <br>  --}}


    {{--  <table style="width:100%;" class="tables" >
        <tr>
            <th> DILAPIDATED </th>
            <th>SANITATION</th>
            <th>ASSESSED VALUE</th>
        </tr>
        <tr>
            <td>{{ str_contains(strtoupper($assessment->categories->pluck('label')->implode(', ')), 'Dilapidated House') ? 'YES' : 'NO' }}</td>
            <td>{{ strtoupper(    optional(App\Models\PropertySanitationType::find($assessment->sanitation))->label == 'Not Applicable' ? 'NA' : optional(App\Models\PropertySanitationType::find($assessment->sanitation))->label  ) }} ({{ strtoupper(optional(App\Models\PropertySanitationType::find($assessment->sanitation))->value) }})</td>
            <td>NLe {!! number_format($assesment_value_new) !!}</td>
        </tr>
    </table>  --}}










{{--  <br>


    <table style="width:100%;" class="tables" >
        <tr>
            <th> TAXABLE PROPERTY VALUE (NLe) </th>

        </tr>
        <tr>
            <td><b>NLe {!! number_format($assesment_value_new) !!}</b></td>
        </tr>
    </table>





<br>  --}}

<h1>POLICY</h1>
    <table style="width:100%;" class="tables" >
        <tr>
            <th> COUNCIL CATEGORY </th>
            <th> MILL RATE </th>

        </tr>
        <tr>
            <td><b>{{ $assessment->group_name }}</b></td>
            <td><b>{!! App\Models\MillRate::where('group_name', '=', $assessment->group_name)->pluck('rate')->count() > 0 ? App\Models\MillRate::where('group_name', '=', $assessment->group_name)->pluck('rate')[0] : '' !!}</b></td>
        </tr>
    </table>







<br>

<h1>RATE PAYABL @if(@$yr) {{@$yr}} @else {{@$year}}  @endif</h1>
    <table style="width:100%;" class="tables" >
        <tr>
            <th> RATE PAYABL @if(@$yr) {{@$yr}} @else {{@$year}} @endif</th>

        </tr>
        <tr>
            <td><b>{{@$due}} </b></td>
        </tr>
    </table>







{{--  
<br>
<br>  --}}

{{--  <h1>COUNCIL DISCOUNT</h1>
    <table style="width:100%;" class="tables" >
        <tr>
            <th>DISABILITY (10%) </th>
            <th>PENSIONERS (10%) </th>

        </tr>
        <tr>
            <td>{!! strtoupper($assessment->disability_discount ? 'Yes' : 'No') !!}</td>
            <td>{!! strtoupper($assessment->pensioner_discount ? 'Yes' : 'No') !!}</td>
        </tr>
    </table>







<br>  --}}

{{--  <h1>DISCOUNTED RATE PAYABLE @if(@$yr) {{@$yr}} @else {{@$year}}  @endif</h1>
    <table style="width:100%;" class="tables" >
        <tr>
            <th>DISCOUNTED RATE PAYABLE @if(@$yr) {{@$yr}} @else {{@$year}}  @endif</th>

        </tr>
        <tr>
            <td>{{@$due}}</td>
        </tr>
    </table>





<br>  --}}

{{--  <h1>DISCOUNTED RATE PAYABLE @if(@$yr) {{@$yr}} @else {{@$year}}  @endif</h1>
    <table style="width:100%;" class="tables" >
        <tr>
            <th> Amount With Counsil Adjustment %</th>
            <th> Amount Without Counsil Adjustment %</th>

        </tr>
        <tr>
            <td>
                @if(@$assesment_value_new!=0)
                 {{@$assesment_value_new}}
                 @else
                 {{@$due}}
                 @endif
           </td>
             <td>
                 @php
                 $percentage=$property->assessment->total_adjustment_percent;
                 if(@$assesment_value_new!=0){
                   $amount=round($assesment_value_new) *100;
                 }else{
                      $amount=round(@$due)*100;
                 }

                 
                 $withoutAmount=$amount/(100+($percentage));
                  // dd($due,$amount,$percentage,$withoutAmount,100+($percentage));
                 $amnt=(int)$withoutAmount;

                 @endphp
                 {{$amnt}}
             </td>
        </tr>
    </table>





 <table class="table table-bordered" style="width:100%; margin-top:20px;" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr>
                        
                        <th style="border:1px solid #ccc; text-align: center;background-color:#ccc; color: #000;font-size: 12px;" scope="col">RATE PAYABLE = (TAXABLE PROPERTY VALUE * MILL RATE)/1000</th>
                        
                    </tr>
                    
                </tbody>
</table>  --}}






























        {{--<div style="width: 100%;justify-content: center;">
            <div style="width: 100%;">
                <p>
                    <strong style="color: #888888; font-size: 10px;">PRIVACY POLICY - PERSONAL INFORMATION:  </strong>
                </p>
                
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify; margin-top:-5px;">
                    <strong>1. </strong>
                    Types of information collected: We may collect and hold personal information
                    about you including information that can identify you, and is relevant to obligatory
                    service(s) provided by the council e.g. Municipal Property Rate Collection.
                    This personal information may include details such as your name, gender, address,
                    contact information, etc.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>2. </strong>
                    Personal information collected directly from you or tenants through our enumerators. We may also collect personal information about you from third parties either residing or whom we met at your property/premises at the time of assessment or delivery.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>3. </strong>
                    Purpose of collection: We collect, use and hold your personal information for the purposes of * Administration for the Property Rate Assessment, Calculation, Bill generation, and Demand Note Distribution. * Providing you with information and other communications. * Our internal business operations, including the fulfilment of any legal requirements; and analyzing our services and customer needs with a view to improving those services
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>4. </strong>
                    Failure to provide accurate and complete information: If the personal information you provide to us is incomplete or inaccurate, we will record the provided details as given on any formal document or communication relating to you.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>5. </strong>
                    Correction of personal information: If upon receiving your Demand Note and you believe the ownership and or property information we hold for that property is incorrect, incomplete or out of date, please advise us. We will take reasonable steps to correct the information so that it is accurate, complete and up to date.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>6. </strong>
                    How do we use and disclose Personal Information: Generally, we only use or disclose personal information about you as set out above. In some circumstances, the law may permit or require us to use or disclose personal information for other purposes (for instance, where you would reasonably expect us to).
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>7. </strong>
                    Security of your information: We store your personal information in electronic format. We take reasonable steps to ensure the security of all information we collect from risks, such as loss or unauthorized access, destruction, use, modification or disclosure of data. Authorized personnel only maintain your personal information in an accessible and secure environment.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>8. </strong>
                    Changes to this Privacy Policy This privacy policy may change from time to time particularly as new rules, regulations and industry requirements are introduced.
                </p>
                <p align="justify" style="color: #888888; font-size: 13px !important; align: justify;">
                    <strong>9. </strong>
                    Complaints and feedback if you wish to make a complaint about a breach of your privacy that applies to us, please contact us as set out below and we will take reasonable steps to investigate the complaint and respond to you.
                </p>
                <p>
                    If you have any queries or concerns about this privacy policy or the way we handle your personal information, please contact us at <a style="background-color: #FFF;">{{  $district->enquiries_email }}</a>
                </p>
                <ul style="list-style-type: square; margin-left: 1%; font-size: 10px; align: justify;">
                    <li><strong>DISCLAIMER:</strong> This methodology and estimate used by council is for Municipal Property Rate calculation only, and might not be representative of the value as would be calculated for other purposes by a professional individual or entity with quantity surveyor and or property valuation expertise.</li>
                    <li>Taxable Property Value is an algorithm-generated value estimated as a percentage of Actual Property Value (Minimum value inclusive of land acquisition value).</li>
                    <li>Property becomes liable for assessment and property rate payment when it has its (a) entrance doors, (b) roof and (c) windows affixed; irrespective of whether it is occupied or not.</li>
                </ul>
            </div>
        </div> --}}
        
    </div>
</div>
</div>