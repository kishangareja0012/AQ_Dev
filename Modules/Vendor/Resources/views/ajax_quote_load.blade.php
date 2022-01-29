<div class="card-content-div  vendor-requests">
	<ul class="breadcrumb">
		<li><a href="{{url('vendor/quote-requests')}}">Quote Requests</a></li>
		<!--<li>View your Requests</li>-->
        <li>Quote Response</li>
        <?php if ($is_premium): ?>
        <li class="last-child pull-right">Premium Vendor</li>
        <?php endif ?>
	</ul>
	<div class="col-md-12">
		<div class="col-md-6">
			<div class="numberfo-viwe-vendor">
				<p><b>{{$today_quotes_sent_count}}</b> Requests today </p>

			</div>
			<div class="numberfo-viwe-vendor">
				<p><b>{{$today_quotes_responded_count}}</b> Replies today </p>

			</div>
		</div>
		<div class="col-md-6">
			<div class="text-right pull-right total-request total-response">
				<div class="viwe-vendor">
					<p> Total Requests <b><?php echo $quotes_sent_count; ?></b></p>

				</div>
				<div class="viwe-vendor">
					<p> Total Responded <b><?php echo $quotes_responded_count; ?></b></p>

				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="vendor-from-search ">
				<form name="searchcustomerquotes">
				<div class="input-group search-location">
					<input type="text" name="keyword" class="form-control" placeholder="Search"  value="<?php if(isset($_GET['keyword'])){echo $_GET['keyword']; } ?>">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
							<i class="glyphicon glyphicon-search"></i>
						</button>
					</div>
				</div>
				</form>

				<!--
				<div class="dropdown">
					<button class="btn  dropdown-toggle" type="button" data-toggle="dropdown">
						<span class="glyphicon glyphicon-filter"></span></button>
					<div class="dropdown-menu">
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
				-->

			</div>
		</div>
	</div>

	<div class="card-table-list ">
		<div class="col-md-12 hidden-xs">
			<div class="card-row-1">
				<p>Photo</p>
				<p>Quote Details & Posted Place & Date </p>
				<p>Customer details</p>
				<p>Price</p>
			</div>
		</div>
	</div>

	<ul id="quote_content">
		@foreach ($vQuotes as $quote)
		<li class="card-row-1 clickable-row">
			<?php 
				if(!empty($quote->item_sample)){$path = asset('public/assets/images/quotes/'.$quote->item_sample);}
				else{$path = asset('public/assets/images/default.jpeg');}
			?>
			<?php if($quote->isResponded){?>
			<div><a href="{{ url('vendor/view-sent-quote',$quote->vendor_quote_id)}}">
            <img width = "100" src="{{ $path }}" alt="{{ $quote->item }}" title="{{ $quote->item }}" class="quote-img-up" /></a></div>
			<div>
				<h5 class="title-card-all"><a href="{{ url('vendor/view-sent-quote',$quote->vendor_quote_id)}}">{{ $quote->item }}</a> </h5> 
				<div class="product-details">{{ $quote->item_description }} </div>
				<p class="on-loction">{{ ucfirst($quote->location) }}, {{ date('d M Y h:i a',strtotime($quote->quote_created_at)) }}</p>
			</div>
			<?php }else{ ?>
			<div><a href="{{ url('vendor/send-quote',$quote->vendor_quote_id)}}"><img width = "100" src="{{ $path }}" alt="{{ $quote->item }}" title="{{ $quote->item }}" class="quote-img-up" /></a></div>
			<div>
				<h5 class="title-card-all"><a href="{{ url('vendor/send-quote',$quote->vendor_quote_id)}}">{{ $quote->item }}</a> </h5> 
				<div class="product-details">{{ $quote->item_description }} </div>
				<p class="on-loction">{{ ucfirst($quote->location) }}, {{ date('d M Y h:i a',strtotime($quote->quote_created_at)) }}</p>
			</div>			
			<?php } ?>	
			
			<div class="vendors-details">
			<p><b>AnyQuote Customer</b> </p>
			<?php /* if($quote->customer_name != $quote->customer_mobile && $quote->customer_name!=$quote->customer_email){ ?>
				<p><b>{{ ucfirst($quote->customer_name) }}</b> </p>
			<?php }else{ ?>
				<p><b>AnyQuote Customer</b> </p>
			<?php } ?>
				<?php if(!$quote->is_privacy){ ?>
				<p> {{ $quote->customer_mobile }}</p>
				<p> {{ $quote->customer_email }}</p>
				<?php } */?>
			</div>
			
			<?php if($quote->isResponded){?>
				<div>
					<a href="{{ url('vendor/view-sent-quote',$quote->vendor_quote_id)}}" class='price-button'><i class="fa fa-eye"></i> View Response</a>
					<p class="ribbon ribbon-1 color-wight">{{$quote->vendor_quote_status}}</p>	
				</div>
			<?php }else{ ?>
				<div>
					<?php 
					if (!$is_premium) {
						if ($quotes_responded_count<=3){
						?>
						<a href="{{ url('vendor/send-quote', $quote->vendor_quote_id ) }}" class="price-button">Provide your price</a>						
					<?php }else{ ?>
						<a href="{{ url('vendor/subscribe-now') }}" class="price-button">Provide your price</a>
					<?php } }else{ ?>
						<a href="{{ url('vendor/send-quote', $quote->vendor_quote_id ) }}" class="price-button">Provide your price</a>
					<?php } ?>
					<?php if($quote->vendor_quote_status == 'New'){ ?>
						<p class="ribbon ribbon-2 color-wight">{{$quote->vendor_quote_status}}</p>
					<?php }elseif($quote->vendor_quote_status == 'Viewed'){ ?>
						<p class="ribbon ribbon-4 color-wight">{{$quote->vendor_quote_status}}</p>
					<?php }else{ ?>
						<p class="ribbon ribbon-3 color-wight">{{$quote->vendor_quote_status}}</p>		
					<?php } ?>
				</div>
			<?php } ?>
		</li>
		@endforeach
	</ul>
	<ul id="ajax_quote_content"></ul>
</div>