<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRunnerstatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('RunnerStats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('runner_id')->unique();
            $table->float('avg_total_distance_per_week')->nullable();
            $table->float('avg_moving_time_per_week')->nullable();
            $table->float('avg_longest_distance_per_week')->nullable();
            $table->float('avg_pace_per_week')->nullable();
            $table->float('avg_total_elevation_per_week')->nullable();
            $table->float('avg_start_time_per_week')->nullable();
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
        Schema::dropIfExists('RunnerStats');
    }
}
