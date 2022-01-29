@extends('customer::layouts.master')
@section('title', 'Update Quote')
@section('content')

<div class="card-content-div  vendor-requests">

	<ul class="breadcrumb">
		<li><a href="{{url('customer/dashboard')}}">Dashboard</a></li>
		<li><a href="{{url('customer/quote-requests')}}">Quote Requests</a></li>
		<li>Create Quote Request</li>
	</ul>

						<div class="row">
							<div class="col-md-6 offset-md-3">

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
											<form action="{{ url('customer/edit-quote-request',$quote->id) }}" method="post" enctype="multipart/form-data">
                                        @csrf
                                            <div class="row">
                                                <div class="col-md-12">
												    <div class="form-group">
                                                        <label>Item </label> <span class="span1">*</span>
                                                        {!! Form::text('item', $quote->item, ['class' => 'form-control','placeholder'=>'Enter Item', 'required']) !!}
                                                    </div>
												</div>	
												<div class="col-md-12">
                                                    <div class="form-group">
                                                        <label>Details</label> 
                                                        {!! Form::text('item_description', $quote->item_description, ['class' => 'form-control','placeholder'=>'Details', '']) !!}
                                                    </div>
												</div>	
												
												<div class="col-md-12">
                                                    <div class="form-group">
                                                        <label>Location</label> 
                                                        {!! Form::text('location', $quote->location, ['class' => 'form-control','placeholder'=>'Location', '']) !!}
                                                    </div>
												</div>
												<div class="col-md-12">
                                                    <div class="form-group">
                                                        <label>Category</label> 
														{!! Form::select('category', $categories, $quote->category, ['class' => 'form-control']) !!}
                                                    </div>
												</div>
												<div class="col-md-12">
                                                    <div class="form-group">
                                                        <label>Upload Image</label> 
														 {!! Form::file('item_sample') !!}
														 <?php if(!empty($quote->item_sample)){?>
														 <img src="{{ asset('public/assets/images/quotes/'.$quote->item_sample) }}" height="50" title="{{$quote->item}}">
														 <?php } ?>
                                                    </div>
												</div>
												
												<div class="col-md-12">
                                                    <div class="form-group">
                                                        <input type="checkbox" name="isprivacy" value="1" <?php if($quote->is_privacy == 1){echo 'checked';} ?>><label>&nbsp;&nbsp;I Agree to share my details</label> 
													</div>
												</div>
												
                                                <div class="col-md-12 space20">
                                                    
                                                        <button type="submit" class="btn btn-success">Update</button> &nbsp;
                                                        &nbsp; <a href="{{ url('customer/quote-requests') }}" class="btn btn-danger">Back to Quote Requests</a>
                                                    
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
