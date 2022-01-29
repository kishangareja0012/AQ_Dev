@extends('vendor::layouts.master')
@section('title', 'Vendor Dashboard')
@section('content')
<style>
	li.points{list-style: decimal !important;}
	ul.text-left.advantages-points {list-style: decimal; padding-left: 39%; }
	input.razorpay-payment-button {background: #ed8d21; border-radius: 50px; border: none; line-height: 25px; padding: 8px 20px 3px 20px; color: #FFF; }
	.mfix.animations.test.font-loaded.drishy > #modal {
		width: 50% !important;
	}
	.mchild {
    width: 50% !important;
}
div#modal, div.mchild {width: 50% !important; }
</style>
<div class="card-content-div  vendor-requests">
	<ul class="breadcrumb">
		<li><a href="{{url('vendor/quote-requests')}}">Dashboard</a></li>
		<li>Subscribe Now</li>
	</ul>
	<div class="row" style="margin-top:15%;">
		<div class="col-md-12 space20 text-center">
			<h4 class="pt-0 pb-0 text-center"> Your Quote Request Limit Is Over Please Subscribe Us To Continue   </h4>
			<ul class="text-left advantages-points">
				<li class="points">You can post unlimited quotes</li>
				<li class="points">You get to see the customer name and number</li>
				<li class="points">Your listing will be in the top</li>
			</ul>
			<br>
			<!-- <button type="" class="btn price-button">Subscribe Now</button> -->
			<div class="payment-modal">
				@php
				$vendor_data = Session::get('vendor_data');
				@endphp

				<form action="{!!url('vendor/payment')!!}" method="POST" >
					<!-- Note that the amount is in paise = 50 INR -->
					<!--amount need to be in paisa-->
					<script src="https://checkout.razorpay.com/v1/checkout.js"
					data-key="{{ Config::get('razorpay.razor_key') }}"
					data-amount="1000"
					data-buttontext="Subscribe Now"
					data-name="Anyquote"
					data-description="Order Value"
					data-image="{{ asset('public/assets-new/images/Logo-color.png') }}"
					data-prefill.name="{{$vendor_data[0]->name}}"
					data-prefill.contact="{{$vendor_data[0]->mobile}}"
					data-prefill.email="{{$vendor_data[0]->email}}"
					data-theme.color="#ff7529">
				</script>
				<input type="hidden" name="_token" value="{!!csrf_token()!!}">
			</form>
		</div>

	</div>
</div>
</div>
@endsection