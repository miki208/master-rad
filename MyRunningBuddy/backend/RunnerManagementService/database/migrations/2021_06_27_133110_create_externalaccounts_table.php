<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalaccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ExternalAccounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('runner_id');
            $table->string('service_name', 64);
            $table->string('access_token', 256)->default('');
            $table->string('refresh_token', 256)->default('');
            $table->string('scope', 256)->default('');
            $table->bigInteger('expires_at')->nullable();
            $table->bigInteger('confirmation_id');
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
        Schema::dropIfExists('ExternalAccounts');
    }
}
