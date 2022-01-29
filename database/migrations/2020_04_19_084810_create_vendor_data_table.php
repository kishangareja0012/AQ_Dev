<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_data', function (Blueprint $table) {
            $table->bigIncrements('vId');
            $table->string('vName', 150);
            $table->string('vCategory');
            $table->text('vAddress');
            $table->string('vEmail');
            $table->string('vPhone');
            $table->string('vMobile');
            $table->string('vLatitude');
            $table->string('vLongitude');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendor_data');
    }
}
