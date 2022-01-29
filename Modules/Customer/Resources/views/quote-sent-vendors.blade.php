@extends('customer::layouts.master')
@section('title', 'Request sent to Vendors for '.$quotedata->item )
@section('content')
<style>
.pagination>.active>span{background-color: #e57b23;border-color: #e57b23;}
.pagination>li>a, .pagination>li>span{color: #e57a23;}
ul.pagination {float: right;margin-bottom: 100px;}
</style>
<script type="text/javascript">
	function sortbyquotes(val){
		var current_url = "{{url()->current()}}";
		if(val == ''){location.href = current_url;}
		location.href = current_url+"?sortby="+val;
	}
</script>
<style>
	p.vendor_not_found_tag{border-radius: 25px;border: 2px solid #c9372c;padding: 45px;width: 100%;height: 200px;text-align: center;font-size: 24px;line-height: 1.5em;font-weight: bold;color: #e67e22;margin-top:10px; }
	.form-group select {padding: 3px 12px; !important}
	@media (max-width: 576px) and (min-width: 0px){
		p.vendor_not_found_tag {height: 346px!important; }
		.from-search-short {grid-template-columns: none; }
		select.border-rs-50.form-control{    padding: 3px 12px;}
	}
</style>
<div class="card-content-div">
	<ul class="breadcrumb">
		<li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>
		<!--<li><a href="{{url('customer/quote-requests')}}">{{$quotedata->item}}</a></li>-->
		<li> Vendors for {{$quotedata->item}}</li>
		<a href="{{ url('customer/quote-requests')}}" style="float: right;">Back</a>
	</ul>
	<div class="grid-list-2">
		<?php 
		if(!empty($quotedata->item_sample)){$path = asset('public/assets/images/quotes/'.$quotedata->item_sample);}
		else{$path = asset('public/assets/images/default.jpeg');}
		?>
		<div><img src="{{$path}}" height="75" alt="{{$quotedata->item}}" title="{{$quotedata->item}}"></div>
		<div>
			<h5 class="title-card-all">{{$quotedata->item}}</h5>
			<p>{{$quotedata->item_description}}</p>
		</div>		
		@if ($vendors_count_sent>0)
		<div class="numberfo-viwe-vendor" style="height:70px;">
			<p><b>Request sent to <?php if($vendors_count_sent > 200){ echo "more than 200"; }else{ echo $vendors_count_sent; } ?> Vendors</b></p>
			@if($vendors_count_responded>0)
			<p><b>Response received from {{$vendors_count_responded}} Vendors</b></p>
			@else
			<div><b>Waiting for price from vendors</b></div>
			@endif
		</div>
		@endif
	</div>
	<input type="hidden" id="quote_id" value="{{$quote_id}}">
	@if ($vendors_count_sent>0)
	<!-- <div class="row hidden-sm hidden-xs"> -->
	<!-- <div class="row visible-sm visible-xs"> -->
	<div class="row">
		<div class="from-search-short ">
			<div class="form-group">
				<?php 
				$sortby = ''; 
				if(isset($_GET['sortby']) && !empty($_GET['sortby'])){
					$sortby = trim($_GET['sortby']);
				}
				?>
				<select class="border-rs-50 form-control" onchange="sortbyquotes(this.value)">
					<option value="" <?php if($sortby == ''){echo "selected";} ?>>All</option>
					<option value="pricelow2high" <?php if($sortby == 'pricelow2high'){echo "selected";} ?>>Price -- Low to High</option>
					<option value="pricehigh2low" <?php if($sortby == 'pricehigh2low'){echo "selected";} ?>>Price -- High to Low</option>
					<option value="newfirst" <?php if($sortby == 'newfirst'){echo "selected";} ?>> Newest First</option>
					<option value="discounthigh"<?php if($sortby == 'discounthigh'){echo "selected";} ?>>Discount</option>
					<option value="registered"<?php if($sortby == 'registered'){echo "selected";} ?>>Register vendors</option>
				</select>
			</div>
			<div class="form-group">
				<form name="vendorquotes-by-location">
					<div class="input-group search-location">
						<input type="text" name="location" class="form-control"  value="<?php if(isset($_GET['location'])){echo $_GET['location']; } ?>" placeholder="Select by location ">
						<div class="input-group-btn">
							<button class="btn btn-default" type="submit">
								<i class="glyphicon glyphicon-map-marker"></i>
							</button>
						</div>
					</div>
				</form>
			</div>
			<div class="form-group">
				<form name="vendorquotes-by-vendor">
					<div class="input-group search-location">
						<input type="text" name="vendor"  value="<?php if(isset($_GET['vendor'])){echo $_GET['vendor']; } ?>" class="form-control" placeholder="Search by vendor">
						<div class="input-group-btn">
							<button class="btn btn-default" type="submit">
								<i class="glyphicon glyphicon-search"></i>
							</button>
						</div>
					</div>
				</form>
			</div>
			
		</div>
	</div>

	<div class="card-table-list ">
		<div class="col-md-12 hidden-xs">
			<div class="grid-list-titls-2">
				<p>Vendor Name</p>
				<p>Vendor Details</p>
				<p>Contact Details</p>
				<p>Price</p>
			</div>
		</div>
		
		<ul id="quote_content">
			<?php if(count($quoteresponses) > 0){ ?>	
				@foreach ($quoteresponses as $request)
				<li class="card-row-3 clickable-row" >
					<div>
						<?php if($request->isResponded){?> 
							<h5 class="title-card-all"><a href="{{url('customer/view-sent-quote',$request->vendor_quote_id)}}">{{ $request->name }}</a></h5>
						<?php }else{ ?>
							<h5 class="title-card-all">{{ $request->name }}</h5>
						<?php } ?>	
						<p>{{ $request->website }}</p>
					</div>
					<div>
						@if($request->isResponded)
							<p>{{ str_replace(',', ', ' , $request->company_email) }}</p>
							<p>{{ str_replace(',', ', ' , $request->company_address) }}</p>
						@else
							@if($address_visible=='registered')
								@if ($request->register_by_self)
									<p>{{ str_replace(',', ', ' , $request->company_address) }}</p>
								@else
									<p></p>
								@endif
							@endif
							@if($address_visible=='non_registered')
								@if ($request->register_by_self)
									<p></p>
								@else
									<p>{{ str_replace(',', ', ' , $request->company_address) }}</p>
								@endif							
							@endif
							@if($address_visible=='all')
								<p>{{ str_replace(',', ', ' , $request->company_address) }}</p>
							@endif
						@endif
					</div>
					<div>
						@if($request->isResponded)
						<h3>
						<span class="phone-icon "></span> {{$request->company_phone}}
						</h3>
						@else
							@if($phone_number_visible=='registered')
							<h3>  
							<?php if(!empty($request->company_phone)){
									if (is_numeric($request->company_phone)) {
										if ($request->register_by_self) {
											echo '<span class="phone-icon "></span>'.$request->company_phone;
										}else{
											echo '<span class="phone-icon "></span> 91XXXXXXXXXX';
										}
									}else{
										echo '';
									} 
								} ?>
							</h3>
							@endif
							@if($phone_number_visible=='non_registered')
							<h3>  
								<?php if(!empty($request->company_phone)){
									if (is_numeric($request->company_phone)) {
										if ($request->register_by_self) {
											echo '<span class="phone-icon "></span> 91XXXXXXXXXX';
										}else{
											echo '<span class="phone-icon "></span>'.$request->company_phone;
										}									
									}else{
										echo '';
									} 
								} ?>	
							</h3>
							@endif
							@if($phone_number_visible=='all')
							<h3>  
								<?php if(!empty($request->company_phone)){?>
									<?php if (is_numeric($request->company_phone)) {
										echo '<span class="phone-icon "></span>'.$request->company_phone;
									}else{
										echo '';
									} ?>
								<?php } ?>	
							</h3>
							@endif
						@endif
					</div>

					<div>
						<?php if($request->isResponded){?>
							<a class="price-button" href="{{url('customer/view-sent-quote',$request->vendor_quote_id)}}"><?php echo "&#8377; ".$request->price; ?></a>&nbsp;&nbsp;&nbsp;&nbsp;
							<a href="{{url('customer/view-sent-quote',$request->vendor_quote_id)}}" class='price-button view-more-button' style="padding: 3px 20px 0px 20px;"><i class="fa fa-eye"></i> View more</a>
						<?php }else{ ?>
							<div><b>Waiting for price</b></div>
							<!-- <div><strong> Request for price sent <br />Waiting for price</strong> </div> -->
							<!--<button class="price-button">{{"Waiting"}}</button>&nbsp;&nbsp;&nbsp;&nbsp;-->
						<?php } ?>	
						<!-- <img src="{{ asset('public/assets-new/images/chat.svg') }}" alt="chat"> -->
					</div>
				</li>
				@endforeach
			<?php }else{ ?>
				<p class="vendor_not_found_tag">We got your request. <br> Our experts will work with vendors to get you best price. <br> Please stay tuned on this page .</p>
			<?php } ?>
		</ul>
		<ul id="ajax_quote_content"></ul>
	</div>
	@else
	<p class="vendor_not_found_tag">We got your request. <br> Our experts will work with vendors to get you best price. <br> Please stay tuned on this page .</p>
	@endif
	<!-- {{ $quoteresponses->links() }} -->
	{{ $quoteresponses->appends(Illuminate\Support\Facades\Input::except('page'))->links() }}
	@php
	$location = request()->query('location') ? request()->query('location') : '';
	$vendor = request()->query('vendor') ? request()->query('vendor') : '';
	$sortby = request()->query('sortby') ? request()->query('sortby') : '';
	$page = request()->query('page') ? request()->query('page') : '';
	@endphp

</div>
<script>
	setInterval(function() {
        // window.location.reload();
		load_quote();
	}, 10000); 

	function load_quote(){
		var quote_id = $("#quote_id").val();
		var location = "{{ $location }}";
		var vendor = "{{ $vendor }}";
		var sortby = "{{ $sortby }}";
		var page = "{{ $page }}";
		$.ajax ({
			url:'<?php echo url("customer/ajax_load_sent_quote"); ?>',
			dataType:"JSON",
			data:{"_token": "{{ csrf_token() }}",quote_id:quote_id,location:location,vendor:vendor,sortby:sortby,page:page},
			type:'POST',
			success:function(data)
			{
				$("#quote_content").hide();
				$("#ajax_quote_content").html(data.popup_html);
					// window.location.reload();
				if (data.is_item!=0) { 
				}
			}
		});
	}
</script>
@endsection