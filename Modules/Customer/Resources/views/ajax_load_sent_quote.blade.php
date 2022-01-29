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
				<p class="vendor_not_found_tag">Our Experts is working on it.</p>
				<!-- <li class="card-row-3 clickable-row"><b>No Records Found</b></li> -->
			<?php } ?>
			<!-- {{ $quoteresponses->links() }} -->