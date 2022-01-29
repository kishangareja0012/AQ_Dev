@extends('vendor::layouts.master')
@section('title', 'Update Quote')
@section('content')
<style>
/*page css*/
span.select2-selection.select2-selection--multiple {
    border-radius: 50px;
    padding-left: 15px;
}

/*page css*/
</style>

<div class="card-content-div  vendor-requests">

    <ul class="breadcrumb">
        <li><a href="{{url('vendor/quote-requests')}}">Dashboard</a></li>
        <!-- <li><a href="{{url('vendor/quote-requests')}}">Quote Requests</a></li> -->
        <li>Edit Profile</li>
    </ul>

    <div class="row">
        <div class="col-md-6 offset-md-3">

            @if(Session::has('flash_message'))
            <div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger"><em> {{ implode('', $errors->all(':message')) }}</em></div>
            @endif

            <!-- start: EXPORT DATA TABLE PANEL  -->
            <div class="panel panel-white">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form method="post" action="{{ url('vendor/submit_profile') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body login-form">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mobilenumber" class="control-label">Shop/Business
                                                    Name</label>
                                                <input style="margin-bottom: 5px;" type="text" name="company_name"
                                                    class="form-control number" id="company_name" required="true"
                                                    value="<?php echo isset($userdata->company_name) ? $userdata->company_name : ""  ?>"
                                                    readonly="true">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mobilenumber" class="control-label">Business Owner
                                                    name</label>
                                                <input style="margin-bottom: 5px;" type="text" name="contact_person"
                                                    class="form-control number" required id="contact_person"
                                                    value="<?php echo isset($userdata->contact_person) ? $userdata->contact_person : ""  ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                @php
                                                $category_array = explode(',',$userdata->category);
                                                @endphp
                                                <label class="select-label"> Select Category </label>
                                                <select class="form-control category_search select2" name="categories[]"
                                                    id="vendor_categories" multiple="true">
                                                    <!-- <option value="">--select category--</option> -->
                                                    <?php foreach ($categories as $key => $value): ?>
                                                    <option value="{{$value->id}}"
                                                        <?php if (in_array($value->id, $category_array)): ?> selected
                                                        <?php endif ?>>{{$value->category_name}}</option>
                                                    <?php endforeach ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="form-group">
                                                        <label for="mobilenumber" class="control-label">Mobile
                                                            Number</label>
                                                        <input style="margin-bottom: 5px;" type="text"
                                                            name="company_phone" class="form-control number" required
                                                            id="company_phone"
                                                            value="<?php echo isset($userdata->company_phone) ? $userdata->company_phone : ""  ?>"
                                                            readonly="true">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mobilenumber" class="control-label">Email ID</label>
                                                <input style="margin-bottom: 5px;" type="email" name="company_email"
                                                    id="company_email" class="form-control number"
                                                    value="<?php echo isset($userdata->company_email) ? $userdata->company_email : ""  ?>"
                                                    <?php if($userdata->company_email!=''){ echo 'readonly'; } ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mobilenumber" class="control-label">Location</label>
                                                <textarea name="company_address" id="company_address"
                                                    class="form-control number" required
                                                    rows="4"><?php echo isset($userdata->company_address) ? $userdata->company_address : ""  ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class=" form-group col-md-6">
                                            <div class="form-group">
                                                <label class="select-label"> City </label>
                                                <input style="margin-bottom: 5px;" type="text" name="company_city"
                                                    class="form-control number" required id="company_city"
                                                    value="<?php echo isset($userdata->company_city) ? $userdata->company_city : ""  ?>"
                                                    readonly="true">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <div class="form-group">
                                                <label for="" class="control-label">Enter Pincode:</label>
                                                <input style="margin-bottom: 5px;" type="text" name="pincode"
                                                    class="form-control number" required id="pincode"
                                                    value="<?php echo isset($userdata->company_pin) ? $userdata->company_pin : ""  ?>"
                                                    readonly="true">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mobilenumber" class="control-label">Website</label>
                                                <input style="margin-bottom: 5px;" type="text" name="website"
                                                    id="website" class="form-control number"
                                                    value="<?php echo isset($userdata->website) ? $userdata->website : ""  ?>">
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <div class="form-group ">
                                            <div class="btn-quotes text-center">
                                                <button type="submit" id="vendor-register" class="btn" type="button">
                                                    Update </button>
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