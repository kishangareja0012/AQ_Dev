<?php
$logged_in_data = Auth::user();
if ($logged_in_data['id'] === '') { ?>
    <script type="text/javascript">
        window.location = "{{ url('/') }}";
    </script>
<?php } ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>@yield('title') - AnyQuote</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaina+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets-new/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets-new/css/styles.css') }}">
    <link rel="icon" href="{{ asset('public/favicon.png') }}" type="image/gif" sizes="32x32">
</head>
<style>
    ul.button-list li a:hover {
        color: #fff;
        background: #939393;
    }

    ul.button-list li ul.dropdown {
        min-width: 95%;
        background: #ed8d21;
        display: none;
        position: absolute;
        z-index: 999;
        left: 10px;
    }

    ul.button-list li ul.dropdown li {
        display: block;
    }

    ul.dropdown.sub-menu:hover {
        font-weight: bold;
        color: #fff;
        background-color: #ed8d21;
    }

    ul.button-list li:hover ul.dropdown {
        display: block;
        line-height: 40px;
        padding: 0px 0px 0px 15px;
        border-radius: 10px !important;
    }

    .header-top .button-list li a.sub-menu-list:hover,
    .button-list li:nth-child(2) a.sub-menu-list {
        background: unset !important;
        color: unset !important;
    }

    .nav>li>a:hover,
    .nav>li>a {
        text-decoration: none;
        background-color: #eee;
        border-radius: 50px;
    }

    @media (max-width: 576px) and (min-width: 0px) {

        /* .header-top .navbar-collapse {height: 265px;} */
        .button-list li {
            float: none;
        }

        .button-list li {
            margin-bottom: 0;
        }
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
                    <a class="navbar-brand" href="{{url('/')}}"><img src="{{ asset('public/assets-new/images/Logo-color.png') }}" alt="" title=""></a>

                </div>
                <div class="collapse navbar-collapse navbar-right" id="myNavbar">
                    <ul class="nav navbar-nav nav-main">
                        <!--<li class="active"><a href="#">About</a></li>
                        <li ><a href="#">Services</a></li>
                        <li ><a href="#">Blogs</a></li>-->
                        <li><a class="contact-us" href="{{ url('contact_us') }}">Contact Us</a></li>
                    </ul>

                    @if (Auth::guest() && !Session::has('VENDORID'))
                    <ul class="nav navbar-nav  button-list">
                        <li><a href="#" data-toggle="modal" data-target="#login">Login to View Your Requests <span class="g-user"></span></a></li>
                        <li><a href="{{ url('vendor') }}">Are you a Vendor <span class="g-arrow-right"></span></a></li>
                    </ul>
                    @else
                    @php
                    $userdata = Auth::user();
                    $users = \DB::table('users')->where('id',$userdata['id'])->first();
                    $usename = ($users->name!='') ? $users->name : $users->mobile;
                    if ($usename==""):
                    $usename = $users->email;
                    endif
                    @endphp
                    <ul class="nav navbar-nav  button-list">
                        <li><a href="{{ url('/') }}">Create Quote Request</a> </li>
                        <li id="customer-first"><a href="javascript:;"><?php if (!Session::has('VENDORID')) {
                                                                            echo 'Hi, ' . $usename;
                                                                        } ?><span class="g-arrow-right"></span></a>
                            <ul class="dropdown sub-menu">
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="{{ url('customer/edit_profile') }}">Edit Profile</a></li>
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="{{ url('logout') }}">Logout</a></li>
                            </ul>
                        </li>


                        <!-- <li ><a href="{{ route('logout') }}"><?php if (!Session::has('VENDORID')) { ?>Hi {{ Auth::user()->name }}, <?php } ?>Logout <span class="g-arrow-right"></span></a>
                            <ul class="dropdown sub-menu">
                                <li class="sub-item"><a class="sub-menu-list" style="padding: 0px; border-radius: 0; " href="{{ url('customer/edit_profile') }}">Edit Profile</a></li>
                            </ul>
                        </li> -->
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

    <script src="{{ asset('public/assets-new/jquery/jquery.min.js') }}"></script>
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
    
        $('#customer-first, #vendor-first').click(function() {
            $('#myNavbar').animate({
                height: '265px'
            }, 500);
        });

        $(".otpShow").hide();
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-Token': $('input[name="_token"]').val()
                }
            });

            $(".product-description, .product-upload").hide();
            $("#show-product-description").on('click', function() {
                $(this).hide();
                $(".product-description").show();
            });
            $("#show-product-upload").on('click', function() {
                $(this).hide();
                $(".product-upload").show();
            });

            $('[id^=mobile],[id^=quote_mobile]').keypress(validateNumber);
            $('[id^=mobile],[id^=quote_mobile]').keyup(validateNumber);

            $("#cust-login,#resend-btn").click(function() {
                var custmobile = $('[id^=mobile]').val();
                if (custmobile.length === 10) {
                    $(".otpShow").show();
                    $("#cust-login").hide();
                } else {
                    $(".otpShow").hide();
                    $("#cust-login").show();
                    alert('Enter Valid Mobile Number');
                    $('[id^=mobile]').focus();
                    return false;
                }

                $.ajax({
                    url: '{{url("sendOtp")}}',
                    data: {
                        "custMobile": custmobile
                    },
                    type: 'post',
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    success: function(result) {
                        //alert(result);
                    }
                });
            });


            $("#quote_send_otp").click(function() {
                var custmobile = $('[id^=quote_mobile]').val();
                if (custmobile.length === 10) {
                    $(".quote_otp").show();
                    $("#quote_send_otp").hide();
                    $("#quote_verify_otp").show();
                } else {
                    $(".quote_otp").hide();
                    $("#quote_send_otp").show();
                    $("#quote_verify_otp").hide();
                    alert('Enter Valid Mobile Number');
                    $('[id^=quote_mobile]').focus();
                    return false;
                }

                $.ajax({
                    url: '{{url("sendOtp")}}',
                    data: {
                        "custMobile": custmobile
                    },
                    type: 'post',
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    success: function(result) {
                        //alert(result);
                    }
                });
            });


            $(".cust_otp").keyup(function() {
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
            var key = window.event ? event.keyCode : event.which;
            if (event.keyCode === 8 || event.keyCode === 46) {
                return true;
            } else if (key < 48 || key > 57) {
                return false;
            } else {
                return true;
            }
        }

        $("#profile_mobile").on('focusout', function() {
            var key = $("#profile_mobile").attr('data-login-method');
            var value = $("#profile_mobile").val();
            var button_type = $("#resend-btn").attr('id');
            if (key == 'Number') {
                var post_value = value;
            } else {
                var post_value = value;
            }
            $.ajax({
                url: '{{url("sendOtp")}}',
                data: {
                    "custMobile": post_value,
                    post_key: key,
                    profile_type: 'customer_profile_otp'
                },
                type: 'POST',
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                success: function(result) {
                    var resultData = JSON.parse(result);
                    if (resultData.error == 1) {
                        $("#otpMsg").addClass('alert-danger').removeClass('alert-success').text(resultData.message);
                    }
                    if (resultData.error == 0) {
                        $("#profile_verify_otp").val(resultData.OTP);
                        $(".otpShow").show();
                        $("#cust-login").hide();
                        if (button_type == 'resend-btn') {
                            $("#otpMsg").addClass('alert-success').removeClass('alert-danger').text('OTP Resend Successfully..!');
                        } else {
                            $("#otpMsg").removeClass('alert-danger').addClass('alert-success').text(resultData.message);
                        }
                    }
                }
            });
        });

        $("#profile-resend-btn").on('click', function() {
            var key = $("#profile_mobile").attr('data-login-method');
            var value = $("#profile_mobile").val();
            var button_type = $("#resend-btn").attr('id');
            if (key == 'Number') {
                var post_value = value;
            } else {
                var post_value = value;
            }
            $.ajax({
                url: '{{url("sendOtp")}}',
                data: {
                    "custMobile": post_value,
                    post_key: key,
                    profile_type: 'customer_profile_otp'
                },
                type: 'POST',
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                success: function(result) {
                    var resultData = JSON.parse(result);
                    if (resultData.error == 1) {
                        $("#otpMsg").addClass('alert-danger').removeClass('alert-success').text(resultData.message);
                    }
                    if (resultData.error == 0) {
                        $("#profile_verify_otp").val(resultData.OTP);
                        $(".otpShow").show();
                        $("#cust-login").hide();
                        if (button_type == 'resend-btn') {
                            $("#otpMsg").addClass('alert-success').removeClass('alert-danger').text('OTP Resend Successfully..!');
                        } else {
                            $("#otpMsg").removeClass('alert-danger').addClass('alert-success').text(resultData.message);
                        }
                    }
                }
            });
        });


        function verify_profile_otp() {
            
            var cust_otp = $("input[name='cust_otp1']").val() + $("input[name='cust_otp2']").val() + $("input[name='cust_otp3']").val() + $("input[name='cust_otp4']").val();

            var custmobile = $('#profile_mobile').val();
            if (cust_otp.length == 4) {
                $.ajax({
                    url: '{{url("verifyLoginOtp")}}',
                    data: {
                        "customer_mobile": custmobile,
                        "cust_otp": cust_otp
                    },
                    type: 'post',
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    success: function(result) {
                        
                        var res = JSON.parse(result);
                        $(".verifyOtp").show();
                        $(".verifyOtp").html(res.message);

                        $("#profile_update").submit();

                    }
                });
            } else {
                $("#cust_otp").prop('invalid', true);
                return false;
            }

        }

        $("#profile_email").on('focusout', function() {
            $('.update_button').attr('disabled', true);
            var cust_email = $("#profile_email").val();
            $.ajax({
                url: '{{url("sendEmailOtp")}}',
                data: {
                    "cust_email": cust_email
                },
                type: 'POST',
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                success: function(result) {
                    $('.update_button').attr('disabled', false);
                    var resultData = JSON.parse(result);
                    if (resultData.error == 1) {

                    }
                    if (resultData.error == 0) {
                        $(".otpShow").show();
                        $("#email_sent_otp").val(resultData.OTP);
                    }
                }
            });
        });

        $("#profile-email-resend").on('click', function() {
            $('.update_button').attr('disabled', true);
            var cust_email = $("#profile_email").val();
            $.ajax({
                url: '{{url("sendEmailOtp")}}',
                data: {
                    "cust_email": cust_email
                },
                type: 'POST',
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                success: function(result) {
                    $('.update_button').attr('disabled', false);
                    var resultData = JSON.parse(result);
                    if (resultData.error == 1) {

                    }
                    if (resultData.error == 0) {
                        $(".otpShow").show();
                        $("#email_sent_otp").val(resultData.OTP);
                    }
                }
            });
        });
    </script>
    <?php  /*  ?>
   <script>
var chat_appid = 'APP_ID';
var chat_auth = 'AUTH_KEY';
</script>
<?php if(!empty(Auth::id())) { ?>
 <script>
	var chat_id = "<?php echo Auth::id(); ?>";
	var chat_name = "<?php echo isset(Auth::user()->name) ? Auth::user()->name : Auth::user()->email; ?>"; 
	var chat_link = "<?php echo Session::get('link'); //$request->session()->get('link', ''); ?>"; 
	var chat_avatar = "<?php echo Session::get('avatar'); //$request->session()->get('avatar', ''); ?>"; 
	var chat_role = "<?php echo Session::get('role'); //$request->session()->get('role', ''); ?>"; 
	var chat_friends = '<?php echo Session::get('friends');//$request->session()->get('friends', ''); ?>'; // eg: 14,16,20 in case if friends feature is enabled.
	</script>
<?php } ?>
<script>
(function() {
    var chat_css = document.createElement('link'); chat_css.rel = 'stylesheet'; chat_css.type = 'text/css'; chat_css.href = 'https://fast.cometondemand.net/'+chat_appid+'x_xchat.css';
    document.getElementsByTagName("head")[0].appendChild(chat_css);
    var chat_js = document.createElement('script'); chat_js.type = 'text/javascript'; chat_js.src = 'https://fast.cometondemand.net/'+chat_appid+'x_xchat.js'; var chat_script = document.getElementsByTagName('script')[0]; chat_script.parentNode.insertBefore(chat_js, chat_script);
})();
</script>
<?php */ ?>

</body>

</html>