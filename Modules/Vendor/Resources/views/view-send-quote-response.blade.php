@extends('vendor::layouts.master')
@section('title', 'View Quote Response')
@section('content')

<div class="card-content-div vendor-requests">
    <ul class="breadcrumb">
      <li><a href="{{url('vendor/quote-requests')}}">Quote Requests</a></li>
      <li><a href="{{url('vendor/quote-requests')}}">{{ ucfirst($quote->item) }}</a></li>
      <li>View Quote Response</li>
  </ul>
  <div class="vendor-list grid-card">
    <div class="row">
        <div class=" col-xs-12">
            <h3>Price requested for</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-lg-3 col-sm-4 col-xs-12">
            <div class="vendor-requests-img">
                <div class="vendor-img">
                   <?php 
                   if(!empty($quote->item_sample)){$path = asset('public/assets/images/quotes/'.$quote->item_sample);}
                   else{$path = asset('public/assets/images/default.jpeg');}
                   ?>
                   <img class="img-responsive" src="{{ $path }}" alt="{{ $quote->item }}" title="{{ $quote->item }}" />
               </div>
           </div>
       </div>
       <div class="col-md-9 col-lg-9 col-sm-8 col-xs-12">
        <h6>{{ ucfirst($quote->location) }} | {{ date('d M Y h:i a',strtotime($quote->created_at)) }}</h6>
        <h5 class="title-card-all"> {{ $quote->item }} </h5>
        <p>{{ $quote->item_description }}</p>
        <hr />
        <div>                               
            <div class="vendor-uplod">
                <div class="row">
                    <div class="col-md-4 col-sx-6 col-sm-12">
                        <h4> Provide your price </h4>
                    </div> 
                    <div class="col-md-8 col-sx-6 col-sm-6">
                        <div  class="text-right">
                          <?php if(!empty($quote->document)){ ?>
                            <div class="upload-images">
                              <a href="{{ url('vendor/download',$quote->document) }}" title="{{$quote->item}}" >{{$quote->document}}</a>
                          </div>
                      <?php } ?>
                      <?php if(!empty($quote->photo)){ ?>
                        <div class="upload-images">
                            <img src="{{ asset('public/assets/images/quote-responses/'.$quote->photo) }}" width="50" alt="{{$quote->item}}" >
                        </div>
                    <?php } ?> 
                </div>
            </div>
        </div>
    </div>
    <div class="vendor-form">
        <input type="hidden" name="quote_id" value="{{$quote->id}}">
        <input type="hidden" name="vendor_id" value="{{$quote->vendor_id}}">
        <input type="hidden" name="user_id" value="{{$quote->user_id}}">
        <div class="form-inline">
            <div class="form-group">
                <label for="price:">Enter price:</label>
                <input type="number" name="price" class="form-control" required value="{{ $quote->price }}">
            </div>
            <div class="form-group">
                <label for="pwd">Discount :</label>
                <input type="text" name="discount" class="form-control" required value="{{ $quote->discount }}">
            </div>
            <div class="form-group">
                <label for="pwd">Price valid till:</label>
                <input type="date" name="expiry_date" class="form-control" required value="{{ $quote->expiry_date }}">
            </div>
        </div>

        <div class="form-group">
            <label for="pwd">Additonal Details</label>
            <textarea class="form-control" name="additional_details"  placeholder="Add more details about your product" rows="3">{{ $quote->additional_details }}</textarea>
        </div>

    </div>
</div>
</div>
</div>
<hr />

<div class="row">  
 <?php if($vendor_quote_products_count > 0){?>
    <div class="col-md-12">
        <h3>Similar Products</h3>            
    </div>
    <?php foreach($vendorquote_products as $vendorquote_product){?>
        <div class="row">
            <div class="col-md-3 col-lg-3 col-sm-4 col-xs-12">
                <div class="vendor-uplod uplod-card">
                    <?php 
                    if(!empty($vendorquote_product->product_file)){$path = asset('public/assets/images/quote-responses/'.$vendorquote_product->product_file);}
                    else{$path = asset('public/assets/images/default.jpeg');}
                    ?>
                    <div class="upload-images">
                        <img src="{{ $path }}" width="150" height="150" alt="{{$vendorquote_product->product_name}}" title="{{$vendorquote_product->product_name}}" >
                   </div>
               </div>
           </div>
           <div class="col-md-9 col-lg-9 col-sm-8 col-xs-12">
            <div>
                <div class="vendor-uplod">
                    <h4> Provide your price </h4>
                </div>
                <div class="vendor-form">
                    <div class="form-group">
                        <input type="text" class="form-control" name="product_name[]" value="{{$vendorquote_product->product_name}}" placeholder="Product Name">
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="product_description[]" value="{{$vendorquote_product->product_description}}" placeholder="Product Decrisption">
                    </div>
                    <div class="form-inline">
                        <div class="form-group">
                            <label for="price:">Enter price:</label>
                            <input type="text"  name="product_price[]" value="{{$vendorquote_product->product_price}}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="pwd">Discount :</label>
                            <input type="text"  name="product_discount[]" value="{{$vendorquote_product->product_discount}}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="Price valid">Price valid till:</label>
                            <input type="date"  name="product_expirydate[]" value="{{$vendorquote_product->product_expirydate}}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>     
    </div>
<?php }  } ?>               
</div>
</div>
</div>
@endsection
