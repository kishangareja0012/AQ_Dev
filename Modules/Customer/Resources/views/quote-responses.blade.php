@extends('customer::layouts.master')
@section('title', 'Responses received from Vendors for '.$quotedata->item )
@section('content')

<div class="card-content-div">
<ul class="breadcrumb">
		<li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>
		<li><a href="{{url('customer/quote-requests')}}">{{$quotedata->item}}</a></li>
		<li>Responses received from Vendors</li>
</ul>
	<div class="grid-list-2">
		<div><img src="{{asset('public/assets/images/quotes/'.$quotedata->item_sample)}}" width="100" alt="{{$quotedata->item}}" title="{{$quotedata->item}}"></div>
		<div>
			<h5 class="title-card-all">{{$quotedata->item}}</h5>
			<p>{{$quotedata->item_description}}
			</p>

		</div>
		<div class="numberfo-viwe-vendor">
			<p>Request sent to <b>{{$vendors_count_sent}}</b> Vendors</p>
			<p>Response received from <b>{{$vendors_count_responded}}</b> Vendors</p>

		</div>

	</div>
	<div class="row hidden-sm hidden-xs">
		<div class="from-search-short ">
			<div class="form-group">
				<select class="border-rs-50 form-control">
					<option selected>Sort by price</option>
					<option>Price -- Low to High</option>
					<option>Price -- High to Low</option>
					<option> Newest First</option>
					<option> Discount</option>
				</select>
			</div>
			<div class="form-group">
				<div class="input-group search-location">
					<input type="text" class="form-control" placeholder="Select by location ">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
							<i class="glyphicon  glyphicon-map-marker"></i>
						</button>
					</div>
				</div>
			</div>
			<div class="form-group">
				<input class="form-control border-rs-50" id="focusedInput" type="text"
					placeholder="Responded Vendors">
			</div>
			<div class="form-group">
				<div class="input-group search-location">
					<input type="text" class="form-control" placeholder="Search by vendor">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
							<i class="glyphicon glyphicon-search"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row visible-sm visible-xs">

		<div class="from-search-short-mobil from-search-short">
			<div class="dropdown">
				<button class="btn  dropdown-toggle" type="button" data-toggle="dropdown">
				<span class="glyphicon glyphicon-filter"></span></button>
				
			  
			  <div class="dropdown-menu">
			<div class="form-group">
				<select class="border-rs-50 form-control">
					<option selected>Sort by price</option>
					<option>Price -- Low to High</option>
					<option>Price -- High to Low</option>
					<option> Newest First</option>
					<option> Discount</option>
				</select>
			</div>
			<div class="form-group">
				<div class="input-group search-location">
					<input type="text" class="form-control" placeholder="Select by location ">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
							<i class="glyphicon  glyphicon-map-marker"></i>
						</button>
					</div>
				</div>
			</div>
			<div class="form-group">
				<input class="form-control border-rs-50" id="focusedInput" type="text"
					placeholder="Responded Vendors">
			</div>
			<div class="form-group">
				<div class="input-group search-location">
					<input type="text" class="form-control" placeholder="Search by vendor">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
							<i class="glyphicon glyphicon-search"></i>
						</button>
					</div>
				</div>
			</div>
			</div>
			</div>
		</div>
	</div>
	<div class="card-table-list ">

		<div class="col-md-12 hidden-xs">
			<div class="grid-list-titls-2">

				<p>Vendor Name</p>
				<p>Vendor Details</p>
				<p>Contact Number</p>
				<p>Price</p>

			</div>
		</div>


		<ul>
		<?php if(count($quoteresponses) > 0){ ?>	
		@foreach ($quoteresponses as $request)
			<li class="card-row-3 clickable-row" >
				<div>
					<h5 class="title-card-all">{{ $request->name }}</h5>
					<p>{{ $request->website }}</p>
				</div>
				<div>
					<h6><b>Address:</b></h6>
					<p>{{ $request->company_address }}</p>
				</div>
				<div>
					<h3>  
					<?php if(!$request->is_privacy && !empty($request->company_phone)){?>
						<span class="phone-icon "></span>{{ $request->company_phone }}
					<?php } ?>	
					</h3>
				</div>

				<div>
					
					<?php if($request->isResponded){?><button class="price-button"><?php echo $request->price; ?></button><?php } ?>
					<?php if($request->isResponded){?>
						<a href="{{url('customer/view-sent-quote',$request->vendor_quote_id)}}" class='price-button view-more-button'><i class="fa fa-eye"></i> View Response</a>
					<?php } ?>
					<img src="{{ asset('public/assets-new/images/chat.svg') }}" alt="chat">
				</div>
			</li>
			@endforeach
			<?php }else{ ?>
				<li class="card-row-3 clickable-row"><b>No Records Found</b></li>
			<?php } ?>
		</ul>
	</div>
</div>		
@endsection