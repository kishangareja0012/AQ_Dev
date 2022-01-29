@extends('vendor::layouts.master')
@section('title', 'Send Quote Response')
@section('content')
<style>
    button.btn.btn-quotes.pull-right.add-more,button.btn.btn-quotes {background: #2c2e39; padding: 6px 49px 4px 30px; font-size: 15px; color: #FFF; border-radius: 50px; text-decoration: none; margin: 0px auto; }
    button.btn.btn-quotes.pull-right.add-more.responsive-button{display: none;}
    @media (max-width: 576px) and (min-width: 0px){
        /* button#addSimilar {margin-bottom: 10px; } */
        button.btn.btn-danger.remove.pull-right {margin-left: 87px !important; }
        button.btn.btn-quotes.pull-right.add-more.responsive-button{display: block;float: left !important;}
        button.btn.btn-quotes.pull-right.add-more.wide-button {display: none; }
        span.add_more_send_quote_buttons.pull-right {float: right !important; margin-right: 6px !important; }
        .vendor-requests .pull-right{margin: 0px;}
    }
    @media (max-width: 400px) and (min-width: 0px){
        button#addSimilar {margin-bottom: 10px; }
    }

</style>
<div class="card-content-div vendor-requests">
    <ul class="breadcrumb">
      <li><a href="{{url('vendor/quote-requests')}}">Quote Requests</a></li>
      <li><a href="{{url('vendor/quote-requests')}}">{{ ucfirst($quote->item) }}</a></li>
      <?php if($quote->isResponded){ ?>
        <li>View Quote Response</li>
    <?php } else{ ?>
        <li>Send Quote Response</li>
    <?php } ?>    
</ul>
<form action="{{ url('vendor/send-quote',$quote_id) }}" method="post" enctype="multipart/form-data">
    @csrf
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
                                    <div class="col-md-6">
                                        <div class="upload-images doc-file">
                                            <button class="btn"> <span>Upload document</span>
                                                <img src="{{asset('public/assets-new/images/cloud-upload-signal.svg')}}" alt="" width="20px">
                                            </button>
                                            <input type="file" name="mydocument" id="fileUpload">
                                      </div>
                                      <div id="filePreview">
                                                <span id="file_loaded" ></span>
                                                <span class="removefile">X</span> 
                                            </div>
                                  </div>
                                  <div class="col-md-6">
                                    <div class="upload-images image-section">
                                        <button class="btn"> <span>Upload Image</span>
                                            <img src="{{asset('public/assets-new/images/cloud-upload-signal.svg')}}" alt="" width="20px">
                                        </button>
                                        <input type="file" name="myfile" id="imageUpload" accept=".png, .jpg, .jpeg">
                                  </div>
                                  <div id="imagePreview" style="background-image: url(http://i.pravatar.cc/500?img=7);">
                                          <span class="remove">X</span>
                                      </div>
                              </div>

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
                        <input type="text" name="price" class="form-control" required value="{{ $quote->price }}">
                    </div>
                    <div class="form-group">
                        <label for="pwd">Discount :</label>
                        <input type="text" name="discount" class="form-control" value="{{ $quote->discount }}">
                    </div>
                    <div class="form-group">
                        <label for="pwd">Price valid till:</label>
                        <input style="padding: 6px 9px 3px 11px;" type="date" name="expiry_date" class="form-control" value="{{ $quote->expiry_date }}">
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
<div class="">
    <div class="btn-quotes text-right">
        <button type="button" class="btn" id="addSimilar" style="padding: 6px 16px 4px 17px;"><i class="glyphicon glyphicon-plus"></i> Add Similar Product</button>
        <span class="send_quote_buttons"> 
            <?php if($quote->isResponded){ ?>    
                <button class="btn" type="submit" style="padding: 6px 15px 4px 15px;">Send Quote &nbsp;&nbsp;&nbsp; <i class="glyphicon glyphicon-arrow-right" style="top: 3px ;" aria-hidden="true"></i> </button>
            <?php }else{ ?>
                <button type="submit" class="btn" style="padding: 6px 15px 4px 15px;">Send Quote &nbsp;&nbsp;&nbsp; <i class="glyphicon glyphicon-arrow-right" style="top: 3px ;" aria-hidden="true"></i> </button>      
            <?php } ?>    
        </span>
    </div>
</div>
<hr />

<div class="row" id="similarProduct" style="display:none;">
    <?php if(!$quote->isResponded){?>
        <div class="control-group">
        <div class="col-md-12">
            <h3>Similar Products</h3>            
        </div> 
        <div class="row">
        <div class="col-md-3 col-lg-3 col-sm-4 col-xs-12">
            <div class="vendor-uplod uplod-card">
                <div class="upload-images">
                    <button class="btn"> <span>Upload Image</span>
                        <img src="images/cloud-upload-signal.svg" alt="" width="20px">
                    </button>
                    <input type="file" name="myfile1[]" onchange="imagePreview(this)" class="similar_product_image" accept=".png, .jpg, .jpeg">
                    <!-- <div class="image_preview"></div> -->
                    <img class="image_preview" height="100" width="100" src="#" alt="your image" />

                </div>
            </div>
        </div>
        <div class="col-md-9 col-lg-9 col-sm-8 col-xs-12">
            <div>
                <div class="vendor-uplod">
                    <h4> Provide your price </h4>
                     <button class="btn btn-danger remove pull-right" type="button" style="margin-right: 18px; margin-bottom: 10px; "><i class="glyphicon glyphicon-remove"></i> </button>
                </div>
                <div class="vendor-form">
                    <input type="hidden" name="product_id[]">
                    <div class="form-group">
                        <input type="text" class="form-control" name="product_name[]" placeholder="Product Name">
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="product_description[]" placeholder="Product Decrisption">
                    </div>
                    <div class="form-inline">
                        <div class="form-group">
                            <label for="price:">Enter price:</label>
                            <input type="text"  name="product_price[]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="pwd">Discount :</label>
                            <input type="text"  name="product_discount[]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="Price valid">Price valid till:</label>
                            <input type="date" style="padding: 6px 9px 3px 11px;" name="product_expirydate[]" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
     </div>
     </div>
        <div class="copy hidden">
            <div class="control-group">
                <div class="row">
                <div class="col-md-3 col-lg-3 col-sm-4 col-xs-12">
                    <div class="vendor-uplod uplod-card">
                        <div class="upload-images">
                            <button class="btn"> <span>Upload Image</span>
                                <img src="images/cloud-upload-signal.svg" alt="" width="20px">
                            </button>
                            <input type="file" name="myfile1[]" onchange="imagePreview(this)"  accept=".png, .jpg, .jpeg">
                            <!-- <div class="image_preview"></div> -->
                            <img class="image_preview" height="100" width="100" src="#" alt="your image" />

                        </div>
                    </div>
                </div>
                <div class="col-md-9 col-lg-9 col-sm-8 col-xs-12">
                    <div>
                        <div class="vendor-uplod">
                            <h4> Provide your price </h4>
                             <button class="btn btn-danger remove pull-right" type="button" style="margin-right: 18px; margin-bottom: 10px; "><i class="glyphicon glyphicon-remove"></i></button>
                        </div>
                        <div class="vendor-form">
                            <input type="hidden" name="product_id[]">
                            <div class="form-group">
                                <input type="text" class="form-control" name="product_name[]" placeholder="Product Name">
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="product_description[]" placeholder="Product Decrisption">
                            </div>
                            <div class="form-inline">
                                <div class="form-group">
                                    <label for="price:">Enter price:</label>
                                    <input type="text"  name="product_price[]" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="pwd">Discount :</label>
                                    <input type="text"  name="product_discount[]" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="Price valid">Price valid till:</label>
                                    <input type="date" style="padding: 6px 9px 3px 11px;"  name="product_expirydate[]" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
               
                </div>
            </div>
        </div>

        <div class="after-add-more"></div>
            <button class="btn btn-quotes pull-right add-more responsive-button" style="margin-right: 5px; margin-bottom: 10px; padding: 6px 16px 4px 17px;" type="button"><i class="glyphicon glyphicon-plus"></i> Add Similar Product</button>
            <span class="add_more_send_quote_buttons pull-right"> 
            <?php if($quote->isResponded){ ?>    
                <button class="btn btn-quotes" type="submit" style="padding: 6px 15px 4px 15px;">Send Quote &nbsp;&nbsp;&nbsp; <i class="glyphicon glyphicon-arrow-right" style="top: 3px;" aria-hidden="true"></i> </button>
            <?php }else{ ?>
                <button type="submit" class="btn btn-quotes" style="padding: 6px 15px 4px 15px;">Send Quote &nbsp;&nbsp;&nbsp; <i class="glyphicon glyphicon-arrow-right" style="top: 3px;" aria-hidden="true"></i> </button>      
            <?php } ?>    
        </span>
           <button class="btn btn-quotes pull-right add-more wide-button" style="margin-right: 5px; margin-bottom: 10px; padding: 6px 16px 4px 17px; " type="button"><i class="glyphicon glyphicon-plus"></i> Add Similar Product</button>
        
    <?php }else{ ?>
        <?php if($vendor_quote_products_count > 0){ ?>
            <div class="col-md-12">
                <h3>Similar Products</h3>            
            </div> 
            <?php foreach($vendorquote_products as $vendorquote_product)?>

            <div class="col-md-3 col-lg-3 col-sm-4 col-xs-12">
                <div class="vendor-uplod uplod-card">
                    <div class="upload-images">
                        <button class="btn"> <span>Upload Image</span>
                            <img src="images/cloud-upload-signal.svg" alt="" width="20px">
                        </button>
                        <input type="file" name="myfile2" id="imageUpload" accept=".png, .jpg, .jpeg">
                        <img src="" alt="">

                    </div>
                </div>
            </div>
            <div class="col-md-9 col-lg-9 col-sm-8 col-xs-12">
                <div>
                    <div class="vendor-uplod">
                        <h4> Provide your price </h4>
                    </div>
                    <div class="vendor-form">
                        <input type="hidden" name="product_id[]" value="{{$vendorquote_product->id}}">
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
        <?php } } ?>
    </div> 
</div>
</form>
</div>	
@endsection