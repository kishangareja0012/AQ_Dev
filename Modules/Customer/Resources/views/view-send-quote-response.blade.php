@extends('customer::layouts.master')
@section('title', 'View Vendor Quote Response')
@section('content')
<div class="card-content-div">
    <ul class="breadcrumb">
      <li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>
      <!--<li><a href="{{url('customer/quote-requests')}}">Vendors for {{ ucfirst($vendorquote->item) }}</a></li>-->
      <li><a href="{{url('customer/quote-sent-vendors',$vendorquote->quote_id)}}">Vendors for {{ ucfirst($vendorquote->item) }}</a></li>
      <li>Quote from {{ ucfirst($vendorquote->name) }}</li>
      <a href="{{ url('customer/quote-sent-vendors/'.$vendorquote->id)}}" style="float: right;">Back</a>
  </ul>             
  <div class=" card-table-list">
    <ul>
        <li class="card-row-3">
            <div>
                <h5 class="title-card-all">{{ ucfirst($vendorquote->name) }}</h5>
                <p>{{ $vendorquote->website }}</p>
            </div>
            <div>
                <p>{{ $vendorquote->company_address }}</p>
            </div>
            <div></div>
            <div>
                <?php if($vendorquote->is_privacy){ ?>
                    <h3> <span class="phone-icon "></span> {{$vendorquote->mobile}}</h3>
                <?php } ?>
            </div>
            <!-- <div>
                <p>Google Map Location</p>
                <a href="#" target="_blank">https://goo.gl/maps/M53DBLnkKA3Yk9vE6</a>
            </div> -->
        </li>            

    </ul>
</div>

<div class="product-details-full">                         
    <div class="img-box-full">
        <?php 
        if(!empty($vendorquote->photo)){$path = asset('public/assets/images/quote-responses/'.$vendorquote->photo);}
        else{$path = asset('public/assets/images/default.jpeg');}
        ?>
        <img src="{{$path}}" style="width: 150px; height: 150px; margin-left: 25%; margin-top: 7%; " alt="{{$vendorquote->item}}" class="img-responsive">
    </div>
    <div class="product-details-viwe">
        <h5 class="title-card-all"><a href="#"> {{ $vendorquote->item }}</a></h5>
        <p class="product-details">{{ $vendorquote->item_description }}</p>
        <p class="offer-box">
<?php if ($vendorquote->discount!=''): ?>
          <b>Offer :</b><span>{{ $vendorquote->discount }}</span>
<?php endif ?>
<?php if ($vendorquote->expiry_date!=''): ?>
          Expires on : {{ isset($vendorquote->expiry_date) ? date('d, M Y',strtotime($vendorquote->expiry_date)) : '--' }}
<?php endif ?>

        </p>
        <?php if(!empty($vendorquote->document)){  
          $docpath = asset('public/assets/documents/quote-responses/'.$vendorquote->document); } 
          else $docpath = '';
          if(!empty($vendorquote->additional_details)){  ?>
             <span class="offer-box">{{$vendorquote->additional_details}}</span><br />
         <?php }?>
         <?php if(!empty($vendorquote->document)){  
          $docpath = asset('public/assets/documents/quote-responses/'.$vendorquote->document); } 
          else $docpath = '';
          if(!empty($vendorquote->document)){  ?>
             <span class="offer-box"><a href="{{$docpath}}" target="_blank">{{$vendorquote->document}}</a></span><br />
         <?php }?>
         <button class="price-button">₹{{ $vendorquote->price }}</button>
     </div>
 </div>
 <?php if($vendor_quote_products_count > 0){ ?>                         
    <div class="col-md-121">
        <h3 style="padding-left:15px">Similar Products</h3>            
    </div> 
    <?php foreach($vendorquote_products as $vendorquote_product){?>

        <div class="product-details-list product-details-full">                         
            <div class="img-box-full text-center">
                <?php 
                if(!empty($vendorquote_product->product_file)){$path = asset('public/assets/images/quote-responses/'.$vendorquote_product->product_file);}
                else{$path = asset('public/assets/images/default.jpeg');}
                ?>    
                <img src="{{$path}}" height="150" width="150" alt="{{$vendorquote_product->product_name}}">                              
            </div>
            <div class="product-details-viwe">
                <h5 class="title-card-all">{{$vendorquote_product->product_name}}</h5>
                <p class="product-details">{{$vendorquote_product->product_description}}</p>
                <p class="offer-box">
<?php if ($vendorquote_product->product_discount!=''): ?>
                  <b>Offer :</b><span>{{$vendorquote_product->product_discount}}</span>
<?php endif ?>
<?php if ($vendorquote_product->product_expirydate!=''): ?>
                  Expires on : {{  isset($vendorquote_product->product_expirydate) ? date('d, M Y',strtotime($vendorquote_product->product_expirydate)) : '--' }}
<?php endif ?>
                </p>
                <button class="price-button">₹{{$vendorquote_product->product_price}}</button>
            </div>
        </div>

    <?php } ?>
<?php } ?>



</div>

@endsection