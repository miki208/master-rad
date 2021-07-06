<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePotentialmatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PotentialMatches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('runner_id');
            $table->bigInteger('suggested_runner');
            $table->float('score');
            $table->boolean('accepted')->nullable();
            $table->timestamps();

            $table->unique(['runner_id', 'suggested_runner']);
            $table->foreign('runner_id')->references('runner_id')->on('RunnerStats');
            $table->foreign('suggested_runner')->references('runner_id')->on('RunnerStats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PotentialMatches');
    }
}
