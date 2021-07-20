<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('runner_id1');
            $table->bigInteger('runner_id2');
            $table->boolean('runner_id1_seen_last_message')->default(false);
            $table->boolean('runner_id2_seen_last_message')->default(false);
            $table->boolean('runner_id1_seen_conversation')->default(false);
            $table->boolean('runner_id2_seen_conversation')->default(false);
            $table->timestamps();

            $table->unique(['runner_id1', 'runner_id2']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
