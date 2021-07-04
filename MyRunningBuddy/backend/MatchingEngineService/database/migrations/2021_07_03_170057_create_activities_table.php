<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('runner_id');
            $table->string('activity_id', 256);

            $table->float('distance_km')->nullable();
            $table->integer('moving_time_sec')->nullable();
            $table->float('total_elevation_gain_m')->nullable();
            $table->bigInteger('start_date');
            $table->float('start_lat');
            $table->float('start_lng');
            $table->float('end_lat');
            $table->float('end_lng');
            $table->float('pace')->nullable();

            $table->timestamps();

            $table->unique(['runner_id', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
}
