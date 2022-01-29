@extends('customer::layouts.master')
@section('title', 'Create Quote Request')
@section('content')
<div class="card-content-div">
	<ul class="breadcrumb">
		<li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>
		<li>Create Quote Request</li>
	</ul>

	<div class="vendor-list grid-card">
		<div class="row">
			<div class="col-md-8 col-lg-8 col-sm-8 col-xs-12">
				<div>
					<div class="vendor-form1">
					<h3>Create Quote Request</h3>
					@if(Session::has('flash_message'))
						<div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
					@endif

					@if ($errors->any())
						<div class="alert alert-danger"><em>  {{ implode('', $errors->all(':message')) }}</em></div>
					@endif
					<form action="{{ url('customer/create-quote-request') }}" method="post" enctype="multipart/form-data">
                                        @csrf
										<div class="form-group">
											<label for="item">Title </label> <span class="span1">*</span>
											{!! Form::text('item', null, ['class' => 'form-control','placeholder'=>'Enter Title', 'required']) !!}
										</div>
										<div class="form-group">
											<label for="item_description">Description</label> <span class="span1">*</span>
											{!! Form::textarea('item_description', null, ['rows' => 3, 'class' => 'form-control','placeholder'=>'Enter Description', '']) !!}
										</div>
										<div class="form-group">
											<label for="location">Location</label> <span class="span1">*</span>
											{!! Form::text('location', null, ['class' => 'form-control','placeholder'=>'Location', '']) !!}
										</div>
										<div class="form-group">
											<label for="category">Category</label> <span class="span1">*</span>
											{!! Form::select('category', $categories, null, ['class' => 'form-control']) !!}
										</div>
										<div class="form-group">
											<label for="item_sample">Upload Image</label> <span class="span1">*</span>
												{!! Form::file('item_sample') !!}
										</div>
										<div class="form-group">
											<input type="checkbox" name="isprivacy" value="1"><label for="isprivacy">&nbsp;&nbsp;I Agree to share my details</label> 
										</div>
										<div class="form-group btn-quotes">
											<button type="submit" class="btn btn-success">Submit</button>
										</div>
											
                                        </form>
						
					</div>
				</div>
			</div>
			<div class="col-md-4 col-lg-4 col-sm-4">&nbsp;</div>

		</div>
						

	</div>

						<!-- end: PAGE CONTENT-->
					</div>
				
@endsection
