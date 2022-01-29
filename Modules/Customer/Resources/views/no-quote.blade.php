@extends('customer::layouts.master')
@section('title', 'Update Quote')
@section('content')
<style>
	p.customer-welcome-message {border-radius: 25px; border: 2px solid #c9372c; padding: 45px; width: 100%; height: 200px; text-align: center; font-size: 24px; line-height: 1.5em; font-weight: bold; color: #e67e22; margin-top: 16% !important; }
	body.inner-vendors {margin-bottom: 0px !important; }
	@media only screen and (max-width: 600px) {
  	p.customer-welcome-message { padding: 10px; height: 225px;}
}
</style>

<div class="card-content-div  vendor-requests">

<!-- 	<ul class="breadcrumb">
		<li><a href="{{url('customer/dashboard')}}">Dashboard</a></li>
		<li>Edit Profile</li>
	</ul> -->

	<div class="row">
		<div class="col-md-8 col-md-offset-2">

			@if(Session::has('flash_message'))
			<div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
			@endif

			@if ($errors->any())
			<div class="alert alert-danger"><em>  {{ implode('', $errors->all(':message')) }}</em></div>
			@endif

			<!-- start: EXPORT DATA TABLE PANEL  -->
			<div class="panel panel-white">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12">
							<p class="customer-welcome-message">
							Hi,Welcome to Anyquote <br> You have no Quote request yet
							<br>
							<a href="{{ url('/') }}" style="background: #2c2e39; padding: 6px 49px 4px 30px; font-size: 15px; color: #FFF; border-radius: 50px; text-decoration: none; margin-top: 15px; " class="btn"> <i class="glyphicon glyphicon-plus" aria-hidden="true"></i> New Quote </a>
							</p>
						</div>
					</div>
				</div>
			</div>
			<!-- end: EXPORT DATA TABLE PANEL -->
		</div>
	</div>
	<!-- end: PAGE CONTENT-->
</div>
@endsection

