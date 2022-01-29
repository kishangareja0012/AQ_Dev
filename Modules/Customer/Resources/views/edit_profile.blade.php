@extends('customer::layouts.master')
@section('title', 'Update Quote')
@section('content')
<style>
	.btn-quotes button, .btn-quotes a {padding: 6px 29px 5px 38px;}
</style>

<div class="card-content-div  vendor-requests">

	<ul class="breadcrumb">
		<li><a href="{{url('customer/quote-requests')}}">Dashboard</a></li>
		<!-- <li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li> -->
		<li>Edit Profile</li>
	</ul>

	<div class="row">
		<div class="col-md-6 offset-md-3">

			@if(Session::has('flash_message'))
			<div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
			@endif

			@if(Session::has('flash_error_message'))
			<div class="alert alert-danger"><em> {!! session('flash_error_message') !!}</em></div>
			@endif

			@if ($errors->any())
			<div class="alert alert-danger"><em>  {{ implode('', $errors->all(':message')) }}</em></div>
			@endif

			<!-- start: EXPORT DATA TABLE PANEL  -->
			<div class="panel panel-white">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12">
							<form method="post" action="{{ url('customer/submit_profile') }}" enctype="multipart/form-data"  autocomplete="off" id="profile_update">
								@csrf
								<div class="modal-body login-form">
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label for="mobilenumber" class="control-label"> Name</label>
												<input style="margin-bottom: 5px;" type="text" name="name" class="form-control number" id="name" required="true" value="<?php echo isset($userdata->name) ? $userdata->name : ''; ?>">
											</div>
										</div>
									</div>
									<?php  $var = ''; $required = 'require'; ?>
									@if($userdata->email_mobile_verified)
									<?php
										$var = 'readonly disabled';
										$required = '';
									?>
										<input type="hidden" name="all_verified" value="1">
									@endif

									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label for="mobilenumber" class="control-label">Email</label>
												<input {{$var}} style="margin-bottom: 5px;" type="email" name="email" class="form-control number" required id="profile_email"  value="<?php echo isset($userdata->email) ? $userdata->email : ''; ?>" <?php if ($userdata->login_method == 'Email'): ?>
												readonly disabled
												<?php endif?>>

											</div>
										</div>
									</div>

									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group col-md-12">
													<div class="form-group">
														<label for="mobilenumber" class="control-label">Mobile Number</label>
														<input {{$var}}  style="margin-bottom: 5px;" type="text" name="mobile" class="form-control number" required id="profile_mobile" data-login-method="Number" value="<?php echo isset($userdata->mobile) ? $userdata->mobile : ''; ?>" onkeypress="return isNumber(event)" <?php if ($userdata->login_method == 'Number'): ?>
														readonly disabled
													<?php endif?>
													>
												</div>
											</div>
										</div>
									</div>			

								</div>

								<div class="row" style="display: none;">
									<div class="col-md-12">
										<div class="form-group">
											<input style="margin-bottom: 5px;" type="text" name="login_method" class="form-control number"  value="<?php echo isset($userdata->login_method) ? $userdata->login_method : ''; ?>" readonly="true">
										</div>
									</div>
								</div>

								<div class="otpShow" style="display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group ">
                                <label>OTP Sent to your mobile number</label>
                                <div class="otp-enter">
                                    <input type="text" name="cust_otp1" class="cust_otp form-control" maxlength="1" {{$required}}>
                                    <input type="text" name="cust_otp2" class="cust_otp form-control" maxlength="1" {{$required}}>
                                    <input type="text" name="cust_otp3" class="cust_otp form-control" maxlength="1" {{$required}}>
                                    <input type="text" name="cust_otp4" class="cust_otp form-control" maxlength="1" {{$required}}>
                                </div>

                                <div class="text-center" style="margin-top: 10px; ">
                                <?php if ($userdata->login_method == "Email"): ?>
                                    <a class="btn" style="font-size: 10px; background-image: none; outline: 0; color: #000; background-color: #55555529;" id="profile-resend-btn"><strong>Resend Code</strong> </a>
                                    <?php else: ?>
                                    	<a class="btn" style="font-size: 10px; background-image: none; outline: 0; color: #000; background-color: #55555529;" id="profile-email-resend"><strong>Resend Code</strong> </a>
                                    	<input type="hidden" id="email_sent_otp" name="email_sent_otp" value="">
                                <?php endif;?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


							</div>

							<div class="row">
								<div class="col-md-12 text-center">
								<p class="verifyOtp" style="display:none;"></p>
									<div class="form-group ">
										<div class="btn-quotes text-center">
										@if($userdata->email_mobile_verified!=1)
											<?php if ($userdata->login_method == "Email"): ?>
												<button id="vendor-register" onclick="verify_profile_otp()" class="btn update_button" type="button"  > Update &nbsp; <i class="glyphicon glyphicon-arrow-right" aria-hidden="true"></i> </button>
											<?php else: ?>
											 	<button class="btn update_button" type="submit"  > Update &nbsp; <i class="glyphicon glyphicon-arrow-right" aria-hidden="true"></i> </button>
											<?php endif;?>
										@else
											<button class="btn update_button" type="submit"  > Update &nbsp; <i class="glyphicon glyphicon-arrow-right" aria-hidden="true"></i> </button>
										@endif
										</div>
									</div>
								</div>
							</div>
						</form>
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
<script>
	function isNumber(evt) {
		evt = (evt) ? evt : window.event;
		var charCode = (evt.which) ? evt.which : evt.keyCode;
		if (charCode > 31 && (charCode < 48 || charCode > 57)) {
			return false;
		}
		return true;
	}
         </script>
