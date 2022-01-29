        @if(count($quotes) > 0)
        @foreach ($quotes as $quote)
        <li class="card-row-1 clickable-row" >
            <?php
if (!empty($quote->item_sample)) {$path = asset('public/assets/images/quotes/' . $quote->item_sample);} else { $path = asset('public/assets/images/default.jpeg');}
?>
            <div> <a href="{{ url('customer/quote-sent-vendors/'.$quote->id) }}"><img class="myImg" width="100" src="{{ $path }}" /></a></div>
            <div>
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
            <?php if ($quote->count_responded > 0) {
                $max_price = max($quote->min_price,$quote->max_price);
                $min_price = min($quote->min_price,$quote->max_price);
                ?>
                <div><button class="price-button">
                    <a href="{{ url('customer/quote-sent-vendors/'.$quote->id) }}" style="color:#fff;"><?php
if ($quote->min_price == $quote->max_price) {echo "&#8377; " . $quote->min_price;} 
else {echo "&#8377; " . $min_price . " - &#8377; " . $max_price;}
    ?>
                </a>
            </button></div>
        <?php } else {?>
            
				<div><strong>Waiting for price from vendors</strong></div>
			
            <!--<div ><button class="price-button">Waiting</button></div>-->
        <?php }?>
    </li>
    @endforeach
    @else
    <li align="center"><b>No Records Found  </b></li>
    @endif
    <!-- {{ $quotes->links() }} -->