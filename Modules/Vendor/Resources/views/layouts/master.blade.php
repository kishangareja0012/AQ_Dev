<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title') - AnyQuote</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaina+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets-new/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets-new/css/styles.css') }}">
    <script src="{{ asset('public/assets-new/jquery/jquery.min.js') }}"></script>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
    <link rel="icon" href="{{ asset('public/favicon.png') }}" type="image/gif" sizes="32x32">
</head>
<style>
    .select2-container--default .select2-selection--single {background-color: #fff; border: 1px solid #aaa; border-radius: 50px; }
    .select2-container .select2-selection--single {box-sizing: border-box; cursor: pointer; display: block; height: 32px; -webkit-user-select: none; }
    .select2-container {width: 100% !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered {line-height: 34px; }

     /*menu css*/
    ul.button-list li ul.dropdown{min-width: 95%; background: #ed8d21; display: none; position: absolute; z-index: 999; left: 10px; } 
    ul.button-list li:hover ul.dropdown {display: block; line-height: 40px; padding: 0px 0px 0px 15px; border-radius: 10px; } 
    li.sub-item a.sub-menu-list:hover {background: unset !important; color: #fff !important; }
    ul.button-list li:hover ul.dropdown{width: 160px !important;}
    /*menu css*/
/*menu css for this page only*/
a.sub-menu-list {
    background: unset !important;
    color:#fff !important;
} 
.footer-2 {background: #ffffff;}
    footer ul li a {color: #ed8d21; }
    footer ul li::after {color: #ed8d21; }
    footer p, footer h3 {color: #ed8d21; }
    .col-md-6.footer-reserve-content{text-align: right !important;}
    .col-md-6.col-sm-12.col-xs-12.copy-right-section {text-align: right;}
    ul.footer-links {padding-left: 0% !important;}
/*.button-list li:nth-child(2) a::before {background: url(../images/user-icon.png) no-repeat right; background-size: 4%; }*/
/*end page css*/
@media (max-width: 576px) and (min-width: 0px){
    li.vendor-name-menu {width: 100%;}
    li.sub-item {width: 100%;}
    .button-list li {margin-bottom: 0;}
    .col-md-6.col-sm-12.col-xs-12.copy-right-section {text-align: center;}
}
@media (max-width: 720px) and (min-width: 576px){
    .col-md-6.col-sm-12.col-xs-12.copy-right-section {text-align: center;}
    ul.footer-links {padding-left: 32% !important;}
}
</style>
<body class="inner-vendors">
    <header>
        <nav class="navbar header-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="{{route('vendor.quotes')}}"><img src="{{ asset('public/assets-new/images/Logo-color.png') }}" alt="" title=""></a>
                </div>
                <div class="collapse navbar-collapse navbar-right" id="myNavbar">
                    <ul class="nav navbar-nav nav-main">
                        <!--<li class="active"><a href="#">About</a></li>
                        <li ><a href="#">Services</a></li>
                        <li ><a href="#">Blogs</a></li>-->
                       <li ><a class="contact-us" href="{{ url('contact_us') }}" >Contact Us</a></li>
                    </ul>
                    @if (!Session::has('VENDORID'))
                    <ul class="nav navbar-nav  button-list">
                        <li ><a href="#" data-toggle="modal" data-target="#login">Login to View Your Requests <span
                            class="g-user"></span></a></li>
                            <li ><a href="#">Are you a Vendor <span class="g-arrow-right"></span></a></li>
                        </ul>
                        @else
                        @php
                    $vendor = \DB::table('vendors')->where('id',Session::get('VENDORID'))->first(); $vendorname = ($vendor->name!='') ? $vendor->name : $vendor->mobile;
                    @endphp
                        <ul class="nav navbar-nav  button-list">
                            <li class="vendor-name-menu"><a href="{{ url('vendor/quote-requests') }}">Quote Requests</a> </li>
                            <li class="vendor-name-menu" id="customer-first"><a href="#">{{$vendorname}} <span class="g-arrow-right"></span></a>
                            <ul class="dropdown sub-menu">
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="{{ url('vendor/edit_profile') }}">Edit Profile</a></li>
                                 <?php if ($vendor->is_premium==0): ?>
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="{{ url('vendor/subscribe-now') }}">Get Premium</a></li>
                                <?php endif ?>
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="#"  onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a></li>
 <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          {{ csrf_field() }}
  </form>
                            </ul>
                            </li>
                        </ul>
                        @endif
                    </div>
                </div>
            </nav>
        </header>
        <section>
            <div class="container">
             @yield('content')
         </div>                
     </section>
     <footer class="footer-2">
        <div class="container">
           <div class="row">
               <div class="col-md-6 col-sm-12 col-xs-12">
                   <ul class="footer-links">
                       <li><a href="{{ url('privacy-policy') }}" target="_blank">Privacy Policy</a></li>
                       <li><a href="{{ url('terms-of-use')}}" target="_blank">Terms of Use</a></li>
                   </ul>
               </div>
               <div class="col-md-6 col-sm-12 col-xs-12 copy-right-section">
               <p><a href="{{ url('disclaimer-policy')}}" target="_blank" style="color: #f09b27;">Disclaimer &copy; <?php echo date('Y'); ?> AnyQuote.&nbsp;&nbsp;All Rights Reserved. </a> &nbsp;  / <a href="{{ url('blogs') }}" style="color: #f09b27;">Blogs</a></p>  
            </div>
        </div>
    </div>
</footer>
<script src="{{ asset('public/assets-new/jquery/bootstrap.min.js') }}"></script>
    <script>

    // start : disabled CTRL + U and inspect element
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    document.onkeydown = function(e) {
        if ((e.ctrlKey && (e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 85 ||e.keyCode === 117 || e.keyCode === 73)) ||
                (e.keyCode === 123)) {
            return false;
        } else {
            return true;
        }
    };
    $(document).keypress("u", function(e) {
        if (e.ctrlKey) {
            return false;
        } else {
            return true;
        }
    });

    function onKeyDown() {
        // current pressed key
        var pressedKey = String.fromCharCode(event.keyCode).toLowerCase();

        if (event.ctrlKey && (pressedKey == "c" ||
                pressedKey == "v")) {
            // disable key press porcessing
            event.returnValue = false;
        }
    } // onKeyDown
    // end : disabled CTRL + U and inspect element

$('#customer-first').click(function(){
    $('#myNavbar').animate({height:'265px'}, 500);
});

        $(".otpShow").hide();
        $(document).ready(function(){
            $('[id^=mobile],[id^=quote_mobile]').keypress(validateNumber);
            $('[id^=mobile],[id^=quote_mobile]').keyup(validateNumber);
            
            $("#cust-login,#resend-btn").click(function(){
                var custmobile = $('[id^=mobile]').val();
                if(custmobile.length === 10 ){
                    $(".otpShow").show();
                    $("#cust-login").hide();
                }else{      
                    $(".otpShow").hide();
                    $("#cust-login").show();
                    alert('Enter Valid Mobile Number'); 
                    $('[id^=mobile]').focus();
                    return false;
                }
                
                $.ajax({
                    url: '{{url("sendOtp")}}',
                    data: {"custMobile": custmobile},
                    type: 'post',
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    success: function(result)
                    {
                        //alert(result);
                        
                    }
                });
            });  


            $("#quote_send_otp").click(function(){
                var custmobile = $('[id^=quote_mobile]').val();
                if(custmobile.length === 10 ){
                    $(".quote_otp").show();
                    $("#quote_send_otp").hide();
                    $("#quote_verify_otp").show();
                }else{      
                    $(".quote_otp").hide();
                    $("#quote_send_otp").show();
                    $("#quote_verify_otp").hide();
                    alert('Enter Valid Mobile Number'); 
                    $('[id^=quote_mobile]').focus();
                    return false;
                }
                
                $.ajax({
                    url: '{{url("sendOtp")}}',
                    data: {"custMobile": custmobile},
                    type: 'post',
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    success: function(result)
                    {
                        //alert(result);
                        
                    }
                });
            });  


            $(".cust_otp").keyup(function () {
                if (this.value.length == this.maxLength) {
                    var $next = $(this).next('.cust_otp');
                    if ($next.length)
                        $(this).next('.cust_otp').focus();
                    else
                        $(this).blur();
                }
            });
        });

        function validateNumber(event) {    
            var numberlG = event.target.value; 
            /*
            if(numberlG.length === 10 ){
                $(".otpShow").show();
            }else{      
                $(".otpShow").hide(); 
            } 
            */   

            var key = window.event ? event.keyCode : event.which;
            if (event.keyCode === 8 || event.keyCode === 46) {
                return true;
            } else if ( key < 48 || key > 57 ) {
                return false;
            } else {
                return true;
            }
        }


        $('#imagePreview').hide();
        

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').css('background-image', 'url('+e.target.result +')');
                    $('#imagePreview').css('height', '100px');
                    $('#imagePreview').css('width', '100px');
                    $('#imagePreview').fadeIn(650);
                    $(".image-section").hide();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        $("#imageUpload").change(function() {
            readURL(this);
        });
        $(".remove").click(function(){
            $(".image-section").show();
            $('#imagePreview').removeAttr('style');
            $('#imagePreview').hide();
        });

// upload doc file code
        $('#filePreview').hide();
        $("#fileUpload").change(function(e){
            var fileName = e.target.files[0].name;
            $('#filePreview').show();
            $('#file_loaded').html(fileName);
            $('.upload-images.doc-file').hide();
        });
         $(".removefile").click(function(){
            $('#filePreview').hide();
            $('.upload-images.doc-file').show();
        });
// upload doc file code


        $(document).ready(function() {
          if (window.File && window.FileList && window.FileReader) {
            $("#imageUpload1").on("change", function(e) {
              var files = e.target.files,
              filesLength = files.length;
              for (var i = 0; i < filesLength; i++) {
                var f = files[i]
                var fileReader = new FileReader();
                fileReader.onload = (function(e) {
                  var file = e.target;
                  $("<span class=\"pip\">" +
                    "<img class=\"imageThumb\" src=\"" + e.target.result + "\" title=\"" + file.name + "\"/>" +
                    "<br/><span class=\"remove\">Remove image</span>" +
                    "</span>").insertAfter("#imageUpload1");

    });
                fileReader.readAsDataURL(f);
            }
        });
        } else {
            alert("Your browser doesn't support to File API")
        }
    });

// add similar product script
$("#addSimilar").click(function(){
    $(".send_quote_buttons, #addSimilar").hide();
    $('#similarProduct').show();
});

$(document).ready(function() {
    var count = 0;
    $(".add-more").click(function(){ 
      var html = $(".copy").html();
      count = count + 1;
      if (count<=4) {
        $(".after-add-more:last").append(html);
    }else{
        alert('You can add only 5 similar products..!');
    }
});

    $("body").on("click",".remove",function(){ 
      $(this).parents(".control-group").remove();
  });
});

function imagePreview(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
        $(input).closest("div.upload-images").find('.image_preview').attr('src', e.target.result);
    }
    reader.readAsDataURL(input.files[0]);
}
}
// add similar product script

</script>
<script type="text/javascript" src="{{ URL::asset('public/js/typeahead.js')}}"></script>
<script type="text/javascript">

  $('.category_search').select2({
      tags: true, 
       placeholder: {
    text: '{{isset($category->category_name) ? $category->category_name : ""}}'
  },
      ajax: {
        url: "{{url('search_cetegory')}}",
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          return {
            results:  $.map(data, function (item) {
              return {
                  text: item.category_name,
                  id: item.id
              }
          })
        };
    },
    cache: true
}
});

  $('.city_search').select2({
      placeholder: 'Select  City',
      ajax: {
        url: "{{url('search_city')}}",
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          return {
            results:  $.map(data, function (item) {
              return {
                  text: item.city,
                  id: item.city
              }
          })
        };
    },
    cache: true
}
});


</script>

<script>
var chat_appid = 'APP_ID';
var chat_auth = 'AUTH_KEY';
</script>
<?php if(!empty(Auth::id())) { ?>
 <script>
	var chat_id = "<?php echo Auth::id(); ?>";
	var chat_name = "<?php echo isset(Auth::user()->name) ? Auth::user()->name : Auth::user()->email; ?>"; 
	var chat_link = "<?php echo $request->session()->get('link', ''); ?>"; 
	var chat_avatar = "<?php echo $request->session()->get('avatar', ''); ?>"; 
	var chat_role = "<?php echo $request->session()->get('role', ''); ?>"; 
	var chat_friends = '<?php echo $request->session()->get('friends', ''); ?>'; // eg: 14,16,20 in case if friends feature is enabled.
	</script>
<?php } ?>
<script>
(function() {
    var chat_css = document.createElement('link'); chat_css.rel = 'stylesheet'; chat_css.type = 'text/css'; chat_css.href = 'https://fast.cometondemand.net/'+chat_appid+'x_xchat.css';
    document.getElementsByTagName("head")[0].appendChild(chat_css);
    var chat_js = document.createElement('script'); chat_js.type = 'text/javascript'; chat_js.src = 'https://fast.cometondemand.net/'+chat_appid+'x_xchat.js'; var chat_script = document.getElementsByTagName('script')[0]; chat_script.parentNode.insertBefore(chat_js, chat_script);
})();
</script>

</body>
</html>