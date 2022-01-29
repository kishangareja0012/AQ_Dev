@extends('customer::layouts.master')
@section('title', 'Quote Requests')
@section('content')

<style>
.pagination>.active>span{background-color: #e57b23;border-color: #e57b23;}
.pagination>li>a, .pagination>li>span{color: #e57a23;}
ul.pagination {float: right;margin-bottom: 100px;}
</style>
<div class="card-content-div">
	<ul class="breadcrumb">
		<!--<li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>-->
		<li>Quote Requests</li>
		<a href="{{ url('/')}}" style="float: right;">Back</a>
	</ul>
	<div class="search-box">
		<form name="quote-search">
			<div class="input-group">
				<input type="text" name="keyword" class="form-control" placeholder="Search" value="<?php if(isset($_GET['keyword'])){echo $_GET['keyword']; } ?>">
				<div class="input-group-btn">
					<button class="btn btn-default" type="submit">
						<i class="glyphicon glyphicon-search"></i>
					</button>
				</div>
			</div>
		</form>
	</div>

	<div class="row">
		<div class="col-md-12">
			@if(Session::has('flash_message'))
			<div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
			@endif

			@if ($errors->any())
			<div class="alert alert-danger"><em>  {{ implode('', $errors->all(':message')) }}</em></div>
			@endif
		</div>
	</div>		

	<div class=" card-table-list">
		<div class="row hidden-xs">
			<div class="col-md-12">
				<div class="card-row-1 clickable-row">
					<h4>Photo</h4>                            
					<h4>Product name</h4>                           
					<h4></h4>
					<h4>Price Range</h4>
				</div>
			</div>
		</div>
	</div>

	<ul id="quote_content">
		@if(count($quotes) > 0)
		@foreach ($quotes as $quote)
		<li class="card-row-1 clickable-row" >
			<?php 
			if(!empty($quote->item_sample)){$path = asset('public/assets/images/quotes/'.$quote->item_sample);}
			else{$path = asset('public/assets/images/default.jpeg');}
			?> 
			<div> <a href="{{ url('customer/quote-sent-vendors/'.$quote->id) }}"><img class="myImg" width="100" src="{{ $path }}" /></a></div>
			<div>
				<!-- if sent count is 0 then redirect to next page "Expert is working on it" else sent count is more than 0 then redirect to vendor listing page -->
				@if($quote->count_sent==0)
					<h5 class="title-card-all"><a href="{{ url('customer/quote-sent-vendors/'.$quote->id.'?expert=1') }}">{{ ucfirst($quote->item) }}</a></h5>
				@else
					<h5 class="title-card-all"><a href="{{ url('customer/quote-sent-vendors/'.$quote->id) }}">{{ ucfirst($quote->item) }}</a></h5>
				@endif				
				<p class="product-details">{{ $quote->item_description }}</p>
				<p class="on-loction">{{$quote->location}}, {{ date('d M Y h:i a',strtotime($quote->created_at)) }}</p>
			</div>
			<div class="vendors-details">
				@if($quote->count_sent>0)
					@if($quote->count_sent>=200)
						<p><b>Request sent to more than <a href="{{ url('customer/quote-sent-vendors', $quote->id ) }}"> 200 </a> Vendors</b></p>
					@else
						<p><b>Request sent to more than <a href="{{ url('customer/quote-sent-vendors', $quote->id ) }}"> {{$quote->count_sent}} </a> Vendors</b></p>
					@endif
				@else
					<p><b>Finding vendors in your area</b></p>
				@endif
				@if($quote->count_responded>0)
					<p><b>Price Received from <a href="{{ url('customer/quote-sent-vendors', $quote->id ) }}"> <?php echo $quote->count_responded; ?> </a> Vendors</b></p>
				@endif
			</div>
			<?php if($quote->count_responded > 0){
					$max_price = max($quote->min_price,$quote->max_price);
					$min_price = min($quote->min_price,$quote->max_price);
				?>
				<div><button class="price-button">
					<a href="{{ url('customer/quote-sent-vendors/'.$quote->id) }}" style="color:#fff;"><?php 
					if($quote->min_price == $quote->max_price){echo "&#8377; ".$quote->min_price;}
					else{echo "&#8377; ".$min_price." - &#8377; ".$max_price;}
					?>
				</a>
			</button></div>
		<?php }else{ ?>
			
				<div><strong>Waiting for price from vendors</strong></div>
			
			<!--<div ><button class="price-button">Waiting</button></div>-->
		<?php } ?>
	</li>
	@endforeach
	@else
	<li align="center"><b>No Records Found	</b></li>
	@endif
	
</ul> <!-- end: PAGE CONTENT-->
<ul id="ajax_quote_content"></ul>
	<!-- {{ $quotes->links() }} -->
{{ $quotes->appends(Illuminate\Support\Facades\Input::except('page'))->links() }}
	@php
	$keyword = request()->query('keyword') ? request()->query('keyword') : '';
	$page = request()->query('page') ? request()->query('page') : '';
	@endphp
</div>
<script>
  	
	setInterval(function() {
		load_quote();
	}, 20000); 
	function load_quote(){
		var keyword = "{{ $keyword }}";
		var page = "{{ $page }}";
		$.ajax ({
			url:'<?php echo url("customer/ajax_quote_load"); ?>',
			dataType:"JSON",
			data:{"_token": "{{ csrf_token() }}",keyword:keyword,page:page},
			type:'POST',
			success:function(data)
			{
				$("#quote_content").hide();
				$("#ajax_quote_content").html(data.popup_html);
				// window.location.reload();
			}
		});
	}
	$("ul.pagination").addClass("pull-right");
</script>
@endsection
