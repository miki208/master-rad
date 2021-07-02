<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastTimeSyncedRemovePreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('runners', function (Blueprint $table) {
            $table->dropColumn('preferences');
        });

        Schema::table('ExternalAccounts', function (Blueprint $table) {
            $table->bigInteger('last_sync')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('runners', function (Blueprint $table) {
            $table->string('preferences', 256)->default('');
        });

        Schema::table('ExternalAccounts', function (Blueprint $table) {
            $table->dropColumn('last_sync');
        });
    }
}
