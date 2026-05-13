<div class="receipt">
    <div class="container">
        <div class="receipt-content">
            <div class="row">
                <div class="col-lg-12">

                    <table style="height: 395px;" class="" width="100%" border="0">
                        <tr style="">
                            <td align="left" style="width: 18%; height: 5px;"></td>
                            <td style="width: 64%; text-align:center;" valign="">&nbsp;</td>
                            <td align="left" style="width: 18%;"></td>
                        </tr>                         
                        <tr>                           
                            <td align="left" style="width: 18%; height: 125px;" valign="top">
                                @if($district->primary_logo_envp)
                                <img style="padding: 0 15px;" {{-- src="https://wardc.srvtechnology.com/storage/app/public/{{ $district->primary_logo_envp }}" --}} src="https://www.wardc.online/apis/storage/app/public/property/env2.png" alt="">
                                @else
                                <img style="padding: 0 15px;" src="https://www.wardc.online/apis/storage/app/public/property/council_logo.jpg" alt="">

                                @endif
                            </td>

                            <td style="width: 64%; text-align:center;" valign="top">
                                <table border="0">
                                    <tr>
                                        <td valign="top">
                                            <h1 class="text-center" style="margin: 0px 0px 1px 0px; font-weight:600; font-size: 18px;text-transform: uppercase;"><span style="">{{$district->council_name_envp}} - {{$district->council_short_name_envp}}</span></h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h1 class="text-center" style="margin-top: 0px; font-weight:600; font-size: 13px; line-height: 16px;">
                                                <span style="">{{$district->council_address_envp}}  </span>
                                            @if($district->council_address_envp2!='')
                                            <br/>
                                            <span style="">{{$district->council_address_envp2}}  </span>                                       
                                            @endif  

                                            @if($district->council_address_envp3!='')
                                            <br/>
                                            <span style="">{{$district->council_address_envp3}}  </span>                                             
                                            @endif

                                            @if($district->council_address_envp4!='')
                                            <br/>
                                            <span style="">{{$district->council_address_envp4}}  </span>                                             
                                            @endif

                                            @if($district->council_address_envp5!='')
                                            <br/>
                                            <span style="">{{$district->council_address_envp5}}  </span>                                             
                                            @endif                                                                                           
                                            </h1>
                                                                                                                                   
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h1 class="text-center" style="margin-bottom: 0px; font-size: 15px;font-weight:600;text-decoration: underline;">
                                                <span style="">PROPERTY RATE DEMAND NOTE - {{$year}}</span>
                                            </h1>
                                        </td>
                                    </tr>                                                                        
                                </table>
                            </td>
                            <td align="right" style="width: 18%;" valign="top">
                               @if($district->secondary_logo_envp)
                                <img style="padding: 0 15px;" {{-- src="https://wardc.srvtechnology.com/storage/app/public/{{ $district->secondary_logo_envp }}" --}} src="https://www.wardc.online/apis/storage/app/public/property/wdc.png" alt="">
                                @else
                                <img style="padding: 0 15px;" src="https://www.wardc.online/apis/storage/app/public/property/council_logo.jpg" alt="">

                                @endif
                            </td>
                        </tr>
<!-- 1 -->                        
                        <tr style="">
                            <td align="left" style="width: 18%; height: 200px;"></td>
                            <td style="width: 64%; text-align:center;" valign="top">
                                <table border="0" style="width: 90%;margin: 0 auto; font-size: 12px;" cellpadding="4">
                                    <tr>
                                        <th valign="top" style="width: 42%">{{ $property->is_organization ? 'ORGANIZATION' : 'PROPERTY OWNER' }} NAME</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->is_organization ? $property->organization_name : (optional($property->landlord)->first_name . ' ' . optional($property->landlord)->middle_name . ' ' . optional($property->landlord)->surname) }}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">PROPERTY ID</th>
                                        <th style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->getPrintableId() }}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">PROPERTY ADDRESS</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->street_number }}  {{ $property->street_name }}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">WARD</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->ward }}</th>
                                    </tr>                                    
                                    <tr>
                                        <th valign="top" style="width: 42%">CONSTITUENCY</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->constituency }}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">SECTION</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->section }}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">DISTRICT</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{$district->name}}</th>
                                    </tr>
                                    <tr>
                                        <th valign="top" style="width: 42%">PROVINCE</th>
                                        <th valign="top" style="width: 4%; text-align: center;">-</th>
                                        <th valign="top" style="width: 54%">{{ $property->province }}</th>
                                    </tr>
                                </table>
                            </td>
                            <td align="right" style="width: 18%;"></td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px; font-weight: 700;text-transform: uppercase; height: 25px;" colspan="3" valign="bottom">{{$district->council_name_envp}} </span>demands payment of municipal rates in respect of the period commencing 1st January to 31st December {{$year}}</td>
                        </tr>
                    </table>
                    
                </div>
            </div>
            <div class="clearfix"></div>
<!--             <div class="row bottom">
            <div class="col-lg-12">
                        <table class="table table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th scope="col" colspan="7" style="font-size: 12px;"><span>{{$district->council_name_envp}} </span>demands payment of municipal rate in respect of the period commencing 1st January to 31st December {{$year}}</th>
                                </tr>
                            </thead>
                        </table>
                        
                    </div>
                </div> -->
                
        </div>
    </div>
</div>
